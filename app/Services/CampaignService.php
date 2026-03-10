<?php

namespace App\Services;

use App\Jobs\SendCampaignEmail;
use App\Models\Campaign;
use App\Models\CampaignSend;

class CampaignService
{
    /**
     * Dispatch a campaign to all active contacts in its list.
     * Uses chunking to avoid loading all contacts into memory.
     */
    public function dispatch(Campaign $campaign): void
    {
        $campaign->update(['status' => 'sending']);

        // Process contacts in chunks to avoid memory issues
        $campaign->contactList->contacts()
            ->where('status', 'active')
            ->chunkById(500, function ($contacts) use ($campaign) {
                foreach ($contacts as $contact) {
                    // Use firstOrCreate for idempotency - prevents duplicate sends
                    $send = CampaignSend::firstOrCreate(
                        [
                            'campaign_id' => $campaign->id,
                            'contact_id'  => $contact->id,
                        ],
                        [
                            'status' => 'pending',
                        ]
                    );

                    // Only dispatch job if the send was just created or is still pending
                    if ($send->status === 'pending') {
                        SendCampaignEmail::dispatch($send->id);
                    }
                }
            });
    }

    public function buildPayload(Campaign $campaign, array $extra = []): array
    {
        $base = [
            'subject' => $campaign->subject,
            'body'    => $campaign->body,
        ];

        return [...$base, ...$extra];
    }
}
