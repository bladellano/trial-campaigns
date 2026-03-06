<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = ['subject', 'body', 'contact_list_id', 'status', 'scheduled_at'];

    
    protected $casts = [
        'status' => 'string',
    ];

    public function contactList(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ContactList::class);
    }

    public function sends(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CampaignSend::class);
    }

    public function getStatsAttribute(): array
    {
        $sends = $this->sends;

        return [
            'pending' => $sends->where('status', 'pending')->count(),
            'sent'    => $sends->where('status', 'sent')->count(),
            'failed'  => $sends->where('status', 'failed')->count(),
            'total'   => $sends->count(),
        ];
    }
}
