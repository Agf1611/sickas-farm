<?php

use App\Http\Controllers\QrCodePrintController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\ReportExportController;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([FilamentAuthenticate::class])
    ->prefix('admin/qr-code')
    ->name('sickas-farm.qr.')
    ->group(function (): void {
        Route::get('/batch/{batch}/print', [QrCodePrintController::class, 'batch'])
            ->name('batch.print');
        Route::get('/sheep/{sheep}/print', [QrCodePrintController::class, 'sheep'])
            ->name('sheep.print');
    });

Route::middleware([FilamentAuthenticate::class])
    ->prefix('admin/invoices')
    ->name('sickas-farm.invoices.')
    ->group(function (): void {
        Route::get('/purchase/{purchase}/preview', [InvoicePdfController::class, 'previewPurchase'])
            ->name('purchase.preview');
        Route::get('/purchase/{purchase}/pdf', [InvoicePdfController::class, 'purchase'])
            ->name('purchase.pdf');
        Route::get('/sale/{sale}/preview', [InvoicePdfController::class, 'previewSale'])
            ->name('sale.preview');
        Route::get('/sale/{sale}/pdf', [InvoicePdfController::class, 'sale'])
            ->name('sale.pdf');
    });

Route::middleware([FilamentAuthenticate::class])
    ->prefix('admin/reports/export')
    ->name('sickas-farm.reports.export.')
    ->group(function (): void {
        Route::get('/profit-loss/{format}', [ReportExportController::class, 'profitLoss'])
            ->whereIn('format', ['excel', 'pdf'])
            ->name('profit-loss');
        Route::get('/performance/{format}', [ReportExportController::class, 'performance'])
            ->whereIn('format', ['excel', 'pdf'])
            ->name('performance');
        Route::get('/population/{format}', [ReportExportController::class, 'population'])
            ->whereIn('format', ['excel', 'pdf'])
            ->name('population');
        Route::get('/unit-financial/{format}', [ReportExportController::class, 'unitFinancial'])
            ->whereIn('format', ['excel', 'pdf'])
            ->name('unit-financial');
    });
