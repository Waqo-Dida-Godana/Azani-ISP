@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="row g-3 mb-4">
    @foreach(['Registered' => $total, 'Active' => $active, 'Defaulters' => $defaulters, 'Disconnected' => $disconnected,
              'Revenue (KSh)' => number_format($revenue)] as $label => $val)
        <div class="col"><div class="card stat shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ $val }}</div>
        </div></div></div>
    @endforeach
</div>

<div class="card shadow-sm mb-4"><div class="card-body">
    <h5>Monthly Billing</h5>
    <form method="POST" action="{{ route('bills.generate') }}" class="row g-2">
        @csrf
        <div class="col-auto"><input type="month" name="month" class="form-control" value="{{ now()->format('Y-m') }}" required></div>
        <div class="col-auto"><button class="btn btn-primary">Generate bills &amp; process overdue</button></div>
    </form>
</div></div>

<div class="card shadow-sm"><div class="card-body">
    <h5>Recent Payments</h5>
    <table class="table table-sm">
        <tr><th>Date</th><th>Institution</th><th>Type</th><th class="text-end">Amount</th></tr>
        @forelse($recent as $p)
            <tr><td>{{ $p->paid_on->format('d/m/Y') }}</td><td>{{ $p->institution->name }}</td>
                <td>{{ \App\Models\Payment::TYPES[$p->type] }}</td><td class="text-end">{{ number_format($p->amount, 2) }}</td></tr>
        @empty <tr><td colspan="4" class="text-muted">No payments yet.</td></tr>
        @endforelse
    </table>
</div></div>
@endsection
