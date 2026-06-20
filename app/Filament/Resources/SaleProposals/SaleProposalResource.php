<?php

namespace App\Filament\Resources\SaleProposals;

use App\Filament\Resources\SaleProposals\Pages\ManageSaleProposals;
use App\Filament\Resources\SaleProposals\Pages\ViewSaleProposal;
use App\Filament\Resources\SaleProposals\RelationManagers\ItemsRelationManager;
use App\Models\Buyer;
use App\Models\SaleProposal;
use App\Services\SaleProposalConversionService;
use App\Support\SickasFormatter;
use Filament\Actions\Action;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SaleProposalResource extends Resource
{
    protected static ?string $model = SaleProposal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Ajuan Penjualan';

    protected static ?string $modelLabel = 'Ajuan Penjualan';

    protected static ?string $pluralModelLabel = 'Ajuan Penjualan';

    protected static ?string $recordTitleAttribute = 'proposal_number';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Ajuan')
                ->schema([
                    TextInput::make('proposal_number')
                        ->label('Nomor Ajuan')
                        ->placeholder('Otomatis saat disimpan')
                        ->disabled()
                        ->dehydrated(false),
                    DatePicker::make('proposed_date')
                        ->label('Tanggal Ajuan')
                        ->native(false)
                        ->default(now())
                        ->required(),
                    Select::make('fattening_batch_id')
                        ->label('Batch')
                        ->relationship('fatteningBatch', 'batch_code')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Estimasi diambil dari harga pasaran aktif dan data berat terakhir batch.'),
                    Select::make('proposal_type')
                        ->label('Tipe Ajuan')
                        ->options([
                            'batch' => 'Satu Batch',
                            'selected_livestock' => 'Ternak Terpilih',
                        ])
                        ->default('batch')
                        ->required(),
                    Select::make('status')
                        ->label('Status')
                        ->options(self::statusOptions())
                        ->default('draft')
                        ->required(),
                ])
                ->columns(2),
            Section::make('Detail Tambahan')
                ->schema([
                    Textarea::make('notes')
                        ->label('Catatan')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),
            Section::make('Estimasi Sistem')
                ->schema([
                    TextInput::make('head_count')
                        ->label('Jumlah Ternak')
                        ->numeric()
                        ->minValue(0)
                        ->helperText('Boleh kosong, sistem memakai stok aktif batch.'),
                    TextInput::make('estimated_total_weight_kg')
                        ->label('Estimasi Total Berat')
                        ->numeric()
                        ->suffix('kg'),
                    TextInput::make('estimated_unit_price')
                        ->label('Harga Pasar Dipakai')
                        ->numeric()
                        ->prefix('Rp'),
                    TextInput::make('estimated_total_amount')
                        ->label('Estimasi Nilai Jual')
                        ->numeric()
                        ->prefix('Rp'),
                    TextInput::make('estimated_profit_loss')
                        ->label('Estimasi Laba / Rugi')
                        ->numeric()
                        ->prefix('Rp'),
                    Placeholder::make('calculation_note')
                        ->label('Catatan Perhitungan')
                        ->content('Jika nilai dikosongkan, sistem akan menghitung otomatis saat ajuan disimpan.'),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('proposal_number')->label('Nomor')->searchable()->sortable(),
                TextColumn::make('proposed_date')->label('Tanggal')->formatStateUsing(fn ($state): string => SickasFormatter::date($state))->sortable(),
                TextColumn::make('fatteningBatch.batch_code')->label('Batch')->searchable(),
                TextColumn::make('livestockType.name')->label('Jenis')->badge()->color('info')->visibleFrom('md'),
                TextColumn::make('head_count')->label('Jumlah')->formatStateUsing(fn ($state): string => SickasFormatter::number($state).' ekor'),
                TextColumn::make('estimated_total_weight_kg')->label('Berat')->formatStateUsing(fn ($state): string => SickasFormatter::kg($state))->visibleFrom('md'),
                TextColumn::make('estimated_total_amount')->label('Estimasi Jual')->formatStateUsing(fn ($state): string => SickasFormatter::rupiah($state))->alignEnd(),
                TextColumn::make('estimated_profit_loss')
                    ->label('Estimasi L/R')
                    ->formatStateUsing(fn ($state): string => SickasFormatter::rupiah($state))
                    ->color(fn ($state): string => (float) $state >= 0 ? 'success' : 'danger')
                    ->alignEnd()
                    ->visibleFrom('md'),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (?string $state): string => self::statusOptions()[$state] ?? '-')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'warning',
                        'approved' => 'success',
                        'rejected', 'cancelled' => 'danger',
                        'converted_to_sale' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('sale.sale_number')
                    ->label('Invoice Penjualan')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(self::statusOptions()),
                SelectFilter::make('livestock_type_id')->label('Jenis Ternak')->relationship('livestockType', 'name'),
            ])
            ->recordActions([
                ViewAction::make()->label('Detail'),
                Action::make('approve')
                    ->label('Setujui')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SaleProposal $record): bool => in_array($record->status, ['draft', 'submitted'], true))
                    ->action(fn (SaleProposal $record): mixed => app(SaleProposalConversionService::class)->approve($record, Auth::user())),
                Action::make('convert_to_sale')
                    ->label('Buat Penjualan')
                    ->icon(Heroicon::OutlinedReceiptPercent)
                    ->color('info')
                    ->visible(fn (SaleProposal $record): bool => $record->status === 'approved' && blank($record->sale_id))
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
                    ->action(function (SaleProposal $record, array $data): mixed {
                        $sale = app(SaleProposalConversionService::class)->convertToSale($record, $data);

                        return redirect()->to(route('filament.admin.resources.sales.index').'?tableSearch='.$sale->sale_number);
                    }),
                EditAction::make()->label('Ubah'),
                DeleteAction::make()->label('Hapus'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Hapus Terpilih'),
                ]),
            ])
            ->defaultSort('proposed_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSaleProposals::route('/'),
            'view' => ViewSaleProposal::route('/{record}'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        return [
            'draft' => 'Draft',
            'submitted' => 'Diajukan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'converted_to_sale' => 'Sudah Jadi Penjualan',
            'cancelled' => 'Dibatalkan',
        ];
    }
}
