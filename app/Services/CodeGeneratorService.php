<?php

namespace App\Services;

use App\Models\FatteningBatch;
use App\Models\Sale;
use App\Models\SaleProposal;
use App\Models\Sheep;
use App\Models\SheepPurchase;
use Illuminate\Database\Eloquent\Model;

class CodeGeneratorService
{
    public function generateBatchCode(?int $year = null): string
    {
        return $this->generateCode('LOT', FatteningBatch::class, 'batch_code', 3, $year);
    }

    public function generateSheepCode(?int $year = null, ?string $prefix = null): string
    {
        $prefix = filled($prefix) ? strtoupper($prefix) : 'DMB';

        return $this->generateCode($prefix, Sheep::class, 'tag_number', 4, $year);
    }

    public function generateSaleInvoiceNumber(?int $year = null): string
    {
        return $this->generateCode('INV-JUAL', Sale::class, 'sale_number', 3, $year);
    }

    public function generatePurchaseInvoiceNumber(?int $year = null): string
    {
        return $this->generateCode('INV-BELI', SheepPurchase::class, 'purchase_number', 3, $year);
    }

    public function generateSaleProposalNumber(?int $year = null): string
    {
        return $this->generateCode('AJ-JUAL', SaleProposal::class, 'proposal_number', 3, $year);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function generateCode(string $prefix, string $modelClass, string $column, int $padding, ?int $year = null): string
    {
        $year ??= now()->year;
        $base = "{$prefix}-{$year}-";
        $latestCode = $modelClass::query()
            ->where($column, 'like', "{$base}%")
            ->orderByDesc($column)
            ->value($column);

        $nextNumber = $latestCode
            ? ((int) str($latestCode)->afterLast('-')->toString()) + 1
            : 1;

        do {
            $code = $base.str_pad((string) $nextNumber, $padding, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while ($modelClass::query()->where($column, $code)->exists());

        return $code;
    }
}
