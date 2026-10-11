<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LanNodeRate extends Model
{
    protected $fillable = ['min_nodes', 'max_nodes', 'cost'];

    /** Returns the cost band for a number of nodes (Table 2). */
    public static function costFor(int $nodes): float
    {
        if ($nodes <= 0) return 0;
        $rate = static::where('min_nodes', '<=', $nodes)->where('max_nodes', '>=', $nodes)->first();
        if (! $rate) {
            throw new \InvalidArgumentException("LAN nodes must be between 2 and 100 (got {$nodes}).");
        }
        return (float) $rate->cost;
    }
}
