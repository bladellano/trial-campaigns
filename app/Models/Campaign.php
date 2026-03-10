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
        'scheduled_at' => 'datetime',
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
        // Use DB aggregation instead of loading all sends into memory
        $stats = $this->sends()
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            ")
            ->first();

        return [
            'pending' => (int) $stats->pending,
            'sent'    => (int) $stats->sent,
            'failed'  => (int) $stats->failed,
            'total'   => (int) $stats->total,
        ];
    }
}
