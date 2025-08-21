<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;


Schedule::command('pairs:poll-exchanges')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        Log::error('Ошибка при опросе бирж');
    });

Schedule::command('pairs:arbitrage-analysis')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        Log::error('Ошибка при анализе арбитража');
    });

Schedule::command('pairs:cleanup-old-data')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onFailure(function () {
        Log::error('Ошибка при очистке старых данных');
    });
