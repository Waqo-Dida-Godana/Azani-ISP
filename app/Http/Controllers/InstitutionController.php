<?php

namespace App\Http\Controllers;

use App\Models\BandwidthPlan;
use App\Models\Institution;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InstitutionController extends Controller
{
    public function __construct(private BillingService $billing) {}

    public function index(Request $r)
    {
        $institutions = Institution::with('plan')
            ->when($r->q, fn ($q) => $q->where('name', 'like', "%{$r->q}%"))
            ->when($r->category, fn ($q) => $q->where('category', $r->category))
            ->orderBy('name')->paginate(15)->withQueryString();
        return view('institutions.index', compact('institutions'));
    }

    public function create()
    {
        return view('institutions.create', ['plans' => BandwidthPlan::orderBy('mbps')->get()]);
    }

    /** Task 1: register institution + contact person, capture registration fee. */
    public function store(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|max:150',
            'category' => ['required', Rule::in(array_keys(Institution::CATEGORIES))],
            'location' => 'required|string|max:100',
            'postal_address' => 'nullable|string|max:100',
            'contact_name' => 'required|string|max:100',
            'contact_id_number' => 'required|string|max:20',
            'contact_designation' => 'nullable|string|max:60',
            'contact_phone' => ['required', 'regex:/^(\+254|0)[17]\d{8}$/'],
            'contact_email' => 'required|email|max:150',
            'bandwidth_plan_id' => 'required|exists:bandwidth_plans,id',
            'payment_reference' => 'nullable|string|max:50',
        ]);

        $inst = Institution::create(collect($data)->except('payment_reference')->all() + ['registered_on' => now()]);
        $inst->payments()->create([
            'type' => 'registration', 'amount' => config('azani.registration_fee'),
            'reference' => $data['payment_reference'] ?? null, 'paid_on' => now(),
        ]);

        return redirect()->route('institutions.show', $inst)->with('ok', 'Institution registered and registration fee captured.');
    }

    public function show(Institution $institution)
    {
        $institution->load('plan', 'infrastructure', 'payments', 'bills', 'upgrades.fromPlan', 'upgrades.toPlan');
        return view('institutions.show', [
            'i' => $institution,
            'plans' => BandwidthPlan::where('mbps', '>', $institution->plan?->mbps ?? 0)->orderBy('mbps')->get(),
        ]);
    }

    public function survey(Request $r, Institution $institution)
    {
        $data = $r->validate([
            'number_of_users' => 'required|integer|min:1',
            'computers_available' => 'required|integer|min:0',
            'has_lan' => 'boolean',
            'pcs_to_purchase' => 'nullable|integer|min:0',
            'lan_nodes_to_purchase' => ['nullable', 'integer', function ($a, $v, $fail) {
                if ($v && ($v < 2 || $v > 100)) $fail('LAN nodes must be between 2 and 100.');
            }],
            'is_ready' => 'boolean',
            'remarks' => 'nullable|string|max:500',
        ]);
        $this->billing->recordSurvey($institution, $data);
        return back()->with('ok', 'Site survey saved.');
    }

    public function upgrade(Request $r, Institution $institution)
    {
        $r->validate(['to_plan_id' => 'required|exists:bandwidth_plans,id']);
        try {
            $this->billing->upgrade($institution, BandwidthPlan::findOrFail($r->to_plan_id));
        } catch (\DomainException $e) {
            return back()->withErrors($e->getMessage());
        }
        return back()->with('ok', 'Bandwidth upgraded with 10% discount.');
    }
}
