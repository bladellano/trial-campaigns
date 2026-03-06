<?php

namespace App\Services;

use App\Jobs\SendCampaignEmail;
use App\Models\Campaign;
use App\Models\CampaignSend;

class CampaignService
{
    /**
     * Dispatch a campaign to all active contacts in its list.
     */
    public function dispatch(Campaign $campaign): void
    {
        $contacts = $campaign->contactList->contacts()
            ->where('status', 'active')
            ->get();

        foreach ($contacts as $contact) {
            $send = CampaignSend::create([
                'campaign_id' => $campaign->id,
                'contact_id'  => $contact->id,
                'status'      => 'pending',
            ]);

            SendCampaignEmail::dispatch($send->id);
        }

        $campaign->update(['status' => 'sending']);
    }

    public function buildPayload(Campaign $campaign, array $extra = []): array
    {
        $base = [
            'subject' => $campaign->subject,
            'body'    => $campaign->body,
        ];

        return [...$base, ...$extra];
    }

    public function resolveReplyTo(Campaign $campaign)
    {
        if (empty($campaign->reply_to)) {
            return null;
        }

        return $campaign->reply_to;
    }
}