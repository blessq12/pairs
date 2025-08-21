<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private string $botToken;
    private string $chatId;
    private string $messageTemplate;

    public function __construct()
    {
        $this->botToken = Setting::get('telegram_bot_token', '');
        $this->chatId = Setting::get('telegram_chat_id', '');
        $this->messageTemplate = Setting::get('telegram_message_template', 'Пара {pair}: профит {profit}% на {exchange}');
    }

    /**
     * Отправляет сообщение в Telegram
     */
    public function sendMessage(string $message): bool
    {
        if (empty($this->botToken) || empty($this->chatId)) {
            Log::warning('Telegram не настроен: отсутствует токен или ID чата');
            return false;
        }

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            if ($response->successful()) {
                Log::info('Сообщение отправлено в Telegram', ['message' => $message]);
                return true;
            } else {
                Log::error('Ошибка отправки в Telegram', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'message' => $message
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Исключение при отправке в Telegram', [
                'exception' => $e->getMessage(),
                'message' => $message
            ]);
            return false;
        }
    }

    /**
     * Отправляет алерт об арбитражной возможности
     */
    public function sendArbitrageAlert(array $opportunity): bool
    {
        $message = $this->formatArbitrageMessage($opportunity);
        return $this->sendMessage($message);
    }

    /**
     * Форматирует сообщение об арбитражной возможности
     */
    private function formatArbitrageMessage(array $opportunity): string
    {
        $buyExchange = $opportunity['buy_exchange']->name;
        $sellExchange = $opportunity['sell_exchange']->name;
        $pair = $opportunity['currency_pair']->symbol;
        $netProfit = round($opportunity['net_profit_percent'], 2);
        $profitUsd = round($opportunity['profit_usd'], 2);
        $buyPrice = $opportunity['buy_price'];
        $sellPrice = $opportunity['sell_price'];

        $message = "🚨 <b>АРБИТРАЖНАЯ ВОЗМОЖНОСТЬ</b>\n\n";
        $message .= "💰 <b>Пара:</b> {$pair}\n";
        $message .= "📈 <b>Профит:</b> {$netProfit}% (${$profitUsd})\n\n";
        $message .= "🛒 <b>Покупка:</b> {$buyExchange} по ${$buyPrice}\n";
        $message .= "🛍️ <b>Продажа:</b> {$sellExchange} по ${$sellPrice}\n\n";
        $message .= "📊 <b>Комиссии:</b> " . round($opportunity['total_commission'] * 100, 2) . "%\n";
        $message .= "⏰ <b>Обнаружено:</b> " . $opportunity['detected_at']->format('H:i:s') . "\n\n";
        $message .= "🔗 <b>Действуй быстро!</b>";

        return $message;
    }

    /**
     * Отправляет тестовое сообщение
     */
    public function sendTestMessage(): bool
    {
        $message = "🧪 <b>Тестовое сообщение</b>\n\n";
        $message .= "Система арбитража работает корректно!\n";
        $message .= "⏰ " . now()->format('Y-m-d H:i:s');

        return $this->sendMessage($message);
    }

    /**
     * Отправляет сообщение об ошибке
     */
    public function sendErrorMessage(string $error): bool
    {
        $message = "❌ <b>ОШИБКА СИСТЕМЫ</b>\n\n";
        $message .= "{$error}\n\n";
        $message .= "⏰ " . now()->format('Y-m-d H:i:s');

        return $this->sendMessage($message);
    }

    /**
     * Проверяет настройки Telegram
     */
    public function isConfigured(): bool
    {
        return !empty($this->botToken) && !empty($this->chatId);
    }
}
