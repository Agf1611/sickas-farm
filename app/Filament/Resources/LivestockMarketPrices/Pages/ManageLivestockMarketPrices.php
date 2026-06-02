<?php

namespace App\Filament\Resources\LivestockMarketPrices\Pages;

use App\Filament\Resources\LivestockMarketPrices\LivestockMarketPriceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageLivestockMarketPrices extends ManageRecords
{
    protected static string $resource = LivestockMarketPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Harga Pasaran'),
        ];
    }
}
