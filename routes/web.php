<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\BacklogController;
use App\Http\Controllers\AdminFirefighterController;
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
    });

});

/*
|--------------------------------------------------------------------------
| Laravel Breeze / Default Auth Routes
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';