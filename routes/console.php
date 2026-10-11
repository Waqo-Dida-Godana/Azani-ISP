<?php

use App\Services\BillingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('azani:bill {month?}', function (BillingService $b, ?string $month = null) {
    $m = $month ? \Carbon\Carbon::createFromFormat('Y-m', $month) : now();
    $this->info($b->generateBills($m) . ' bills generated for ' . $m->format('M Y'));
})->purpose('Generate monthly bills for active institutions');

Artisan::command('azani:overdue', function (BillingService $b) {
    $r = $b->processOverdue();
    $this->info("Fined: {$r['fined']}  Disconnected: {$r['disconnected']}");
})->purpose('Apply 15% overdue fines and disconnect after the 10th');

Schedule::command('azani:bill')->monthlyOn(1, '00:05');
Schedule::command('azani:overdue')->dailyAt('00:30');
