<?php

namespace App\Jobs;

use App\Models\CampaignSend;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCampaignEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(
        private readonly int $campaignSendId
    ) {}

    public function handle(): void
    {
        $send = CampaignSend::find($this->campaignSendId);

        if (!$send) {
            return;
        }

        try {
            $this->sendEmail($send->contact->email, $send->campaign->subject, $send->campaign->body);

            $send->update(['status' => 'sent']);

        } catch (\Exception $e) {
            $send->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Campaign send failed', ['send_id' => $send->id, 'error' => $e->getMessage()]);
        }
    }

    private function sendEmail(string $to, string $subject, string $body): void
    {
        Log::info("Sending email to {$to}: {$subject}");
    }
}