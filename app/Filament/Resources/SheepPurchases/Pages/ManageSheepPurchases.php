<?php

namespace App\Filament\Resources\SheepPurchases\Pages;

use App\Filament\Resources\SheepPurchases\SheepPurchaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSheepPurchases extends ManageRecords
{
    protected static string $resource = SheepPurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Pembelian'),
        ];
    }
}
