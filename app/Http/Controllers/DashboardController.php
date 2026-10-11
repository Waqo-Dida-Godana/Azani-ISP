<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Payment;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('dashboard', [
            'total'        => Institution::count(),
            'active'       => Institution::where('status', 'active')->count(),
            'defaulters'   => Institution::defaulters()->count(),
            'disconnected' => Institution::where('status', 'disconnected')->count(),
            'revenue'      => Payment::sum('amount'),
            'recent'       => Payment::with('institution')->latest()->take(8)->get(),
        ]);
    }
}
