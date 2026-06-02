<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Concerns\HasSickasPhotoUpload;
use App\Filament\Resources\Sales\Pages\ManageSales;
use App\Models\Sale;
use App\Services\ReportExportService;
use App\Support\SickasFormatter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SaleResource extends Resource
{
    use HasSickasPhotoUpload;

    protected static ?string $model = Sale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Penjualan';

    protected static ?string $modelLabel = 'Penjualan';

    protected static ?string $pluralModelLabel = 'Penjualan';

    protected static ?string $recordTitleAttribute = 'sale_number';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Penjualan')
                ->schema([
                    TextInput::make('sale_number')
                        ->label('Nomor Invoice')
                        ->placeholder('Otomatis saat disimpan')
                        ->disabled()
                        ->dehydrated(false),
                    DatePicker::make('sale_date')->label('Tanggal Penjualan')->native(false)->required(),
                    Select::make('buyer_id')
                        ->label('Pembeli')
                        ->relationship('buyer', 'name')
                        ->searchable()
                        ->preload(),
                    Select::make('fattening_batch_id')
                        ->label('Batch Penggemukan')
                        ->relationship('fatteningBatch', 'batch_code')
                        ->searchable()
                        ->preload(),
                    Select::make('sale_type')
                        ->label('Jenis Penjualan')
                        ->options([
                            'per_head' => 'Per Ekor',
                            'bulk' => 'Borongan',
                            'per_kg' => 'Per Kg',
                        ])
                        ->required(),
                ])
                ->columns(2),
            Section::make('Jumlah dan Nilai')
                ->schema([
                    TextInput::make('head_count')->label('Jumlah Ekor')->numeric()->minValue(0)->required(),
                    TextInput::make('total_weight_kg')->label('Total Bobot')->numeric()->suffix('kg'),
                    TextInput::make('unit_price')->label('Harga Satuan')->numeric()->prefix('Rp'),
                    TextInput::make('total_amount')->label('Total Penjualan')->numeric()->prefix('Rp')->required(),
                    Textarea::make('notes')->label('Catatan')->rows(3)->columnSpanFull(),
                    self::photoUpload('proof_photo_paths', 'Foto Bukti Penjualan', 'sickas-farm/bukti-penjualan'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sale_number')->label('Nomor')->searchable()->sortable(),
                TextColumn::make('sale_date')->label('Tanggal')->formatStateUsing(fn ($state): string => SickasFormatter::date($state))->sortable(),
                TextColumn::make('buyer.name')->label('Pembeli')->searchable()->visibleFrom('md'),
                TextColumn::make('fatteningBatch.batch_code')->label('Batch')->searchable()->visibleFrom('lg'),
                TextColumn::make('sale_type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'per_head' => 'info',
                        'bulk' => 'success',
                        'per_kg' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'per_head' => 'Per Ekor',
                        'bulk' => 'Borongan',
                        'per_kg' => 'Per Kg',
                        default => '-',
                    }),
                TextColumn::make('head_count')->label('Jumlah')->formatStateUsing(fn ($state): string => SickasFormatter::number($state))->visibleFrom('md'),
                TextColumn::make('total_weight_kg')->label('Bobot')->formatStateUsing(fn ($state): string => SickasFormatter::kg($state))->visibleFrom('lg'),
                TextColumn::make('total_amount')->label('Total')->formatStateUsing(fn ($state): string => SickasFormatter::rupiah($state))->sortable(),
                self::photoColumn('proof_photo_paths', 'Foto Bukti'),
            ])
            ->filters([
                Filter::make('sale_date')
                    ->label('Tanggal Penjualan')
                    ->schema([
                        DatePicker::make('from')->label('Dari Tanggal')->native(false),
                        DatePicker::make('until')->label('Sampai Tanggal')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('sale_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('sale_date', '<=', $date))),
            ])
            ->headerActions([
                Action::make('export_excel')
                    ->label('Export Excel')
                    ->color('success')
                    ->action(fn ($livewire) => app(ReportExportService::class)->downloadSalesExcel([
                        'from' => $livewire->getTableFilterState('sale_date')['from'] ?? null,
                        'until' => $livewire->getTableFilterState('sale_date')['until'] ?? null,
                    ])),
                Action::make('export_pdf')
                    ->label('Export PDF')
                    ->color('danger')
                    ->action(fn ($livewire) => app(ReportExportService::class)->downloadSalesPdf([
                        'from' => $livewire->getTableFilterState('sale_date')['from'] ?? null,
                        'until' => $livewire->getTableFilterState('sale_date')['until'] ?? null,
                    ])),
            ])
            ->recordActions([
                ViewAction::make()->label('Detail'),
                Action::make('sold_livestock_detail')
                    ->label('Ternak Terjual')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->color('info')
                    ->modalHeading(fn (Sale $record): string => 'Detail Ternak Terjual - '.$record->sale_number)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (Sale $record) => view('filament.sales.sold-livestock-detail', [
                        'sale' => $record->loadMissing([
                            'saleItems.sheep.livestockType',
                            'saleItems.sheep.fatteningBatch',
                            'saleItems.sheep.pen',
                        ]),
                    ])),
                Action::make('invoice_pdf')
                    ->label('Preview Invoice')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (Sale $record): string => route('sickas-farm.invoices.sale.preview', $record))
                    ->openUrlInNewTab(),
                EditAction::make()->label('Ubah'),
                DeleteAction::make()->label('Hapus'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Hapus Terpilih'),
                ]),
            ])
            ->defaultSort('sale_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSales::route('/'),
        ];
    }
}
