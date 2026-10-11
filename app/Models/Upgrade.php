<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upgrade extends Model
{
    protected $fillable = ['institution_id', 'from_plan_id', 'to_plan_id', 'original_cost',
                           'discount', 'discounted_cost', 'effective_month'];

    protected $casts = ['effective_month' => 'date'];

    public function institution() { return $this->belongsTo(Institution::class); }
    public function fromPlan() { return $this->belongsTo(BandwidthPlan::class, 'from_plan_id'); }
    public function toPlan() { return $this->belongsTo(BandwidthPlan::class, 'to_plan_id'); }
}
