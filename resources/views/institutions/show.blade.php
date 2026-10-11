@extends('layouts.app')
@section('title', $i->name)
@section('content')
@php $req = $i->infrastructure; @endphp
<h3>{{ $i->name }} <span class="badge bg-secondary fs-6">{{ ucfirst($i->status) }}</span></h3>
<p class="text-muted">{{ \App\Models\Institution::CATEGORIES[$i->category] }} &middot; {{ $i->location }} &middot;
   {{ $i->plan?->mbps }} MBPS &middot; Contact: {{ $i->contact_name }} ({{ $i->contact_phone }}, {{ $i->contact_email }})</p>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="card shadow-sm"><div class="card-body">
      <h5>Site Survey &amp; Infrastructure</h5>
      <form method="POST" action="{{ route('institutions.survey', $i) }}" class="row g-2">
        @csrf
        <div class="col-6"><label class="form-label small">Number of users</label><input type="number" name="number_of_users" value="{{ $req->number_of_users ?? '' }}" class="form-control" required></div>
        <div class="col-6"><label class="form-label small">Computers available</label><input type="number" name="computers_available" value="{{ $req->computers_available ?? 0 }}" class="form-control" required></div>
        <div class="col-6"><label class="form-label small">PCs to buy (@ KSh 40,000)</label><input type="number" name="pcs_to_purchase" value="{{ $req->pcs_to_purchase ?? 0 }}" class="form-control"></div>
        <div class="col-6"><label class="form-label small">LAN nodes to buy (2-100)</label><input type="number" name="lan_nodes_to_purchase" value="{{ $req->lan_nodes_to_purchase ?? 0 }}" class="form-control"></div>
        <div class="col-6"><input type="hidden" name="has_lan" value="0"><label><input type="checkbox" name="has_lan" value="1" @checked($req?->has_lan)> Has LAN</label></div>
        <div class="col-6"><input type="hidden" name="is_ready" value="0"><label><input type="checkbox" name="is_ready" value="1" @checked($req?->is_ready)> Ready for connectivity</label></div>
        <div class="col-12"><textarea name="remarks" class="form-control" placeholder="Remarks">{{ $req->remarks ?? '' }}</textarea></div>
        <div class="col-12"><button class="btn btn-outline-primary btn-sm">Save survey</button></div>
      </form>
      @if($req)
        <table class="table table-sm mt-3 mb-0">
          <tr><td>Installation fee</td><td class="text-end">{{ number_format(config('azani.installation_fee'), 2) }}</td></tr>
          <tr><td>PCs</td><td class="text-end">{{ number_format($req->pc_cost, 2) }}</td></tr>
          <tr><td>LAN</td><td class="text-end">{{ number_format($req->lan_cost, 2) }}</td></tr>
          <tr class="fw-bold"><td>Total installation cost</td><td class="text-end">{{ number_format($req->total_installation_cost, 2) }}</td></tr>
        </table>
        @unless($i->hasPaid('installation'))
          <form method="POST" action="{{ route('payments.installation', $i) }}" class="row g-2 mt-2">
            @csrf
            <div class="col-4"><select name="method" class="form-select form-select-sm">@foreach(['M-Pesa','Bank','Cheque','Cash'] as $m)<option>{{ $m }}</option>@endforeach</select></div>
            <div class="col-4"><input name="reference" class="form-control form-control-sm" placeholder="Reference"></div>
            <div class="col-4"><input type="date" name="paid_on" value="{{ now()->toDateString() }}" class="form-control form-control-sm"></div>
            <div class="col-12"><button class="btn btn-success btn-sm">Capture installation payment</button></div>
          </form>
        @endunless
      @endif
    </div></div>

    @if($i->plan && $plans->count())
    <div class="card shadow-sm mt-4"><div class="card-body">
      <h5>Upgrade Bandwidth (10% discount)</h5>
      <form method="POST" action="{{ route('institutions.upgrade', $i) }}" class="d-flex gap-2">
        @csrf
        <select name="to_plan_id" class="form-select">@foreach($plans as $p)<option value="{{ $p->id }}">{{ $p->mbps }} MBPS — KSh {{ number_format($p->monthly_cost * 0.9) }} after discount</option>@endforeach</select>
        <button class="btn btn-warning">Upgrade</button>
      </form>
    </div></div>
    @endif
  </div>

  <div class="col-lg-6">
    <div class="card shadow-sm"><div class="card-body">
      <h5>Monthly Bills</h5>
      <table class="table table-sm">
        <tr><th>Month</th><th>Amount</th><th>Fine</th><th>Reconn.</th><th>Total</th><th></th></tr>
        @forelse($i->bills->sortByDesc('billing_month') as $b)
          <tr><td>{{ $b->billing_month->format('M Y') }}</td><td>{{ number_format($b->amount) }}</td>
              <td>{{ number_format($b->overdue_fine) }}</td><td>{{ number_format($b->reconnection_fee) }}</td>
              <td class="fw-bold">{{ number_format($b->total_due) }}</td>
              <td>@if($b->is_paid)<span class="badge bg-success">Paid</span>@else
                <form method="POST" action="{{ route('payments.monthly', $b) }}">@csrf
                  <input type="hidden" name="method" value="M-Pesa"><input type="hidden" name="paid_on" value="{{ now()->toDateString() }}">
                  <button class="btn btn-sm btn-success">Pay</button></form>@endif</td></tr>
        @empty <tr><td colspan="6" class="text-muted">No bills yet.</td></tr>
        @endforelse
      </table>
    </div></div>

    <div class="card shadow-sm mt-4"><div class="card-body">
      <h5>Payment History</h5>
      <table class="table table-sm mb-0">
        @foreach($i->payments->sortByDesc('paid_on') as $p)
          <tr><td>{{ $p->paid_on->format('d/m/Y') }}</td><td>{{ \App\Models\Payment::TYPES[$p->type] }}</td><td class="text-end">{{ number_format($p->amount, 2) }}</td></tr>
        @endforeach
        <tr class="fw-bold"><td colspan="2">Total</td><td class="text-end">{{ number_format($i->payments->sum('amount'), 2) }}</td></tr>
      </table>
    </div></div>
  </div>
</div>
@endsection
