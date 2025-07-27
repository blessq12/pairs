<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;

class InitSettings extends Command
{
    protected $signature = 'pairs:init-settings';

    protected $description = 'Initialize default settings for the pairs trading system';

    public function handle(): void
    {
        $this->info('Initializing settings...');

        Setting::firstOrCreate();

        $this->info('Settings have been initialized!');
        $this->newLine();
        $this->info('You can now edit these settings in the admin panel.');
    }
} 