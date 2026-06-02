<?php

namespace App\Filament\Resources\LivestockTypes\Pages;

use App\Filament\Resources\LivestockTypes\LivestockTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageLivestockTypes extends ManageRecords
{
    protected static string $resource = LivestockTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Jenis Ternak'),
        ];
    }
}
