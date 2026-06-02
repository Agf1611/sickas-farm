<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SheepPurchase;
use App\Services\InvoicePdfService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class InvoicePdfController extends Controller
{
    public function previewPurchase(SheepPurchase $purchase, InvoicePdfService $invoices): View
    {
        Gate::authorize('view', $purchase);

        return $invoices->previewPurchase($purchase);
    }

    public function purchase(SheepPurchase $purchase, InvoicePdfService $invoices): Response
    {
        Gate::authorize('view', $purchase);

        return $invoices->downloadPurchase($purchase);
    }

    public function previewSale(Sale $sale, InvoicePdfService $invoices): View
    {
        Gate::authorize('view', $sale);

        return $invoices->previewSale($sale);
    }

    public function sale(Sale $sale, InvoicePdfService $invoices): Response
    {
        Gate::authorize('view', $sale);

        return $invoices->downloadSale($sale);
    }
}
