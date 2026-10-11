<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyBill extends Model
{
    protected $fillable = ['institution_id', 'billing_month', 'amount', 'is_upgrade_rate', 'due_date',
                           'disconnection_date', 'overdue_fine', 'reconnection_fee', 'is_paid', 'paid_on'];

    protected $casts = ['billing_month' => 'date', 'due_date' => 'date', 'disconnection_date' => 'date',
                        'paid_on' => 'date', 'is_paid' => 'boolean', 'is_upgrade_rate' => 'boolean'];

    public function institution() { return $this->belongsTo(Institution::class); }
    public function payments() { return $this->hasMany(Payment::class); }

    public function getTotalDueAttribute(): float
    {
        return (float) $this->amount + (float) $this->overdue_fine + (float) $this->reconnection_fee;
    }
}
