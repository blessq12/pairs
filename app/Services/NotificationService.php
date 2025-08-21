<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    private TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Отправляет алерты для всех готовых возможностей одним сообщением
     */
    public function sendArbitrageAlerts(Collection $opportunities): int
    {
        if (!$this->isNotificationsEnabled()) {
            Log::info('Уведомления отключены в настройках');
            return 0;
        }

        if (!$this->telegramService->isConfigured()) {
            Log::warning('Telegram не настроен, алерты не отправлены');
            return 0;
        }

        if ($opportunities->isEmpty()) {
            Log::info('Нет арбитражных возможностей для отправки');
            return 0;
        }

        try {
            // Конвертируем коллекцию в массив для отправки
            $opportunitiesArray = $opportunities->toArray();

            // Отправляем одно сообщение со всеми возможностями
            $success = $this->telegramService->sendArbitrageSummary($opportunitiesArray);

            if ($success) {
                // Помечаем все возможности как отправленные
                foreach ($opportunities as $opportunity) {
                    $opportunity->markAsAlerted();
                }

                $count = $opportunities->count();
                Log::info('Сводка арбитражных возможностей отправлена', [
                    'count' => $count,
                    'total_profit' => $opportunities->sum('profit_usd'),
                    'avg_profit' => $opportunities->avg('profit_usd'),
                ]);

                return $count;
            } else {
                Log::error('Не удалось отправить сводку арбитражных возможностей');
                return 0;
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при отправке сводки арбитражных возможностей', [
                'exception' => $e->getMessage(),
                'count' => $opportunities->count(),
            ]);
            return 0;
        }
    }

    /**
     * Отправляет тестовое сообщение
     */
    public function sendTestMessage(): bool
    {
        if (!$this->isNotificationsEnabled()) {
            Log::warning('Уведомления отключены, тестовое сообщение не отправлено');
            return false;
        }

        if (!$this->telegramService->isConfigured()) {
            Log::warning('Telegram не настроен, тестовое сообщение не отправлено');
            return false;
        }

        return $this->telegramService->sendTestMessage();
    }

    /**
     * Отправляет сообщение об ошибке системы
     */
    public function sendErrorMessage(string $error): bool
    {
        if (!$this->telegramService->isConfigured()) {
            Log::error('Telegram не настроен, сообщение об ошибке не отправлено: ' . $error);
            return false;
        }

        return $this->telegramService->sendErrorMessage($error);
    }

    /**
     * Проверяет, включены ли уведомления
     */
    private function isNotificationsEnabled(): bool
    {
        return Setting::get('notification_enabled', true);
    }

    /**
     * Получает статистику уведомлений
     */
    public function getNotificationStats(): array
    {
        $enabled = $this->isNotificationsEnabled();
        $telegramConfigured = $this->telegramService->isConfigured();

        return [
            'notifications_enabled' => $enabled,
            'telegram_configured' => $telegramConfigured,
            'can_send_alerts' => $enabled && $telegramConfigured,
        ];
    }
}
