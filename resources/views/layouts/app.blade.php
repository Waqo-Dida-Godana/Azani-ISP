<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Azani ISP') | Azani Information System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#f4f6f9; }
        .navbar { background:#0b3d5c; }
        .stat { border:0; border-left:4px solid #0b3d5c; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">AZANI ISP</a>
        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('institutions.index') }}">Institutions</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('institutions.create') }}">Register</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('payments.index') }}">Payments</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('reports.index') }}">Reports</a></li>
            </ul>
        </div>
    </div>
</nav>
<main class="container pb-5">
    @if(session('ok'))<div class="alert alert-success">{{ session('ok') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    @yield('content')
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
