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

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 60;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [10, 30, 60];

    public function __construct(
        private readonly int $campaignSendId
    ) {}

    public function handle(): void
    {
        // Eager load relationships to avoid N+1 queries
        $send = CampaignSend::with(['contact', 'campaign'])->find($this->campaignSendId);

        if (!$send) {
            return;
        }

        // Idempotency check - don't resend if already sent
        if ($send->status === 'sent') {
            Log::info('Campaign send already sent, skipping', ['send_id' => $send->id]);
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

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    private function sendEmail(string $to, string $subject, string $body): void
    {
        // Mock email sending
        Log::info("Sending email to {$to}: {$subject}");
    }
}
