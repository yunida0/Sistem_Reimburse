<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReimbursementController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// ── Auth (Breeze) ──────────────────────────────────────────────────────────
require __DIR__ . '/auth.php';

// ── Protected ─────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/', fn() => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile (Breeze)
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [\App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Pengajuan ─────────────────────────────────────────────────────────
    Route::resource('reimbursements', ReimbursementController::class)
        ->except(['destroy']);

    Route::post('/reimbursements/{reimbursement}/submit', [ReimbursementController::class, 'submit'])
        ->name('reimbursements.submit');
    Route::post('/reimbursements/{reimbursement}/cancel', [ReimbursementController::class, 'cancel'])
        ->name('reimbursements.cancel');
    Route::post('/reimbursements/{reimbursement}/comments', [CommentController::class, 'store'])
        ->name('reimbursements.comments.store');

    // ── Lampiran ──────────────────────────────────────────────────────────
    Route::get('/attachments/{attachment}', [AttachmentController::class, 'show'])
        ->name('attachments.show');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])
        ->name('attachments.destroy');

    // ── Approval ──────────────────────────────────────────────────────────
    Route::prefix('approvals')->name('approvals.')->group(function () {
        Route::get('/', [ApprovalController::class, 'index'])->name('index');
        Route::post('/{reimbursement}/approve', [ApprovalController::class, 'approve'])->name('approve');
        Route::post('/{reimbursement}/return', [ApprovalController::class, 'return'])->name('return');
        Route::post('/{reimbursement}/reject', [ApprovalController::class, 'reject'])->name('reject');
    });

    // ── Finance ───────────────────────────────────────────────────────────
    Route::middleware('role:finance')->prefix('finance')->name('finance.')->group(function () {
        Route::get('/verifications', [FinanceController::class, 'index'])->name('index');
        Route::post('/{reimbursement}/verify', [FinanceController::class, 'verify'])->name('verify');
        Route::post('/{reimbursement}/return', [FinanceController::class, 'return'])->name('return');
        Route::post('/{reimbursement}/reject', [FinanceController::class, 'reject'])->name('reject');
    });

    // ── Pembayaran ────────────────────────────────────────────────────────
    Route::middleware('role:cashier')->prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('/{reimbursement}/pay', [PaymentController::class, 'create'])->name('create');
        Route::post('/{reimbursement}/pay', [PaymentController::class, 'store'])->name('store');
    });

    // ── Notifikasi ────────────────────────────────────────────────────────
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
    });

    // ── Laporan ───────────────────────────────────────────────────────────
    Route::middleware('role:finance,admin')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/export', [ReportController::class, 'export'])->name('export');
    });

    // ── Admin ─────────────────────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', Admin\UserController::class)->except(['destroy', 'show']);
        Route::resource('departments', Admin\DepartmentController::class)->except(['destroy', 'show']);
        Route::resource('categories', Admin\CategoryController::class)->except(['destroy', 'show']);
    });

});
