@extends('layouts.app')
@section('title', 'Institutions')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h3>Institutions</h3>
    <a href="{{ route('institutions.create') }}" class="btn btn-primary">+ Register Institution</a>
</div>
<form class="row g-2 mb-3">
    <div class="col-md-5"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Search by name"></div>
    <div class="col-md-3"><select name="category" class="form-select"><option value="">All categories</option>
        @foreach(\App\Models\Institution::CATEGORIES as $k => $v)<option value="{{ $k }}" @selected(request('category')==$k)>{{ $v }}</option>@endforeach
    </select></div>
    <div class="col-auto"><button class="btn btn-outline-secondary">Filter</button></div>
</form>
<div class="card shadow-sm"><table class="table table-hover mb-0">
    <thead><tr><th>Name</th><th>Category</th><th>Location</th><th>Contact</th><th>Bandwidth</th><th>Status</th></tr></thead>
    @foreach($institutions as $i)
        <tr>
            <td><a href="{{ route('institutions.show', $i) }}">{{ $i->name }}</a></td>
            <td>{{ \App\Models\Institution::CATEGORIES[$i->category] }}</td>
            <td>{{ $i->location }}</td><td>{{ $i->contact_name }}<br><small>{{ $i->contact_phone }}</small></td>
            <td>{{ $i->plan?->mbps }} MBPS</td>
            <td><span class="badge bg-{{ ['active'=>'success','disconnected'=>'danger','surveyed'=>'info','registered'=>'secondary'][$i->status] }}">{{ ucfirst($i->status) }}</span></td>
        </tr>
    @endforeach
</table></div>
<div class="mt-3">{{ $institutions->links() }}</div>
@endsection
