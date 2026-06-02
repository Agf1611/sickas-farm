<?php

namespace App\Http\Controllers;

use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportExportController extends Controller
{
    public function profitLoss(Request $request, ReportExportService $reports, string $format): Response
    {
        return $this->download(
            $format,
            fn (array $filters): Response => $reports->downloadProfitLossExcel($filters),
            fn (array $filters): Response => $reports->downloadProfitLossPdf($filters),
            $this->filters($request),
        );
    }

    public function performance(Request $request, ReportExportService $reports, string $format): Response
    {
        return $this->download(
            $format,
            fn (array $filters): Response => $reports->downloadPerformanceExcel($filters),
            fn (array $filters): Response => $reports->downloadPerformancePdf($filters),
            $this->filters($request),
        );
    }

    public function population(Request $request, ReportExportService $reports, string $format): Response
    {
        return $this->download(
            $format,
            fn (array $filters): Response => $reports->downloadPopulationExcel($filters),
            fn (array $filters): Response => $reports->downloadPopulationPdf($filters),
            $this->filters($request),
        );
    }

    public function unitFinancial(Request $request, ReportExportService $reports, string $format): Response
    {
        return $this->download(
            $format,
            fn (array $filters): Response => $reports->downloadUnitFinancialExcel($filters),
            fn (array $filters): Response => $reports->downloadUnitFinancialPdf($filters),
            $this->filters($request),
        );
    }

    /**
     * @param  callable(array<string, mixed>): Response  $excel
     * @param  callable(array<string, mixed>): Response  $pdf
     * @param  array<string, mixed>  $filters
     */
    private function download(string $format, callable $excel, callable $pdf, array $filters): Response
    {
        return match ($format) {
            'excel' => $excel($filters),
            'pdf' => $pdf($filters),
            default => abort(404),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return collect($request->only([
            'batch_id',
            'pen_id',
            'livestock_type_id',
            'growth_status',
            'sheep_status',
            'from',
            'until',
        ]))
            ->filter(fn ($value): bool => filled($value))
            ->all();
    }
}
