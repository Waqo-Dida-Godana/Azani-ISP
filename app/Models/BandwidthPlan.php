<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BandwidthPlan extends Model
{
    protected $fillable = ['mbps', 'monthly_cost'];

    public function institutions() { return $this->hasMany(Institution::class); }

    public function getLabelAttribute(): string
    {
        return "{$this->mbps} MBPS - KSh " . number_format($this->monthly_cost);
    }
}
