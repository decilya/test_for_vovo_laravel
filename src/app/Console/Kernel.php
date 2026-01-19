<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Ежедневный анализ логов
        $schedule->command('security:analyze --hours=24 --export')
            ->dailyAt('02:00')
            ->onOneServer();

        // Очистка старых логов
        $schedule->command('security:logs:clean')->weekly();

        // Отчет администратору
        if (config('app.env') === 'production') {
            $schedule->command('security:analyze --hours=168 --notify')
                ->weeklyOn(1, '8:00') // Каждый понедельник в 8:00
                ->emailOutputTo('security@example.com');
        }
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
