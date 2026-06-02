<?php

namespace App\Filament\Resources\SaleProposals\Pages;

use App\Filament\Resources\SaleProposals\SaleProposalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSaleProposals extends ManageRecords
{
    protected static string $resource = SaleProposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Buat Ajuan Penjualan'),
        ];
    }
}
