<?php

use App\Http\Controllers\Portal\ApprovalController;
use App\Http\Controllers\Portal\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:client'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('projects/{project}/{year}/{month}', [ApprovalController::class, 'show'])->name('approvals.show');
    Route::post('projects/{project}/{year}/{month}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
    Route::post('projects/{project}/{year}/{month}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');
});
