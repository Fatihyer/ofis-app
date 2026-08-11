<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ClearBackupLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:clear-logs'; // Renamed signature

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup old backups and log the process';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $this->info('Starting cleanup...');
            Log::info('Starting cleanup process...');

            // Invoke Spatie's backup:clean command
            $cleanupCommand = $this->getApplication()->find('backup:clean');
            $exitCode = $cleanupCommand->run($this->input, $this->output);

            if ($exitCode === 0) {
                $this->info('Cleanup completed successfully!');
                Log::info('Cleanup completed successfully!');
            } else {
                $this->error('Cleanup failed!');
                Log::error('Cleanup failed!');
            }
        } catch (\Exception $e) {
            $this->error('Cleanup failed because: ' . $e->getMessage());

            // Log the full exception trace for further investigation
            Log::error('Cleanup failed with exception: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
        }
    }
}
