<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;


Schedule::command('pairs:poll-queued')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        Log::error('Ошибка при опросе бирж (queued)');
    });

Schedule::command('pairs:arbitrage-queued')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        Log::error('Ошибка при анализе арбитража (queued)');
    });

Schedule::command('pairs:cleanup-old-data')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onFailure(function () {
        Log::error('Ошибка при очистке старых данных');
    });
