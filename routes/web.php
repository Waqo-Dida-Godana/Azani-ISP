<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');

Route::resource('institutions', InstitutionController::class)->only(['index', 'create', 'store', 'show']);
Route::post('institutions/{institution}/survey', [InstitutionController::class, 'survey'])->name('institutions.survey');
Route::post('institutions/{institution}/upgrade', [InstitutionController::class, 'upgrade'])->name('institutions.upgrade');

Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
Route::post('institutions/{institution}/pay-installation', [PaymentController::class, 'installation'])->name('payments.installation');
Route::post('bills/{bill}/pay', [PaymentController::class, 'monthly'])->name('payments.monthly');
Route::post('bills/generate', [PaymentController::class, 'generateBills'])->name('bills.generate');

Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('reports/{type}', [ReportController::class, 'show'])->name('reports.show');
Route::get('reports/{type}/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
