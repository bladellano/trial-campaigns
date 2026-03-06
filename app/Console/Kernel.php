<?php

namespace App\Console;

use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            $campaigns = Campaign::where('scheduled_at', '<=', now())->get();

            foreach ($campaigns as $campaign) {
                app(CampaignService::class)->dispatch($campaign);
            }
        })->everyMinute();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
