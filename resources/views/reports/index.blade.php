@extends('layouts.app')
@section('title', 'Reports')
@section('content')
<h3>Reports</h3>
<div class="list-group shadow-sm">
    @foreach($reports as $key => $label)
        <div class="list-group-item d-flex justify-content-between align-items-center">
            <a href="{{ route('reports.show', $key) }}">{{ $label }}</a>
            <a href="{{ route('reports.pdf', $key) }}" class="btn btn-sm btn-outline-danger">PDF</a>
        </div>
    @endforeach
</div>
@endsection
