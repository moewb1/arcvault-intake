<?php

use App\Http\Controllers\IntakeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [IntakeController::class, 'index'])->name('intake.index');
Route::post('/intake', [IntakeController::class, 'store'])->name('intake.store');
Route::post('/intake/process-samples', [IntakeController::class, 'processSamples'])->name('intake.samples');
Route::get('/intake/records.json', [IntakeController::class, 'exportJson'])->name('intake.export');
