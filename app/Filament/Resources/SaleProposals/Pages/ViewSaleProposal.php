<?php

namespace App\Filament\Resources\SaleProposals\Pages;

use App\Filament\Resources\SaleProposals\SaleProposalResource;
use App\Models\Buyer;
use App\Models\SaleProposal;
use App\Services\SaleProposalConversionService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ViewSaleProposal extends ViewRecord
{
    protected static string $resource = SaleProposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Setujui Ajuan')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => in_array($this->record->status, ['draft', 'submitted'], true))
                ->action(fn (): mixed => app(SaleProposalConversionService::class)->approve($this->record, Auth::user())),
            Action::make('convert_to_sale')
                ->label('Buat Penjualan')
                ->icon(Heroicon::OutlinedReceiptPercent)
                ->color('info')
                ->visible(fn (): bool => $this->record->status === 'approved' && blank($this->record->sale_id))
                ->schema([
                    DatePicker::make('sale_date')
                        ->label('Tanggal Penjualan')
                        ->native(false)
                        ->default(now())
                        ->required(),
                    Select::make('buyer_id')
                        ->label('Pembeli')
                        ->options(fn (): array => Buyer::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload(),
                    Textarea::make('notes')
                        ->label('Catatan Tambahan')
                        ->rows(3),
                ])
                ->requiresConfirmation()
                ->action(function (array $data): mixed {
                    /** @var SaleProposal $proposal */
                    $proposal = $this->record;
                    $sale = app(SaleProposalConversionService::class)->convertToSale($proposal, $data);

                    return redirect()->to(route('filament.admin.resources.sales.index').'?tableSearch='.$sale->sale_number);
                }),
            EditAction::make()->label('Ubah Ajuan'),
        ];
    }
}
