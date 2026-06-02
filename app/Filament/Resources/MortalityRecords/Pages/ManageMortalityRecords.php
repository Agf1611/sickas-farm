<?php

namespace App\Filament\Resources\MortalityRecords\Pages;

use App\Filament\Resources\MortalityRecords\MortalityRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMortalityRecords extends ManageRecords
{
    protected static string $resource = MortalityRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Kejadian'),
        ];
    }
}
