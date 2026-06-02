<?php

namespace App\Filament\Resources\SheepBatches\Pages;

use App\Filament\Resources\SheepBatches\SheepBatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSheepBatches extends ManageRecords
{
    protected static string $resource = SheepBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Batch'),
        ];
    }
}
