<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['institution_id', 'monthly_bill_id', 'type', 'amount', 'method', 'reference', 'paid_on'];

    protected $casts = ['paid_on' => 'date'];

    public const TYPES = [
        'registration' => 'Registration Fee', 'installation' => 'Installation Fee',
        'monthly' => 'Monthly Internet', 'overdue_fine' => 'Overdue Fine',
        'reconnection' => 'Re-connection Fee', 'computers' => 'Personal Computers', 'lan' => 'LAN Nodes',
    ];

    public function institution() { return $this->belongsTo(Institution::class); }
    public function bill() { return $this->belongsTo(MonthlyBill::class, 'monthly_bill_id'); }
}
