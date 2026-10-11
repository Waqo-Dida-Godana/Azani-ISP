<?php

namespace App\Services;

use App\Models\BandwidthPlan;
use App\Models\Institution;
use App\Models\InfrastructureRequirement;
use App\Models\LanNodeRate;
use App\Models\MonthlyBill;
use App\Models\Payment;
use App\Models\Upgrade;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * All Azani business computations live here so controllers stay thin.
 */
class BillingService
{
    /** Record a site survey and compute the PC / LAN purchase cost. */
    public function recordSurvey(Institution $inst, array $d): InfrastructureRequirement
    {
        $pcs   = (int) ($d['pcs_to_purchase'] ?? 0);
        $nodes = (int) ($d['lan_nodes_to_purchase'] ?? 0);

        $req = $inst->infrastructure()->updateOrCreate([], [
            'number_of_users'       => $d['number_of_users'],
            'computers_available'   => $d['computers_available'] ?? 0,
            'has_lan'               => (bool) ($d['has_lan'] ?? false),
            'pcs_to_purchase'       => $pcs,
            'lan_nodes_to_purchase' => $nodes,
            'pc_cost'               => $pcs * config('azani.pc_unit_cost'),
            'lan_cost'              => LanNodeRate::costFor($nodes),
            'is_ready'              => (bool) ($d['is_ready'] ?? false),
            'surveyed_on'           => $d['surveyed_on'] ?? now(),
            'remarks'               => $d['remarks'] ?? null,
        ]);

        if ($inst->status === 'registered') $inst->update(['status' => 'surveyed']);
        return $req;
    }

    /** Upgrade to a higher bandwidth with a 10% discount on the new plan. */
    public function upgrade(Institution $inst, BandwidthPlan $to): Upgrade
    {
        $from = $inst->plan;
        if (! $from || $to->mbps <= $from->mbps) {
            throw new \DomainException('Upgrade must be to a higher bandwidth than the current plan.');
        }
        $discount = round($to->monthly_cost * config('azani.upgrade_discount'), 2);

        return DB::transaction(function () use ($inst, $from, $to, $discount) {
            $inst->update(['bandwidth_plan_id' => $to->id]);
            return $inst->upgrades()->create([
                'from_plan_id' => $from->id, 'to_plan_id' => $to->id,
                'original_cost' => $to->monthly_cost, 'discount' => $discount,
                'discounted_cost' => $to->monthly_cost - $discount,
                'effective_month' => now()->startOfMonth(),
            ]);
        });
    }

    /** Monthly rate: discounted if the institution has upgraded to its current plan. */
    public function monthlyRate(Institution $inst): array
    {
        $up = $inst->upgrades()->where('to_plan_id', $inst->bandwidth_plan_id)->latest()->first();
        return $up ? [(float) $up->discounted_cost, true] : [(float) $inst->plan->monthly_cost, false];
    }

    /** Create bills for all active institutions for a month. */
    public function generateBills(Carbon $month): int
    {
        $month = $month->copy()->startOfMonth();
        $count = 0;
        Institution::where('status', 'active')->whereNotNull('bandwidth_plan_id')->each(function ($inst) use ($month, &$count) {
            [$rate, $isUpgrade] = $this->monthlyRate($inst);
            $bill = MonthlyBill::firstOrCreate(
                ['institution_id' => $inst->id, 'billing_month' => $month],
                ['amount' => $rate, 'is_upgrade_rate' => $isUpgrade,
                 'due_date' => $month->copy()->endOfMonth(),
                 'disconnection_date' => $month->copy()->addMonth()->day(config('azani.disconnection_day'))]
            );
            if ($bill->wasRecentlyCreated) $count++;
        });
        return $count;
    }

    /** Apply 15% fines after the due date and disconnect after the 10th. */
    public function processOverdue(?Carbon $today = null): array
    {
        $today = $today ?? now();
        $fined = $disconnected = 0;

        MonthlyBill::where('is_paid', false)->with('institution')->each(function ($bill) use ($today, &$fined, &$disconnected) {
            if ($today->gt($bill->due_date) && $bill->overdue_fine == 0) {
                $bill->update(['overdue_fine' => round($bill->amount * config('azani.overdue_fine_rate'), 2)]);
                $fined++;
            }
            if ($today->gt($bill->disconnection_date) && $bill->institution->status !== 'disconnected') {
                $bill->institution->update(['status' => 'disconnected']);
                $bill->update(['reconnection_fee' => config('azani.reconnection_fee')]);
                $disconnected++;
            }
        });
        return compact('fined', 'disconnected');
    }

    /** Pay a monthly bill in full: bill + fine + reconnection; reconnects if needed. */
    public function payBill(MonthlyBill $bill, array $d): void
    {
        DB::transaction(function () use ($bill, $d) {
            $base = ['institution_id' => $bill->institution_id, 'monthly_bill_id' => $bill->id,
                     'method' => $d['method'] ?? 'M-Pesa', 'reference' => $d['reference'] ?? null,
                     'paid_on' => $d['paid_on'] ?? now()];

            Payment::create($base + ['type' => 'monthly', 'amount' => $bill->amount]);
            if ($bill->overdue_fine > 0) Payment::create($base + ['type' => 'overdue_fine', 'amount' => $bill->overdue_fine]);
            if ($bill->reconnection_fee > 0) Payment::create($base + ['type' => 'reconnection', 'amount' => $bill->reconnection_fee]);

            $bill->update(['is_paid' => true, 'paid_on' => $base['paid_on']]);

            $inst = $bill->institution;
            if ($inst->status === 'disconnected' && ! $inst->bills()->where('is_paid', false)->exists()) {
                $inst->update(['status' => 'active']);
            }
        });
    }

    /** Pay installation (KSh 10,000) plus any PCs/LAN purchased; activates the connection. */
    public function payInstallation(Institution $inst, array $d): void
    {
        $req = $inst->infrastructure;
        if (! $req) throw new \DomainException('Record the site survey first.');

        DB::transaction(function () use ($inst, $req, $d) {
            $base = ['method' => $d['method'] ?? 'M-Pesa', 'reference' => $d['reference'] ?? null, 'paid_on' => $d['paid_on'] ?? now()];
            $inst->payments()->create($base + ['type' => 'installation', 'amount' => config('azani.installation_fee')]);
            if ($req->pc_cost > 0)  $inst->payments()->create($base + ['type' => 'computers', 'amount' => $req->pc_cost]);
            if ($req->lan_cost > 0) $inst->payments()->create($base + ['type' => 'lan', 'amount' => $req->lan_cost]);
            $req->update(['is_ready' => true]);
            $inst->update(['status' => 'active']);
        });
    }
}
