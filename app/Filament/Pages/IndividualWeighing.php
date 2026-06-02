<?php

namespace App\Filament\Pages;

use App\Models\FatteningBatch;
use App\Models\Sheep;
use App\Services\GrowthMonitoringService;
use App\Services\IndividualWeighingService;
use App\Support\SickasFormatter;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class IndividualWeighing extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional Ternak';

    protected static ?string $navigationLabel = 'Timbang Per Ekor';

    protected static ?string $title = 'Timbang Per Ekor';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.individual-weighing';

    #[Url]
    public ?int $batchId = null;

    public ?string $weighedAt = null;

    public ?string $notes = null;

    /**
     * @var array<int, mixed>
     */
    public array $weights = [];

    /**
     * @var array<int, ?string>
     */
    public array $itemNotes = [];

    public function mount(): void
    {
        $this->weighedAt = now()->toDateString();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('weighing.manage') ?? false;
    }

    public function updatedBatchId(): void
    {
        $this->weights = [];
        $this->itemNotes = [];
    }

    public function getBatchOptions(): array
    {
        return FatteningBatch::query()
            ->where('status', 'active')
            ->orderByDesc('start_date')
            ->orderBy('batch_code')
            ->pluck('batch_code', 'id')
            ->all();
    }

    public function getSelectedBatch(): ?FatteningBatch
    {
        if (! $this->batchId) {
            return null;
        }

        return FatteningBatch::query()
            ->with(['pen', 'livestockType'])
            ->find($this->batchId);
    }

    public function getActiveSheepRows(): Collection
    {
        if (! $this->batchId) {
            return collect();
        }

        $service = app(GrowthMonitoringService::class);

        return Sheep::query()
            ->with(['livestockType', 'pen'])
            ->where('fattening_batch_id', $this->batchId)
            ->where('status', 'active')
            ->orderBy('tag_number')
            ->get()
            ->map(fn (Sheep $sheep): array => [
                'sheep' => $sheep,
                'growth' => $service->calculateSheepGrowth($sheep),
            ]);
    }

    public function save(IndividualWeighingService $service): void
    {
        $this->validate([
            'batchId' => ['required', 'exists:fattening_batches,id'],
            'weighedAt' => ['required', 'date'],
        ], [
            'batchId.required' => 'Pilih batch terlebih dahulu.',
            'weighedAt.required' => 'Tanggal timbang wajib diisi.',
        ]);

        $batch = FatteningBatch::query()->findOrFail($this->batchId);
        $items = $this->getActiveSheepRows()
            ->map(fn (array $row): array => [
                'sheep_id' => $row['sheep']->id,
                'weight_kg' => $this->weights[$row['sheep']->id] ?? null,
                'notes' => $this->itemNotes[$row['sheep']->id] ?? null,
            ])
            ->all();

        $summary = $service->record($batch, $this->weighedAt, $items, $this->notes);

        $this->weights = [];
        $this->itemNotes = [];
        $this->notes = null;

        Notification::make()
            ->title('Timbang per ekor tersimpan')
            ->body('Tersimpan '.$summary['qty'].' ekor, total '.SickasFormatter::kg($summary['total_weight_kg']).'.')
            ->success()
            ->send();
    }

    public function formatKg(?float $value): string
    {
        return SickasFormatter::kg($value);
    }

    public function formatAdg(?float $value): string
    {
        return SickasFormatter::adg($value);
    }

    public function statusColor(string $status): string
    {
        return app(GrowthMonitoringService::class)->colorForStatus($status);
    }
}
