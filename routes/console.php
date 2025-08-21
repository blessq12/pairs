<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

// Основная команда арбитража - каждые 5 минут
Schedule::command('arbitrage:run')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        Log::info('Планировщик арбитража выполнен успешно');
    })
    ->onFailure(function () {
        Log::error('Планировщик арбитража завершился с ошибкой');
    });

// Проверка статуса системы каждый час
Schedule::command('arbitrage:status')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Очистка старых арбитражных возможностей каждый день в 2:00
Schedule::call(function () {
    $cutoffDate = now()->subDays(7); // Удаляем возможности старше 7 дней
    $deleted = \App\Models\ArbitrageOpportunity::where('created_at', '<', $cutoffDate)->delete();
    Log::info("Очищено {$deleted} старых арбитражных возможностей");
})->dailyAt('02:00')
    ->name('cleanup-arbitrage-opportunities')
    ->withoutOverlapping();

// Отправка ежедневного отчета в 9:00
Schedule::call(function () {
    $yesterday = now()->subDay();
    $opportunities = \App\Models\ArbitrageOpportunity::whereDate('created_at', $yesterday)->count();
    $totalProfit = \App\Models\ArbitrageOpportunity::whereDate('created_at', $yesterday)->sum('profit_usd');

    $message = "📊 <b>ЕЖЕДНЕВНЫЙ ОТЧЕТ</b>\n\n";
    $message .= "📅 Дата: " . $yesterday->format('d.m.Y') . "\n";
    $message .= "💰 Найдено возможностей: {$opportunities}\n";
    $message .= "💵 Общий профит: \${$totalProfit}\n";
    $message .= "⏰ Отчет сгенерирован: " . now()->format('H:i:s');

    $telegramService = app(\App\Services\TelegramService::class);
    if ($telegramService->isConfigured()) {
        $telegramService->sendMessage($message);
    }
})->dailyAt('09:00')
    ->name('daily-arbitrage-report')
    ->withoutOverlapping();
