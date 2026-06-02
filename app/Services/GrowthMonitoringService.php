<?php

namespace App\Services;

use App\Models\FatteningBatch;
use App\Models\Sheep;
use App\Models\WeighingRecord;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class GrowthMonitoringService
{
    private const REWEIGHING_LIMIT_DAYS = 14;

    private const DEFAULT_TARGET_SALE_AVERAGE_WEIGHT_KG = 30.0;

    public function calculateBatchGrowth(FatteningBatch $batch): array
    {
        $latestWeighing = $batch->weighingRecords()
            ->where('record_type', 'batch')
            ->whereNotNull('total_weight_kg')
            ->latest('weighed_at')
            ->latest('id')
            ->first();

        $headCount = $latestWeighing?->head_count ?: $batch->current_head_count;
        $initialWeight = $batch->initial_total_weight_kg !== null ? (float) $batch->initial_total_weight_kg : null;
        $latestWeight = $latestWeighing?->total_weight_kg !== null ? (float) $latestWeighing->total_weight_kg : null;
        $initialAverageWeight = $this->calculateAverageWeight($initialWeight, $batch->initial_head_count);
        $latestAverageWeight = $this->calculateAverageWeight($latestWeight, $headCount);
        $days = $this->calculateDays($batch->start_date, $latestWeighing?->weighed_at);
        $gain = $this->calculateGain($initialWeight, $latestWeight);
        $adg = $this->calculateAdg($gain, $days);
        $status = $this->determineStatus($latestWeighing, $adg);
        $weighingAlert = $this->determineWeighingAlert($batch->status === 'active', $latestWeighing?->weighed_at);
        $targetSaleAverageWeight = $this->targetSaleAverageWeight($batch);
        $sellingIndicator = $this->determineSellingIndicator($status, $latestAverageWeight, $targetSaleAverageWeight);

        return [
            'subject_type' => 'batch',
            'subject_label' => $batch->batch_code,
            'initial_weight' => $initialWeight,
            'latest_weight' => $latestWeight,
            'weight_gain' => $gain,
            'initial_average_weight' => $initialAverageWeight,
            'latest_average_weight' => $latestAverageWeight,
            'average_weight_gain' => $this->calculateGain($initialAverageWeight, $latestAverageWeight),
            'days' => $days,
            'adg' => $adg,
            'status' => $status,
            'recommendation' => $this->recommendationFor($status),
            'latest_weighed_at' => $latestWeighing?->weighed_at,
            'weighing_alert_status' => $weighingAlert['status'],
            'weighing_alert_description' => $weighingAlert['description'],
            'days_since_last_weighing' => $weighingAlert['days_since_last_weighing'],
            'target_sale_average_weight' => $targetSaleAverageWeight,
            'selling_indicator' => $sellingIndicator['status'],
            'selling_indicator_description' => $sellingIndicator['description'],
            'head_count' => $headCount,
            'average_weight' => $latestAverageWeight,
        ];
    }

    public function calculateSheepGrowth(Sheep $sheep): array
    {
        $latestWeighing = $sheep->weighingRecords()
            ->where('record_type', 'individual')
            ->whereNotNull('weight_kg')
            ->latest('weighed_at')
            ->latest('id')
            ->first();

        $initialWeight = $sheep->initial_weight_kg !== null ? (float) $sheep->initial_weight_kg : null;
        $latestWeight = $latestWeighing?->weight_kg !== null ? (float) $latestWeighing->weight_kg : null;
        $days = $this->calculateDays($sheep->fatteningBatch?->start_date, $latestWeighing?->weighed_at);
        $gain = $this->calculateGain($initialWeight, $latestWeight);
        $adg = $this->calculateAdg($gain, $days);
        $status = $this->determineStatus($latestWeighing, $adg);
        $weighingAlert = $this->determineWeighingAlert($sheep->status === 'active', $latestWeighing?->weighed_at);

        return [
            'subject_type' => 'individual',
            'subject_label' => $sheep->tag_number,
            'initial_weight' => $initialWeight,
            'latest_weight' => $latestWeight,
            'weight_gain' => $gain,
            'days' => $days,
            'adg' => $adg,
            'status' => $status,
            'recommendation' => $this->recommendationFor($status),
            'latest_weighed_at' => $latestWeighing?->weighed_at,
            'weighing_alert_status' => $weighingAlert['status'],
            'weighing_alert_description' => $weighingAlert['description'],
            'days_since_last_weighing' => $weighingAlert['days_since_last_weighing'],
            'head_count' => 1,
            'average_weight' => $latestWeight,
        ];
    }

    public function countBatchNeverWeighed(): int
    {
        return FatteningBatch::query()
            ->where('status', 'active')
            ->whereDoesntHave('weighingRecords', fn ($query): mixed => $query
                ->where('record_type', 'batch')
                ->whereNotNull('total_weight_kg'))
            ->count();
    }

    public function countBatchNeedsReweighing(): int
    {
        return FatteningBatch::query()
            ->where('status', 'active')
            ->whereHas('weighingRecords', fn ($query): mixed => $query
                ->where('record_type', 'batch')
                ->whereNotNull('total_weight_kg'))
            ->whereDoesntHave('weighingRecords', fn ($query): mixed => $query
                ->where('record_type', 'batch')
                ->whereNotNull('total_weight_kg')
                ->whereDate('weighed_at', '>=', $this->recentWeighingThresholdDate()))
            ->count();
    }

    public function countSheepNeverWeighed(): int
    {
        return Sheep::query()
            ->where('status', 'active')
            ->whereDoesntHave('weighingRecords', fn ($query): mixed => $query
                ->where('record_type', 'individual')
                ->whereNotNull('weight_kg'))
            ->count();
    }

    public function countSheepNeedsReweighing(): int
    {
        return Sheep::query()
            ->where('status', 'active')
            ->whereHas('weighingRecords', fn ($query): mixed => $query
                ->where('record_type', 'individual')
                ->whereNotNull('weight_kg'))
            ->whereDoesntHave('weighingRecords', fn ($query): mixed => $query
                ->where('record_type', 'individual')
                ->whereNotNull('weight_kg')
                ->whereDate('weighed_at', '>=', $this->recentWeighingThresholdDate()))
            ->count();
    }

    public function activeBatchGrowthRows(): Collection
    {
        return FatteningBatch::query()
            ->with(['pen'])
            ->where('status', 'active')
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (FatteningBatch $batch): array => [
                'batch' => $batch,
                'batch_code' => $batch->batch_code,
                'pen_name' => $batch->pen?->name ?? 'Tanpa kandang',
                'start_date' => $batch->start_date,
                ...$this->calculateBatchGrowth($batch),
            ]);
    }

    public function activeBatchGrowthSummary(): array
    {
        $rows = $this->activeBatchGrowthRows();
        $rowsWithAdg = $rows->whereNotNull('adg');

        return [
            'average_adg' => $rowsWithAdg->avg('adg'),
            'total_weight_gain' => $rows->sum(fn (array $row): float => (float) ($row['weight_gain'] ?? 0)),
            'good_count' => $rows->where('status', 'Bagus')->count(),
            'slow_count' => $rows->where('status', 'Lambat')->count(),
            'stagnant_count' => $rows->where('status', 'Stagnan')->count(),
            'down_count' => $rows->where('status', 'Turun')->count(),
            'needs_reweighing_count' => $rows->where('weighing_alert_status', 'Perlu Timbang Ulang')->count(),
        ];
    }

    public function activeBatchGrowthWarnings(int $limitPerGroup = 5): array
    {
        $rows = $this->activeBatchGrowthRows();

        return [
            'down' => $rows
                ->where('status', 'Turun')
                ->take($limitPerGroup)
                ->values()
                ->all(),
            'slow' => $rows
                ->where('status', 'Lambat')
                ->take($limitPerGroup)
                ->values()
                ->all(),
            'not_weighed_overdue' => $rows
                ->filter(fn (array $row): bool => $row['weighing_alert_status'] === 'Belum Ditimbang'
                    && $this->isStartDateOlderThanReweighingLimit($row['start_date']))
                ->take($limitPerGroup)
                ->values()
                ->all(),
        ];
    }

    public function colorForStatus(string $status): string
    {
        return match ($status) {
            'Bagus' => 'success',
            'Lambat' => 'warning',
            'Stagnan' => 'gray',
            'Turun' => 'danger',
            default => 'gray',
        };
    }

    public function colorForWeighingAlert(string $status): string
    {
        return match ($status) {
            'Perlu Timbang Ulang' => 'warning',
            'Belum Ditimbang' => 'danger',
            'Timbang Terkini' => 'success',
            default => 'gray',
        };
    }

    public function colorForSellingIndicator(string $status): string
    {
        return match ($status) {
            'Siap Dipertimbangkan untuk Dijual' => 'success',
            'Lanjutkan Penggemukan' => 'info',
            'Evaluasi Pakan dan Perawatan' => 'warning',
            'Jangan Dijual Normal, Periksa Kesehatan' => 'danger',
            default => 'gray',
        };
    }

    public function recommendationFor(string $status): string
    {
        return match ($status) {
            'Bagus' => 'Pertumbuhan bagus. Lanjutkan pola pakan dan perawatan saat ini.',
            'Lambat' => 'Pertumbuhan lambat. Cek kualitas pakan, jadwal pemberian pakan, dan kondisi kandang.',
            'Stagnan' => 'Berat hampir tidak naik. Periksa nafsu makan, kesehatan, dan kemungkinan kurang pakan.',
            'Turun' => 'Berat turun. Segera periksa kesehatan ternak dan pisahkan jika perlu.',
            default => 'Segera lakukan timbang agar pertumbuhan bisa dipantau.',
        };
    }

    private function calculateDays(?CarbonInterface $startDate, ?CarbonInterface $weighedAt): ?int
    {
        if (! $startDate || ! $weighedAt) {
            return null;
        }

        $start = CarbonImmutable::parse($startDate->toDateString());
        $end = CarbonImmutable::parse($weighedAt->toDateString());

        return max(1, (int) $start->diffInDays($end, absolute: false));
    }

    private function calculateGain(?float $initialWeight, ?float $latestWeight): ?float
    {
        if ($initialWeight === null || $latestWeight === null) {
            return null;
        }

        return $latestWeight - $initialWeight;
    }

    private function calculateAdg(?float $gain, ?int $days): ?float
    {
        if ($gain === null || ! $days) {
            return null;
        }

        return $gain / $days;
    }

    private function calculateAverageWeight(?float $totalWeight, ?int $headCount): ?float
    {
        if ($totalWeight === null || ! $headCount) {
            return null;
        }

        return $totalWeight / $headCount;
    }

    private function determineStatus(?WeighingRecord $latestWeighing, ?float $adg): string
    {
        if (! $latestWeighing || $adg === null) {
            return 'Belum Ditimbang';
        }

        return match (true) {
            $adg >= 0.15 => 'Bagus',
            $adg >= 0.05 => 'Lambat',
            $adg >= 0 => 'Stagnan',
            default => 'Turun',
        };
    }

    private function determineWeighingAlert(bool $isActive, ?CarbonInterface $latestWeighedAt): array
    {
        if (! $isActive) {
            return [
                'status' => 'Tidak Aktif',
                'description' => 'Tidak perlu peringatan timbang untuk data tidak aktif.',
                'days_since_last_weighing' => null,
            ];
        }

        if (! $latestWeighedAt) {
            return [
                'status' => 'Belum Ditimbang',
                'description' => 'Segera lakukan timbang awal.',
                'days_since_last_weighing' => null,
            ];
        }

        $daysSinceLastWeighing = $this->calculateDays($latestWeighedAt, now());

        if ($daysSinceLastWeighing !== null && $daysSinceLastWeighing > self::REWEIGHING_LIMIT_DAYS) {
            return [
                'status' => 'Perlu Timbang Ulang',
                'description' => 'Timbang terakhir sudah lebih dari 14 hari.',
                'days_since_last_weighing' => $daysSinceLastWeighing,
            ];
        }

        return [
            'status' => 'Timbang Terkini',
            'description' => 'Timbang terakhir masih dalam 14 hari.',
            'days_since_last_weighing' => $daysSinceLastWeighing,
        ];
    }

    private function recentWeighingThresholdDate(): string
    {
        return now()->subDays(self::REWEIGHING_LIMIT_DAYS)->toDateString();
    }

    private function targetSaleAverageWeight(FatteningBatch $batch): float
    {
        return $batch->target_sale_average_weight_kg !== null
            ? (float) $batch->target_sale_average_weight_kg
            : self::DEFAULT_TARGET_SALE_AVERAGE_WEIGHT_KG;
    }

    private function determineSellingIndicator(string $growthStatus, ?float $latestAverageWeight, float $targetSaleAverageWeight): array
    {
        if ($growthStatus === 'Bagus' && $latestAverageWeight !== null && $latestAverageWeight >= $targetSaleAverageWeight) {
            return [
                'status' => 'Siap Dipertimbangkan untuk Dijual',
                'description' => 'ADG bagus dan berat rata-rata sudah mencapai target.',
            ];
        }

        if ($growthStatus === 'Bagus') {
            return [
                'status' => 'Lanjutkan Penggemukan',
                'description' => 'ADG bagus, tetapi berat rata-rata belum mencapai target.',
            ];
        }

        if (in_array($growthStatus, ['Lambat', 'Stagnan', 'Belum Ditimbang'], true)) {
            return [
                'status' => 'Evaluasi Pakan dan Perawatan',
                'description' => 'Periksa pakan, kandang, kesehatan, dan jadwal timbang sebelum keputusan jual.',
            ];
        }

        if ($growthStatus === 'Turun') {
            return [
                'status' => 'Jangan Dijual Normal, Periksa Kesehatan',
                'description' => 'Berat turun, cek kesehatan dan perlakuan khusus sebelum penjualan.',
            ];
        }

        return [
            'status' => 'Evaluasi Pakan dan Perawatan',
            'description' => 'Data performa belum cukup untuk keputusan jual.',
        ];
    }

    private function isStartDateOlderThanReweighingLimit(?CarbonInterface $startDate): bool
    {
        if (! $startDate) {
            return false;
        }

        return $this->calculateDays($startDate, now()) > self::REWEIGHING_LIMIT_DAYS;
    }
}
