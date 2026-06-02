<?php

namespace Tests\Feature;

use App\Filament\Pages\BatchProfitLossReport;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FatteningBatch;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class BatchProfitLossReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_profit_loss_uses_sales_minus_capital_minus_expenses(): void
    {
        $batch = FatteningBatch::create([
            'start_date' => '2026-05-31',
            'initial_head_count' => 20,
            'current_head_count' => 18,
            'purchase_capital' => 40000000,
            'status' => 'active',
        ]);

        $category = ExpenseCategory::create([
            'code' => 'PAKAN',
            'name' => 'Pakan',
        ]);

        Expense::create([
            'expense_date' => '2026-06-01',
            'expense_category_id' => $category->id,
            'fattening_batch_id' => $batch->id,
            'description' => 'Pakan',
            'amount' => 5000000,
        ]);

        Sale::create([
            'sale_date' => '2026-06-10',
            'sale_type' => 'bulk',
            'fattening_batch_id' => $batch->id,
            'head_count' => 5,
            'total_amount' => 18000000,
        ]);

        $report = app(BatchProfitLossReport::class);

        $this->assertSame(-27000000.0, $this->invokeReportMethod($report, 'getBatchProfitLoss', $batch));
    }

    public function test_period_filter_limits_expenses_and_sales_totals(): void
    {
        $batch = FatteningBatch::create([
            'start_date' => '2026-05-31',
            'initial_head_count' => 20,
            'current_head_count' => 18,
            'purchase_capital' => 40000000,
            'status' => 'active',
        ]);

        $category = ExpenseCategory::create([
            'code' => 'OBAT',
            'name' => 'Obat',
        ]);

        Expense::create([
            'expense_date' => '2026-06-01',
            'expense_category_id' => $category->id,
            'fattening_batch_id' => $batch->id,
            'description' => 'Obat Juni',
            'amount' => 1000000,
        ]);

        Expense::create([
            'expense_date' => '2026-07-01',
            'expense_category_id' => $category->id,
            'fattening_batch_id' => $batch->id,
            'description' => 'Obat Juli',
            'amount' => 3000000,
        ]);

        Sale::create([
            'sale_date' => '2026-06-10',
            'sale_type' => 'bulk',
            'fattening_batch_id' => $batch->id,
            'head_count' => 2,
            'total_amount' => 8000000,
        ]);

        Sale::create([
            'sale_date' => '2026-07-10',
            'sale_type' => 'bulk',
            'fattening_batch_id' => $batch->id,
            'head_count' => 2,
            'total_amount' => 9000000,
        ]);

        $report = app(BatchProfitLossReport::class);
        $report->tableFilters = [
            'period' => [
                'from' => '2026-06-01',
                'until' => '2026-06-30',
            ],
        ];

        $this->assertSame(1000000.0, $this->invokeReportMethod($report, 'getBatchExpenses', $batch));
        $this->assertSame(8000000.0, $this->invokeReportMethod($report, 'getBatchSales', $batch));
        $this->assertSame(-33000000.0, $this->invokeReportMethod($report, 'getBatchProfitLoss', $batch));
    }

    private function invokeReportMethod(BatchProfitLossReport $report, string $method, FatteningBatch $batch): float
    {
        $reflection = new ReflectionMethod($report, $method);

        return $reflection->invoke($report, $batch);
    }
}
