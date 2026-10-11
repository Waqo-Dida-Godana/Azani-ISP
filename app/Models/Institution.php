<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * An institution (primary, junior, senior school or college) receiving services.
 * One Institution -> One InfrastructureRequirement, Many Payments, Many Bills, Many Upgrades.
 */
class Institution extends Model
{
    protected $fillable = [
        'name', 'category', 'location', 'postal_address',
        'contact_name', 'contact_id_number', 'contact_designation', 'contact_phone', 'contact_email',
        'bandwidth_plan_id', 'status', 'registered_on',
    ];

    protected $casts = ['registered_on' => 'date'];

    public const CATEGORIES = ['primary' => 'Primary School', 'junior' => 'Junior School',
                               'senior' => 'Senior School', 'college' => 'College'];

    public function plan(): BelongsTo { return $this->belongsTo(BandwidthPlan::class, 'bandwidth_plan_id'); }
    public function infrastructure(): HasOne { return $this->hasOne(InfrastructureRequirement::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function bills(): HasMany { return $this->hasMany(MonthlyBill::class); }
    public function upgrades(): HasMany { return $this->hasMany(Upgrade::class); }

    public function hasPaid(string $type): bool
    {
        return $this->payments()->where('type', $type)->exists();
    }

    /** Defaulters: an unpaid bill whose due date has passed. */
    public function scopeDefaulters($q)
    {
        return $q->whereHas('bills', fn ($b) => $b->where('is_paid', false)->whereDate('due_date', '<', now()));
    }

    /** Disconnection issues: unpaid past the 10th of the next month, or already disconnected. */
    public function scopeDisconnectionIssues($q)
    {
        return $q->where('status', 'disconnected')
            ->orWhereHas('bills', fn ($b) => $b->where('is_paid', false)->whereDate('disconnection_date', '<', now()));
    }
}
