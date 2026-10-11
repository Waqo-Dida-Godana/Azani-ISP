<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InfrastructureRequirement extends Model
{
    protected $fillable = [
        'institution_id', 'number_of_users', 'computers_available', 'has_lan',
        'pcs_to_purchase', 'lan_nodes_to_purchase', 'pc_cost', 'lan_cost',
        'is_ready', 'surveyed_on', 'remarks',
    ];

    protected $casts = ['has_lan' => 'boolean', 'is_ready' => 'boolean', 'surveyed_on' => 'date'];

    public function institution() { return $this->belongsTo(Institution::class); }

    /** PCs + LAN ("assorted services"). */
    public function getAssortedCostAttribute(): float
    {
        return (float) $this->pc_cost + (float) $this->lan_cost;
    }

    /** Installation fee + PCs + LAN. */
    public function getTotalInstallationCostAttribute(): float
    {
        return config('azani.installation_fee') + $this->assorted_cost;
    }
}
