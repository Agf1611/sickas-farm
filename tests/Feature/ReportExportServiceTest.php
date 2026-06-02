<?php

namespace Tests\Feature;

use App\Models\Buyer;
use App\Models\BusinessProfile;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FatteningBatch;
use App\Models\Pen;
use App\Models\Sale;
use App\Models\SheepPurchase;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WeighingRecord;
use App\Services\ReportExportService;
use App\Support\SickasFarmPermissions;
use App\Filament\Pages\BatchProfitLossReport;
use App\Filament\Pages\FatteningPerformanceReport;
use App\Filament\Pages\SheepPopulationReport;
use App\Filament\Pages\SheepUnitFinancialReport;
use Database\Seeders\SickasFarmRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ReportExportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_sickas_reports_build_filtered_rows_and_download_files(): void
    {
        $data = $this->createReportData();
        $service = app(ReportExportService::class);

        $reportCalls = [
            'population' => [
                fn (): array => $service->buildPopulationReport([
                    'pen_id' => (string) $data['pen']->id,
                    'from' => '2026-05-01',
                    'until' => '2026-05-31',
                ]),
                fn (): BinaryFileResponse => $service->downloadPopulationExcel(['pen_id' => (string) $data['pen']->id]),
                fn (): Response => $service->downloadPopulationPdf(['pen_id' => (string) $data['pen']->id]),
            ],
            'purchases' => [
                fn (): array => $service->buildPurchasesReport([
                    'from' => '2026-05-01',
                    'until' => '2026-05-31',
                ]),
                fn (): BinaryFileResponse => $service->downloadPurchasesExcel(['from' => '2026-05-01', 'until' => '2026-05-31']),
                fn (): Response => $service->downloadPurchasesPdf(['from' => '2026-05-01', 'until' => '2026-05-31']),
            ],
            'expenses' => [
                fn (): array => $service->buildExpensesReport([
                    'from' => '2026-05-01',
                    'until' => '2026-05-31',
                ]),
                fn (): BinaryFileResponse => $service->downloadExpensesExcel(['from' => '2026-05-01', 'until' => '2026-05-31']),
                fn (): Response => $service->downloadExpensesPdf(['from' => '2026-05-01', 'until' => '2026-05-31']),
            ],
            'sales' => [
                fn (): array => $service->buildSalesReport([
                    'from' => '2026-05-01',
                    'until' => '2026-05-31',
                ]),
                fn (): BinaryFileResponse => $service->downloadSalesExcel(['from' => '2026-05-01', 'until' => '2026-05-31']),
                fn (): Response => $service->downloadSalesPdf(['from' => '2026-05-01', 'until' => '2026-05-31']),
            ],
            'profit_loss' => [
                fn (): array => $service->buildProfitLossReport([
                    'batch_id' => (string) $data['batch']->id,
                    'from' => '2026-05-01',
                    'until' => '2026-05-31',
                ]),
                fn (): BinaryFileResponse => $service->downloadProfitLossExcel(['batch_id' => (string) $data['batch']->id]),
                fn (): Response => $service->downloadProfitLossPdf(['batch_id' => (string) $data['batch']->id]),
            ],
            'performance' => [
                fn (): array => $service->buildPerformanceReport([
                    'batch_id' => (string) $data['batch']->id,
                    'growth_status' => 'Bagus',
                    'from' => '2026-05-01',
                    'until' => '2026-05-31',
                ]),
                fn (): BinaryFileResponse => $service->downloadPerformanceExcel(['batch_id' => (string) $data['batch']->id]),
                fn (): Response => $service->downloadPerformancePdf(['batch_id' => (string) $data['batch']->id]),
            ],
            'unit_financial' => [
                fn (): array => $service->buildUnitFinancialReport([
                    'batch_id' => (string) $data['batch']->id,
                    'pen_id' => (string) $data['pen']->id,
                    'from' => '2026-05-01',
                    'until' => '2026-05-31',
                ]),
                fn (): BinaryFileResponse => $service->downloadUnitFinancialExcel(['batch_id' => (string) $data['batch']->id]),
                fn (): Response => $service->downloadUnitFinancialPdf(['batch_id' => (string) $data['batch']->id]),
            ],
        ];

        foreach ($reportCalls as $name => [$build, $excel, $pdf]) {
            $report = $build();

            $this->assertNotEmpty($report['rows'], "Report {$name} should contain filtered rows.");
            $this->assertNotEmpty($report['summary'], "Report {$name} should contain summary data.");
            $excelResponse = $excel();

            $this->assertStringContainsString('.xlsx', (string) $excelResponse->headers->get('content-disposition'));
            $this->assertStringContainsString('attachment', (string) $excelResponse->headers->get('content-disposition'));
            $this->assertMatchesRegularExpression('/sickas-farm-\d{4}-\d{2}-\d{2}\.xlsx/', (string) $excelResponse->headers->get('content-disposition'));

            $pdfResponse = $pdf();

            $this->assertStringContainsString('.pdf', (string) $pdfResponse->headers->get('content-disposition'));
            $this->assertStringContainsString('attachment', (string) $pdfResponse->headers->get('content-disposition'));
            $this->assertMatchesRegularExpression('/sickas-farm-\d{4}-\d{2}-\d{2}\.pdf/', (string) $pdfResponse->headers->get('content-disposition'));
        }
    }

    public function test_report_page_export_filters_match_active_filter_state(): void
    {
        $batchProfitLoss = app(BatchProfitLossReport::class);
        $batchProfitLoss->batchId = '11';
        $batchProfitLoss->livestockTypeId = '30';
        $batchProfitLoss->periodFrom = '2026-05-01';
        $batchProfitLoss->periodUntil = '2026-05-31';

        $performance = app(FatteningPerformanceReport::class);
        $performance->batchId = '12';
        $performance->penId = '21';
        $performance->livestockTypeId = '31';
        $performance->growthStatus = 'Bagus';
        $performance->startDateFrom = '2026-05-01';
        $performance->startDateUntil = '2026-05-31';

        $population = app(SheepPopulationReport::class);
        $population->penId = '22';
        $population->batchId = '13';
        $population->livestockTypeId = '32';
        $population->sheepStatus = 'active';
        $population->purchaseDateFrom = '2026-05-01';
        $population->purchaseDateUntil = '2026-05-31';

        $financial = app(SheepUnitFinancialReport::class);
        $financial->periodFrom = '2026-05-01';
        $financial->periodUntil = '2026-05-31';
        $financial->batchId = '14';
        $financial->penId = '23';
        $financial->livestockTypeId = '33';

        $this->assertSame([
            'batch_id' => '11',
            'livestock_type_id' => '30',
            'from' => '2026-05-01',
            'until' => '2026-05-31',
        ], $this->exportFilters($batchProfitLoss));

        $this->assertSame([
            'batch_id' => '12',
            'pen_id' => '21',
            'livestock_type_id' => '31',
            'growth_status' => 'Bagus',
            'from' => '2026-05-01',
            'until' => '2026-05-31',
        ], $this->exportFilters($performance));

        $this->assertSame([
            'pen_id' => '22',
            'batch_id' => '13',
            'livestock_type_id' => '32',
            'sheep_status' => 'active',
            'from' => '2026-05-01',
            'until' => '2026-05-31',
        ], $this->exportFilters($population));

        $this->assertSame([
            'from' => '2026-05-01',
            'until' => '2026-05-31',
            'batch_id' => '14',
            'pen_id' => '23',
            'livestock_type_id' => '33',
        ], $this->exportFilters($financial));
    }

    public function test_report_exports_use_business_profile_identity_when_available(): void
    {
        BusinessProfile::create([
            'app_name' => 'SICKAS FARM Ketapang',
            'business_name' => 'Unit Ternak Ketapang',
            'bumdes_name' => 'BUMDes Ketapang Makmur',
            'unit_name' => 'Unit Domba Sejahtera',
        ]);

        $report = app(ReportExportService::class)->buildPopulationReport();

        $this->assertSame('SICKAS FARM Ketapang', $report['app_name']);
        $this->assertSame('Unit Ternak Ketapang', $report['business_title']);
        $this->assertSame('BUMDes Ketapang Makmur', $report['bumdes_name']);
        $this->assertSame('Unit Domba Sejahtera', $report['unit_name']);
    }

    public function test_report_export_routes_download_files_outside_livewire(): void
    {
        $this->seed(SickasFarmRoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(SickasFarmPermissions::SUPER_ADMIN);
        $data = $this->createReportData();

        $routes = [
            'sickas-farm.reports.export.profit-loss' => ['batch_id' => (string) $data['batch']->id],
            'sickas-farm.reports.export.performance' => ['batch_id' => (string) $data['batch']->id],
            'sickas-farm.reports.export.population' => ['batch_id' => (string) $data['batch']->id],
            'sickas-farm.reports.export.unit-financial' => ['batch_id' => (string) $data['batch']->id],
        ];

        foreach ($routes as $route => $filters) {
            foreach (['excel' => '.xlsx', 'pdf' => '.pdf'] as $format => $extension) {
                $response = $this
                    ->actingAs($admin)
                    ->get(route($route, ['format' => $format, ...$filters]));

                $response->assertOk();
                $this->assertStringContainsString($extension, (string) $response->headers->get('content-disposition'));
                $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
            }
        }
    }

    /**
     * @return array{pen: Pen, batch: FatteningBatch}
     */
    private function createReportData(): array
    {
        $pen = Pen::create([
            'code' => 'KDG-EXP-1',
            'name' => 'Kandang Export',
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-EXP-1',
            'name' => 'Supplier Export',
        ]);

        $buyer = Buyer::create([
            'code' => 'BYR-EXP-1',
            'name' => 'Pembeli Export',
        ]);

        $batch = FatteningBatch::create([
            'batch_code' => 'LOT-EXP-001',
            'pen_id' => $pen->id,
            'supplier_id' => $supplier->id,
            'start_date' => '2026-05-01',
            'initial_head_count' => 10,
            'current_head_count' => 8,
            'initial_total_weight_kg' => 200,
            'purchase_capital' => 20000000,
            'status' => 'active',
        ]);

        SheepPurchase::create([
            'purchase_date' => '2026-05-01',
            'supplier_id' => $supplier->id,
            'pen_id' => $pen->id,
            'fattening_batch_id' => $batch->id,
            'purchase_type' => 'bulk',
            'head_count' => 10,
            'total_weight_kg' => 200,
            'total_purchase_price' => 19000000,
            'transport_cost' => 750000,
            'other_cost' => 250000,
        ]);

        $category = ExpenseCategory::create([
            'code' => 'PAKAN-EXP',
            'name' => 'Pakan Export',
        ]);

        Expense::create([
            'expense_date' => '2026-05-10',
            'expense_category_id' => $category->id,
            'fattening_batch_id' => $batch->id,
            'pen_id' => $pen->id,
            'description' => 'Pakan export',
            'amount' => 1500000,
        ]);

        Sale::create([
            'sale_number' => 'INV-EXP-001',
            'sale_date' => '2026-05-20',
            'buyer_id' => $buyer->id,
            'fattening_batch_id' => $batch->id,
            'sale_type' => 'bulk',
            'head_count' => 2,
            'total_weight_kg' => 55,
            'total_amount' => 7000000,
        ]);

        WeighingRecord::create([
            'weighed_at' => '2026-05-11',
            'record_type' => 'batch',
            'fattening_batch_id' => $batch->id,
            'head_count' => 10,
            'total_weight_kg' => 203,
        ]);

        return [
            'pen' => $pen,
            'batch' => $batch,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function exportFilters(object $page): array
    {
        $method = new ReflectionMethod($page, 'exportFilters');

        return $method->invoke($page);
    }
}
