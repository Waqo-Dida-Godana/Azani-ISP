<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\MonthlyBill;
use App\Models\Payment;
use App\Services\BillingService;
use Illuminate\Http\Request;

/** Task 2: capture registration, installation and monthly payments. */
class PaymentController extends Controller
{
    public function __construct(private BillingService $billing) {}

    public function index()
    {
        $payments = Payment::with('institution')->latest('paid_on')->paginate(20);
        return view('payments.index', compact('payments'));
    }

    private function rules(): array
    {
        return ['method' => 'required|in:M-Pesa,Bank,Cheque,Cash', 'reference' => 'nullable|string|max:50',
                'paid_on' => 'required|date|before_or_equal:today'];
    }

    public function installation(Request $r, Institution $institution)
    {
        $data = $r->validate($this->rules());
        if ($institution->hasPaid('installation')) return back()->withErrors('Installation already paid.');
        try {
            $this->billing->payInstallation($institution, $data);
        } catch (\DomainException $e) {
            return back()->withErrors($e->getMessage());
        }
        return back()->with('ok', 'Installation payment captured. Institution is now active.');
    }

    public function monthly(Request $r, MonthlyBill $bill)
    {
        $data = $r->validate($this->rules());
        if ($bill->is_paid) return back()->withErrors('Bill already paid.');
        $this->billing->payBill($bill, $data);
        return back()->with('ok', 'Monthly payment captured.');
    }

    public function generateBills(Request $r)
    {
        $r->validate(['month' => 'required|date_format:Y-m']);
        $n = $this->billing->generateBills(\Carbon\Carbon::createFromFormat('Y-m', $r->month));
        $o = $this->billing->processOverdue();
        return back()->with('ok', "{$n} bills generated; {$o['fined']} fined; {$o['disconnected']} disconnected.");
    }
}
