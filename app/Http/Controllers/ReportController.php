<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Payment;
use App\Models\Upgrade;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

/**
 * Tasks 3, 4 and 5: listings, computations and printable reports.
 * Every report returns [title, headers, rows, totals] and renders as HTML or PDF.
 */
class ReportController extends Controller
{
    public const REPORTS = [
        'registered'     => 'Registered Institutions',
        'defaulters'     => 'List of Defaulters',
        'disconnection'  => 'Institutions with Disconnection Issues',
        'infrastructure' => 'Infrastructural Requirements per Institution',
        'installation'   => 'Total Installation Cost per Institution',
        'assorted'       => 'Cost of PCs and LAN (Assorted Services)',
        'upgrades'       => 'Monthly Charges for Upgraded Services',
        'category'       => 'Charges, Fines & Re-connection Fees by Category',
        'aggregate'      => 'Aggregate Amount per Service by Institution',
    ];

    public function index() { return view('reports.index', ['reports' => self::REPORTS]); }

    public function show(string $type) { return view('reports.show', $this->build($type) + ['type' => $type]); }

    public function pdf(string $type)
    {
        return Pdf::loadView('reports.pdf', $this->build($type))
            ->setPaper('a4', 'landscape')->download("azani-{$type}-" . now()->format('Ymd') . '.pdf');
    }

    private function k($v): string { return number_format((float) $v, 2); }

    private function build(string $type): array
    {
        abort_unless(isset(self::REPORTS[$type]), 404);
        $title = self::REPORTS[$type];
        $totals = null;

        switch ($type) {
            case 'registered':
                $headers = ['#', 'Institution', 'Category', 'Location', 'Contact Person', 'Phone', 'Email', 'Bandwidth', 'Status', 'Registered'];
                $rows = Institution::with('plan')->orderBy('name')->get()->values()->map(fn ($i, $n) => [
                    $n + 1, $i->name, Institution::CATEGORIES[$i->category], $i->location, $i->contact_name,
                    $i->contact_phone, $i->contact_email, $i->plan?->mbps . ' MBPS', ucfirst($i->status), $i->registered_on->format('d/m/Y')]);
                break;

            case 'defaulters':
                $headers = ['Institution', 'Category', 'Contact', 'Phone', 'Month', 'Amount', 'Fine (15%)', 'Total Due'];
                $rows = []; $sum = 0;
                foreach (Institution::defaulters()->with(['bills' => fn ($b) => $b->where('is_paid', false)])->get() as $i) {
                    foreach ($i->bills as $b) {
                        $rows[] = [$i->name, Institution::CATEGORIES[$i->category], $i->contact_name, $i->contact_phone,
                                   $b->billing_month->format('M Y'), $this->k($b->amount), $this->k($b->overdue_fine), $this->k($b->total_due)];
                        $sum += $b->total_due;
                    }
                }
                $totals = ['', '', '', '', '', '', 'TOTAL', $this->k($sum)];
                break;

            case 'disconnection':
                $headers = ['Institution', 'Contact', 'Phone', 'Status', 'Unpaid Months', 'Amount + Fines', 'Re-connection Fee', 'Total to Reconnect'];
                $rows = Institution::disconnectionIssues()->with(['bills' => fn ($b) => $b->where('is_paid', false)])->get()->map(function ($i) {
                    $amt = $i->bills->sum(fn ($b) => $b->amount + $b->overdue_fine);
                    $re = $i->bills->sum('reconnection_fee') ?: config('azani.reconnection_fee');
                    return [$i->name, $i->contact_name, $i->contact_phone, ucfirst($i->status), $i->bills->count(),
                            $this->k($amt), $this->k($re), $this->k($amt + $re)];
                });
                break;

            case 'infrastructure':
                $headers = ['Institution', 'Users', 'PCs Available', 'Has LAN', 'PCs to Buy', 'LAN Nodes', 'Ready', 'Surveyed', 'Remarks'];
                $rows = Institution::has('infrastructure')->with('infrastructure')->get()->map(fn ($i) => [
                    $i->name, $i->infrastructure->number_of_users, $i->infrastructure->computers_available,
                    $i->infrastructure->has_lan ? 'Yes' : 'No', $i->infrastructure->pcs_to_purchase,
                    $i->infrastructure->lan_nodes_to_purchase, $i->infrastructure->is_ready ? 'Yes' : 'No',
                    $i->infrastructure->surveyed_on->format('d/m/Y'), $i->infrastructure->remarks]);
                break;

            case 'installation':
                $headers = ['Institution', 'Installation Fee', 'PCs Cost', 'LAN Cost', 'Total Installation Cost'];
                $list = Institution::has('infrastructure')->with('infrastructure')->get();
                $rows = $list->map(fn ($i) => [$i->name, $this->k(config('azani.installation_fee')),
                    $this->k($i->infrastructure->pc_cost), $this->k($i->infrastructure->lan_cost), $this->k($i->infrastructure->total_installation_cost)]);
                $totals = ['TOTAL', '', '', '', $this->k($list->sum(fn ($i) => $i->infrastructure->total_installation_cost))];
                break;

            case 'assorted':
                $headers = ['Institution', 'PCs Bought', 'PCs Cost (@40,000)', 'LAN Nodes', 'LAN Cost', 'Total'];
                $list = Institution::whereHas('infrastructure', fn ($q) => $q->where('pcs_to_purchase', '>', 0)->orWhere('lan_nodes_to_purchase', '>', 0))
                    ->with('infrastructure')->get();
                $rows = $list->map(fn ($i) => [$i->name, $i->infrastructure->pcs_to_purchase, $this->k($i->infrastructure->pc_cost),
                    $i->infrastructure->lan_nodes_to_purchase, $this->k($i->infrastructure->lan_cost), $this->k($i->infrastructure->assorted_cost)]);
                $totals = ['TOTAL', '', '', '', '', $this->k($list->sum(fn ($i) => $i->infrastructure->assorted_cost))];
                break;

            case 'upgrades':
                $headers = ['Institution', 'From', 'To', 'Full Cost', 'Discount (10%)', 'Monthly Charge', 'Effective'];
                $list = Upgrade::with('institution', 'fromPlan', 'toPlan')->get();
                $rows = $list->map(fn ($u) => [$u->institution->name, $u->fromPlan->mbps . ' MBPS', $u->toPlan->mbps . ' MBPS',
                    $this->k($u->original_cost), $this->k($u->discount), $this->k($u->discounted_cost), $u->effective_month->format('M Y')]);
                $totals = ['TOTAL', '', '', '', '', $this->k($list->sum('discounted_cost')), ''];
                break;

            case 'category':
                $headers = ['Category', 'Monthly Charges', 'Overdue Fines', 'Re-connection Fees', 'Total'];
                $data = Payment::join('institutions', 'institutions.id', '=', 'payments.institution_id')
                    ->whereIn('type', ['monthly', 'overdue_fine', 'reconnection'])
                    ->groupBy('institutions.category')
                    ->select('institutions.category',
                        DB::raw("SUM(CASE WHEN type='monthly' THEN amount ELSE 0 END) m"),
                        DB::raw("SUM(CASE WHEN type='overdue_fine' THEN amount ELSE 0 END) f"),
                        DB::raw("SUM(CASE WHEN type='reconnection' THEN amount ELSE 0 END) r"))->get()->keyBy('category');
                $rows = collect(Institution::CATEGORIES)->map(function ($label, $key) use ($data) {
                    $d = $data[$key] ?? null;
                    return [$label, $this->k($d->m ?? 0), $this->k($d->f ?? 0), $this->k($d->r ?? 0), $this->k(($d->m ?? 0) + ($d->f ?? 0) + ($d->r ?? 0))];
                })->values();
                $totals = ['TOTAL', $this->k($data->sum('m')), $this->k($data->sum('f')), $this->k($data->sum('r')),
                           $this->k($data->sum('m') + $data->sum('f') + $data->sum('r'))];
                break;

            case 'aggregate':
                $types = array_keys(Payment::TYPES);
                $headers = array_merge(['Institution'], array_values(Payment::TYPES), ['Total']);
                $sums = Payment::select('institution_id', 'type', DB::raw('SUM(amount) total'))
                    ->groupBy('institution_id', 'type')->get()->groupBy('institution_id');
                $rows = Institution::orderBy('name')->get()->map(function ($i) use ($types, $sums) {
                    $s = ($sums[$i->id] ?? collect())->pluck('total', 'type');
                    return array_merge([$i->name], array_map(fn ($t) => $this->k($s[$t] ?? 0), $types), [$this->k($s->sum())]);
                });
                $totals = array_merge(['TOTAL'], array_map(fn ($t) => $this->k(Payment::where('type', $t)->sum('amount')), $types),
                                      [$this->k(Payment::sum('amount'))]);
                break;
        }

        return ['title' => $title, 'headers' => $headers, 'rows' => collect($rows)->values(), 'totals' => $totals];
    }
}
