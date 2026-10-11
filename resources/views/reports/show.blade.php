@extends('layouts.app')
@section('title', $title)
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h3>{{ $title }}</h3>
    <a href="{{ route('reports.pdf', $type) }}" class="btn btn-danger">Download PDF</a>
</div>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-sm table-striped mb-0">
    <thead class="table-dark"><tr>@foreach($headers as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
    @forelse($rows as $r)<tr>@foreach($r as $c)<td>{{ $c }}</td>@endforeach</tr>
    @empty <tr><td colspan="{{ count($headers) }}" class="text-muted">No records.</td></tr>
    @endforelse
    @if($totals)<tr class="fw-bold table-secondary">@foreach($totals as $c)<td>{{ $c }}</td>@endforeach</tr>@endif
</table></div></div>
@endsection
