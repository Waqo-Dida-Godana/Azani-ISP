@extends('layouts.app')
@section('title', 'Register Institution')
@section('content')
<h3>Register Institution</h3>
<form method="POST" action="{{ route('institutions.store') }}" class="card shadow-sm"><div class="card-body">
    @csrf
    <h5 class="mb-3">Institution Details</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-6"><label class="form-label">Name</label><input name="name" value="{{ old('name') }}" class="form-control" required></div>
        <div class="col-md-3"><label class="form-label">Category</label><select name="category" class="form-select" required>
            @foreach(\App\Models\Institution::CATEGORIES as $k => $v)<option value="{{ $k }}" @selected(old('category')==$k)>{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Location</label><input name="location" value="{{ old('location') }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Postal Address</label><input name="postal_address" value="{{ old('postal_address') }}" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Requested Bandwidth</label><select name="bandwidth_plan_id" class="form-select" required>
            @foreach($plans as $p)<option value="{{ $p->id }}" @selected(old('bandwidth_plan_id')==$p->id)>{{ $p->label }} / month</option>@endforeach</select></div>
    </div>
    <h5 class="mb-3">Contact Person</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-4"><label class="form-label">Full Name</label><input name="contact_name" value="{{ old('contact_name') }}" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">ID Number</label><input name="contact_id_number" value="{{ old('contact_id_number') }}" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Designation</label><input name="contact_designation" value="{{ old('contact_designation') }}" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Phone (07XXXXXXXX)</label><input name="contact_phone" value="{{ old('contact_phone') }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="contact_email" value="{{ old('contact_email') }}" class="form-control" required></div>
    </div>
    <h5 class="mb-3">Registration Fee: KSh {{ number_format(config('azani.registration_fee')) }}</h5>
    <div class="col-md-4 mb-3"><label class="form-label">Payment Reference (e.g. M-Pesa code)</label><input name="payment_reference" value="{{ old('payment_reference') }}" class="form-control"></div>
    <button class="btn btn-primary">Register &amp; Capture Fee</button>
</div></form>
@endsection
