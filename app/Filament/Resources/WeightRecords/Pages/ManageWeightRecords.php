<?php

namespace App\Filament\Resources\WeightRecords\Pages;

use App\Filament\Resources\WeightRecords\WeightRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWeightRecords extends ManageRecords
{
    protected static string $resource = WeightRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Timbang'),
        ];
    }
}
