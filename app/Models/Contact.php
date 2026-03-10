<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * A contact can belong to many contact lists.
     */
    public function contactLists(): BelongsToMany
    {
        return $this->belongsToMany(ContactList::class, 'contact_contact_list');
    }

    /**
     * A contact has many campaign sends.
     */
    public function campaignSends(): HasMany
    {
        return $this->hasMany(CampaignSend::class);
    }

    /**
     * Scope to get only active contacts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get only unsubscribed contacts.
     */
    public function scopeUnsubscribed($query)
    {
        return $query->where('status', 'unsubscribed');
    }

    /**
     * Mark contact as unsubscribed.
     */
    public function unsubscribe(): bool
    {
        return $this->update(['status' => 'unsubscribed']);
    }
}
