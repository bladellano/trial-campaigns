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
            // Process campaigns in chunks to avoid memory issues
            Campaign::where('status', 'draft')
                ->where('scheduled_at', '<=', now())
                ->whereNotNull('scheduled_at')
                ->chunkById(50, function ($campaigns) {
                    foreach ($campaigns as $campaign) {
                        try {
                            app(CampaignService::class)->dispatch($campaign);

                            // Clear the scheduled_at to prevent reprocessing
                            $campaign->update(['scheduled_at' => null]);
                        } catch (\Exception $e) {
                            \Log::error('Failed to dispatch scheduled campaign', [
                                'campaign_id' => $campaign->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                });
        })->everyMinute();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
