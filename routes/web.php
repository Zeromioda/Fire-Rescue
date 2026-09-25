<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\BacklogController;
use App\Http\Controllers\OtpPasswordResetController;
use App\Http\Controllers\AdminFirefighterController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\PostIncidentController;
use Illuminate\Support\Facades\DB;

Route::get('/health', function () {
    try {
        DB::connection()->getPdo();
        return response()->json(['status' => 'ok']);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 503);
    }
});

/*
|--------------------------------------------------------------------------
| Public & Guest Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route('login');
});

// Custom Password OTP Recovery Routes (Guest Only)
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password-otp', [OtpPasswordResetController::class, 'showRequestForm'])->name('password.otp.request');
    Route::post('/forgot-password-otp', [OtpPasswordResetController::class, 'sendOtp'])->name('password.otp.send');
    Route::get('/verify-otp', [OtpPasswordResetController::class, 'showVerifyForm'])->name('password.otp.verify.form');
    Route::post('/verify-otp', [OtpPasswordResetController::class, 'verifyAndReset'])->middleware('throttle:5,1')->name('password.otp.reset');
    Route::post('/verify-otp/resend', [OtpPasswordResetController::class, 'resend'])->name('password.otp.resend');
});

/*
|--------------------------------------------------------------------------
| Authenticated Responders & Dispatchers
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard & Availability
    Route::get('/dashboard', [IncidentController::class, 'dashboard'])->name('dashboard');
    Route::post('/responder/toggle-availability', [IncidentController::class, 'toggleAvailability'])->name('responder.toggle-availability');

    // Incident Response Operations
    Route::get('/incidents/create', [IncidentController::class, 'create'])->name('incidents.create');
    Route::post('/incidents', [IncidentController::class, 'store'])->name('incidents.store');
    Route::patch('/incidents/{incident}/status', [IncidentController::class, 'updateStatus'])->name('incidents.update-status');
    Route::post('/incidents/{incident}/report', [IncidentController::class, 'submitFinalReport'])->name('incidents.submit-report');

    // Equipment & Fleet Management (View & Status Update for Responders + Admin)
    Route::get('/equipment', [EquipmentController::class, 'index'])->name('equipment.index');
    Route::patch('/equipment/{equipment}', [EquipmentController::class, 'update'])->name('equipment.update');
    Route::patch('/apparatus/{apparatus}', [EquipmentController::class, 'updateApparatus'])->name('apparatus.update');

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    /*
    |--------------------------------------------------------------------------
    | Admin Privileged Command Routes Only
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:Admin'])->group(function () {
        // Fire Incident Reporting
        Route::get('/incidents', [IncidentController::class, 'index'])->name('incidents.index');
        Route::get('/incidents/{incident}', [IncidentController::class, 'show'])->whereNumber('incident')->name('incidents.show');

        // Rescue Operation Dispatch
        Route::get('/dispatch', [DispatchController::class, 'index'])->name('dispatch.index');
        Route::post('/dispatch/{incident}', [DispatchController::class, 'store'])->name('dispatch.store');
        Route::post('/dispatch/{incident}/release/{apparatus}', [DispatchController::class, 'releaseUnit'])->name('dispatch.release');

        // Emergency Response Tracking
        Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking.index');
        Route::post('/tracking/{incident}', [TrackingController::class, 'update'])->name('tracking.update');

        // Post-Incident Reporting
        Route::get('/post-incident', [PostIncidentController::class, 'index'])->name('post-incident.index');
        Route::get('/post-incident/{incident}', [PostIncidentController::class, 'show'])->name('post-incident.show');

        // Equipment & Apparatus Creation and Deletion (Admin Only)
        Route::post('/equipment', [EquipmentController::class, 'store'])->name('equipment.store');
        Route::post('/equipment/apparatus', [EquipmentController::class, 'storeApparatus'])->name('apparatus.store');
        Route::delete('/equipment/{equipment}', [EquipmentController::class, 'destroy'])->name('equipment.destroy');
        Route::delete('/equipment/apparatus/{apparatus}', [EquipmentController::class, 'destroyApparatus'])->name('equipment.apparatus.destroy');
    
        // Manage Personnel
        Route::get('/admin/firefighters', [AdminFirefighterController::class, 'index'])->name('admin.firefighters.index');
        Route::post('/admin/firefighters', [AdminFirefighterController::class, 'store'])->name('admin.firefighters.store');
        Route::put('/admin/firefighters/{user}', [AdminFirefighterController::class, 'update'])->name('admin.firefighters.update');

        // Station Backlog / Access Log
        Route::get('/backlog', [BacklogController::class, 'index'])->name('backlog.index');
        Route::post('/backlog', [BacklogController::class, 'store'])->name('backlog.store');
        Route::put('/backlog/{backlog}', [BacklogController::class, 'update'])->name('backlog.update');

        // AI Analysis & Reporting
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('/reports/generate', [ReportController::class, 'generate'])->middleware('throttle:10,1')->name('reports.generate');
    });

});

/*
|--------------------------------------------------------------------------
| Laravel Breeze / Default Auth Routes
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';