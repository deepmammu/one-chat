<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class CloseTodayTimesheets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timesheets:close-today';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close all open daily_timeSheet entries for today by setting Status=closed';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $table = env('TIMESHEETS_TABLE', 'daily_timeSheet');
        $dateCol = env('TIMESHEETS_DATE_COLUMN', 'Date');
        $statusCol = 'Status';

        $today = now()->toDateString();

        $affected = DB::table($table)
            ->where($dateCol, $today)
            ->where($statusCol, 'open')
            ->update([
                $statusCol => 'closed',
                'updated_at' => now(),
            ]);

        $this->info("Closed {$affected} open timesheet(s) for {$today}.");
        return self::SUCCESS;
    }
}
