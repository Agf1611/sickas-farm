<?php

namespace App\Filament\Resources\Sheep\Pages;

use App\Filament\Resources\Sheep\SheepResource;
use App\Models\Sheep;
use App\Services\QrCodeService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewSheep extends ViewRecord
{
    protected static string $resource = SheepResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print_qr')
                ->label('Cetak QR')
                ->icon(Heroicon::OutlinedQrCode)
                ->url(fn (): string => app(QrCodeService::class)->sheepPrintUrl($this->getRecord()))
                ->openUrlInNewTab(),
            EditAction::make()->label('Ubah'),
        ];
    }

    public function getTitle(): string
    {
        /** @var Sheep $record */
        $record = $this->getRecord();

        return 'Detail Ternak '.$record->tag_number;
    }
}
