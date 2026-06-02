<?php

namespace App\Filament\Resources\Sheep\Pages;

use App\Filament\Resources\Sheep\SheepResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSheep extends ManageRecords
{
    protected static string $resource = SheepResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Ternak'),
        ];
    }
}
