<?php

namespace App\Console;

use App\Console\Commands\ParseCertificate;
use App\Console\Commands\ParseFinData;
use App\Console\Commands\ReportBigConsole;
use App\Console\Commands\ReportConsole;
use App\Jobs\CleanOldRedisData;
use App\Jobs\ParseCertificateJob;
use App\Jobs\ParseNewCalcBfo2400;
use App\Jobs\ParserFin2024Job;
use App\Jobs\ParserFinJob;
use App\Jobs\ReportBigJob;
use App\Jobs\ReportJob;
use App\Parsers\ParserRunner;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // тут джобы для крона если надо
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
