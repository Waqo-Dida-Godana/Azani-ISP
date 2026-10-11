<?php

namespace Database\Seeders;

use App\Models\BandwidthPlan;
use App\Models\Institution;
use App\Models\LanNodeRate;
use App\Services\BillingService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(BillingService $billing): void
    {
        // Table 1
        foreach ([[4, 1200], [10, 2000], [20, 3500], [25, 4000], [50, 7000]] as [$mbps, $cost]) {
            BandwidthPlan::create(['mbps' => $mbps, 'monthly_cost' => $cost]);
        }
        // Table 2
        foreach ([[2, 10, 10000], [11, 20, 20000], [21, 40, 30000], [41, 100, 40000]] as [$a, $b, $c]) {
            LanNodeRate::create(['min_nodes' => $a, 'max_nodes' => $b, 'cost' => $c]);
        }

        // Sample data
        $samples = [
            ['Kilimani Primary School', 'primary', 'Nairobi', 4, 30, 5, false, 10, 15],
            ['Moi Junior School', 'junior', 'Eldoret', 10, 60, 20, true, 0, 0],
            ['Alliance Senior School', 'senior', 'Kikuyu', 20, 200, 40, false, 20, 45],
            ['Mombasa Technical College', 'college', 'Mombasa', 25, 400, 100, true, 0, 0],
        ];
        foreach ($samples as $n => [$name, $cat, $loc, $mbps, $users, $pcs, $lan, $buyPc, $nodes]) {
            $i = Institution::create([
                'name' => $name, 'category' => $cat, 'location' => $loc,
                'contact_name' => "Contact Person " . ($n + 1), 'contact_id_number' => '2' . str_pad($n, 7, '0'),
                'contact_designation' => 'Principal', 'contact_phone' => '07' . str_pad($n, 8, '1'),
                'contact_email' => "contact{$n}@example.ac.ke",
                'bandwidth_plan_id' => BandwidthPlan::where('mbps', $mbps)->value('id'),
                'registered_on' => now()->subMonths(3),
            ]);
            $i->payments()->create(['type' => 'registration', 'amount' => config('azani.registration_fee'), 'paid_on' => now()->subMonths(3)]);
            $billing->recordSurvey($i, ['number_of_users' => $users, 'computers_available' => $pcs, 'has_lan' => $lan,
                'pcs_to_purchase' => $buyPc, 'lan_nodes_to_purchase' => $nodes, 'is_ready' => $lan, 'surveyed_on' => now()->subMonths(3)]);
            $billing->payInstallation($i->fresh(), ['paid_on' => now()->subMonths(2)]);
        }

        $billing->upgrade(Institution::where('category', 'junior')->first(), BandwidthPlan::where('mbps', 20)->first());

        // Last month: two pay, two default
        $billing->generateBills(now()->subMonth());
        Institution::with('bills')->take(2)->get()->each(fn ($i) => $billing->payBill($i->bills->first(), []));
        $billing->processOverdue();
    }
}
