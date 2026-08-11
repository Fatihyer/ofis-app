<?php

namespace App\Console;

use Illuminate\Support\Facades\DB;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\Transfer;
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */ 
    protected $commands = [

        \App\Console\Commands\ClearLog::class,
        \App\Console\Commands\ClearBackupLogs::class,
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
{
    // Schedule the renamed custom command
    $schedule->command('transfer:call')->everyMinute();
    $schedule->command('backup:clear-logs')->daily()->at('01:00');
    $schedule->command('backup:run')->daily()->at('02:00');
    $schedule->command('google-ads:upload-form-leads --limit=50')
        ->everyTenMinutes()
        ->withoutOverlapping();
    $schedule->command('gmail:read-requests --accounts=contact,resparis,paris,sales,sales2 --days=7 --limit=100')
        ->everyTwoMinutes()
        ->withoutOverlapping(10);
   $schedule->command('hermes:archive-daily')
        ->timezone('Europe/Paris')
        ->dailyAt('23:50');

    $schedule->command('hermes:archive-driver-daily-from-transfers')
        ->timezone('Europe/Paris')
        ->dailyAt('23:52');

    $schedule->command('hermes:archive-driver-working-stats')
        ->timezone('Europe/Paris')
        ->dailyAt('23:54');
    // Remove or comment out any conflicting or redundant schedules
}

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
