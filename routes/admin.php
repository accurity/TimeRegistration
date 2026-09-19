<?php

use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ClientUserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InvoiceController;
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
    Route::put('projects/{project}/time-entries/days/{date}', [TimeEntryController::class, 'saveDay'])
        ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
        ->name('projects.time-entries.days.update');
    Route::post('projects/{project}/monthly-approval', [MonthlyApprovalController::class, 'submit'])->name('projects.monthly-approval.submit');
    Route::resource('invoices', InvoiceController::class)->except('show');
    Route::post('invoices/{invoice}/finalize', [InvoiceController::class, 'finalize'])->name('invoices.finalize');
    Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
});
