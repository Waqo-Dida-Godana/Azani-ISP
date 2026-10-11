@extends('layouts.app')
@section('title', 'Payments')
@section('content')
<h3>All Payments</h3>
<div class="card shadow-sm"><table class="table table-sm mb-0">
    <tr><th>Date</th><th>Institution</th><th>Type</th><th>Method</th><th>Reference</th><th class="text-end">Amount (KSh)</th></tr>
    @foreach($payments as $p)
        <tr><td>{{ $p->paid_on->format('d/m/Y') }}</td><td>{{ $p->institution->name }}</td><td>{{ \App\Models\Payment::TYPES[$p->type] }}</td>
            <td>{{ $p->method }}</td><td>{{ $p->reference }}</td><td class="text-end">{{ number_format($p->amount, 2) }}</td></tr>
    @endforeach
</table></div>
<div class="mt-3">{{ $payments->links() }}</div>
@endsection
