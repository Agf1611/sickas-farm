<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SheepPurchase;
use App\Services\InvoicePdfService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class InvoicePdfController extends Controller
{
    public function purchase(SheepPurchase $purchase, InvoicePdfService $invoices): Response
    {
        Gate::authorize('view', $purchase);

        return $invoices->downloadPurchase($purchase);
    }

    public function sale(Sale $sale, InvoicePdfService $invoices): Response
    {
        Gate::authorize('view', $sale);

        return $invoices->downloadSale($sale);
    }
}
