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
     * Отправляет алерты для всех готовых возможностей
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

        $sentCount = 0;

        foreach ($opportunities as $opportunity) {
            try {
                $success = $this->telegramService->sendArbitrageAlert($opportunity->toArray());

                if ($success) {
                    $opportunity->markAsAlerted();
                    $sentCount++;

                    Log::info('Алерт отправлен', [
                        'pair' => $opportunity->currencyPair->symbol,
                        'profit' => $opportunity->net_profit_percent,
                        'buy_exchange' => $opportunity->buyExchange->name,
                        'sell_exchange' => $opportunity->sellExchange->name,
                    ]);
                } else {
                    Log::error('Не удалось отправить алерт', [
                        'pair' => $opportunity->currencyPair->symbol,
                        'profit' => $opportunity->net_profit_percent,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Ошибка при отправке алерта', [
                    'opportunity_id' => $opportunity->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        return $sentCount;
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
