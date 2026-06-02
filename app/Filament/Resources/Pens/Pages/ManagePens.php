<?php

namespace App\Filament\Resources\Pens\Pages;

use App\Filament\Resources\Pens\PenResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePens extends ManageRecords
{
    protected static string $resource = PenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Kandang'),
        ];
    }
}
