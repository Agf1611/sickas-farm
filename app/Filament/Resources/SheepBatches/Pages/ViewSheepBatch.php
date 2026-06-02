<?php

namespace App\Filament\Resources\SheepBatches\Pages;

use App\Filament\Resources\SheepBatches\SheepBatchResource;
use App\Models\FatteningBatch;
use App\Services\BatchOperationalSummaryService;
use App\Services\QrCodeService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ViewSheepBatch extends ViewRecord
{
    protected static string $resource = SheepBatchResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print_qr')
                ->label('Cetak QR')
                ->icon(Heroicon::OutlinedQrCode)
                ->url(fn (): string => app(QrCodeService::class)->batchPrintUrl($this->getRecord()))
                ->openUrlInNewTab(),
            EditAction::make()->label('Ubah'),
        ];
    }

    public function getTitle(): string
    {
        /** @var FatteningBatch $record */
        $record = $this->getRecord();

        return 'Detail Batch '.$record->batch_code;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.resources.sheep-batches.pages.operational-detail')
                ->viewData([
                    'summary' => $this->getOperationalSummary(),
                ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getOperationalSummary(): array
    {
        /** @var FatteningBatch $record */
        $record = $this->getRecord();

        return app(BatchOperationalSummaryService::class)->summarize($record);
    }
}
