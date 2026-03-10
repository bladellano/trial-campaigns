<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'body' => $this->body,
            'status' => $this->status,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'contact_list' => new ContactListResource($this->whenLoaded('contactList')),
            'contact_list_id' => $this->contact_list_id,
            'stats' => $this->when(
                $this->relationLoaded('sends') || isset($this->pending_count),
                [
                    'pending' => $this->pending_count ?? 0,
                    'sent' => $this->sent_count ?? 0,
                    'failed' => $this->failed_count ?? 0,
                    'total' => ($this->pending_count ?? 0) + ($this->sent_count ?? 0) + ($this->failed_count ?? 0),
                ]
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
