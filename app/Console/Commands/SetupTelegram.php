<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class SetupTelegram extends Command
{
    protected $signature = 'pairs:setup-telegram {--token= : Токен бота} {--chat-id= : ID чата} {--test : Отправить тестовое сообщение}';
    protected $description = 'Настройка Telegram для уведомлений';

    private TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }

    public function handle(): void
    {
        $this->info('🤖 Настройка Telegram уведомлений...');

        // Проверяем тестовый режим
        if ($this->option('test')) {
            $this->sendTestMessage();
            return;
        }

        $token = $this->option('token');
        $chatId = $this->option('chat-id');

        if (!$token || !$chatId) {
            $this->error('❌ Необходимо указать --token и --chat-id');
            $this->info('Пример: php artisan pairs:setup-telegram --token="1234567890:ABC..." --chat-id="-1001234567890"');
            return;
        }

        // Сохраняем настройки
        Setting::set('telegram_bot_token', $token);
        Setting::set('telegram_chat_id', $chatId);

        $this->info('✅ Настройки Telegram сохранены');

        // Проверяем подключение
        $this->info('🔍 Проверяем подключение к Telegram...');
        
        if ($this->telegramService->isConfigured()) {
            $this->info('✅ Telegram настроен корректно');
            
            // Отправляем тестовое сообщение
            $this->info('📤 Отправляем тестовое сообщение...');
            $success = $this->telegramService->sendTestMessage();
            
            if ($success) {
                $this->info('✅ Тестовое сообщение отправлено успешно!');
            } else {
                $this->error('❌ Не удалось отправить тестовое сообщение');
            }
        } else {
            $this->error('❌ Telegram не настроен корректно');
        }
    }

    private function sendTestMessage(): void
    {
        $this->info('🧪 Отправляем тестовое сообщение...');
        
        if (!$this->telegramService->isConfigured()) {
            $this->error('❌ Telegram не настроен. Сначала настройте токен и ID чата.');
            return;
        }
        
        $success = $this->telegramService->sendTestMessage();
        
        if ($success) {
            $this->info('✅ Тестовое сообщение отправлено успешно!');
        } else {
            $this->error('❌ Не удалось отправить тестовое сообщение');
        }
    }
}
