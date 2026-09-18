<?php

use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ClientUserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MonthlyApprovalController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TimeEntryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::resource('clients', ClientController::class)->except('show');
    Route::resource('projects', ProjectController::class)->except('show');
    Route::resource('clients.users', ClientUserController::class)->only(['index', 'create', 'store', 'destroy'])->shallow();
    Route::resource('projects.time-entries', TimeEntryController::class)->except(['show', 'create'])->scoped();
    Route::post('projects/{project}/monthly-approval', [MonthlyApprovalController::class, 'submit'])->name('projects.monthly-approval.submit');
});
