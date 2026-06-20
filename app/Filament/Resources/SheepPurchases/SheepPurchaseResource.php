<?php

namespace App\Filament\Resources\SheepPurchases;

use App\Filament\Concerns\HasSickasPhotoUpload;
use App\Filament\Resources\SheepPurchases\Pages\ManageSheepPurchases;
use App\Models\LivestockType;
use App\Models\SheepPurchase;
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

class SheepPurchaseResource extends Resource
{
    use HasSickasPhotoUpload;

    protected static ?string $model = SheepPurchase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional Ternak';

    protected static ?string $navigationLabel = 'Pembelian Ternak';

    protected static ?string $modelLabel = 'Pembelian Ternak';

    protected static ?string $pluralModelLabel = 'Pembelian Ternak';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Pembelian')
                ->schema([
                    DatePicker::make('purchase_date')
                        ->label('Tanggal Pembelian')
                        ->native(false)
                        ->required(),
                    Select::make('purchase_type')
                        ->label('Tipe Pembelian')
                        ->options([
                            'bulk' => 'Borongan',
                            'per_head' => 'Per Ekor',
                            'per_kg' => 'Per Kg',
                        ])
                        ->default('bulk')
                        ->required(),
                    Select::make('livestock_type_id')
                        ->label('Jenis Ternak')
                        ->options(fn (): array => LivestockType::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->default(fn (): ?int => LivestockType::query()->where('code', 'DMB')->value('id'))
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('supplier_id')
                        ->label('Supplier')
                        ->relationship('supplier', 'name')
                        ->searchable()
                        ->preload(),
                    Select::make('pen_id')
                        ->label('Kandang')
                        ->relationship('pen', 'name')
                        ->searchable()
                        ->preload(),
                ])
                ->columns(2),
            Section::make('Jumlah dan Biaya')
                ->schema([
                    TextInput::make('head_count')
                        ->label('Jumlah Ekor')
                        ->numeric()
                        ->minValue(1)
                        ->required(),
                    TextInput::make('total_weight_kg')
                        ->label('Total Berat')
                        ->numeric()
                        ->suffix('kg'),
                    TextInput::make('total_purchase_price')
                        ->label('Total Harga Beli')
                        ->numeric()
                        ->prefix('Rp')
                        ->required(),
                    TextInput::make('transport_cost')
                        ->label('Biaya Transport')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0)
                        ->required(),
                    TextInput::make('other_cost')
                        ->label('Biaya Lain-lain')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0)
                        ->required(),
                ])
                ->columns(2),
            Section::make('Detail Tambahan')
                ->schema([
                    Select::make('fattening_batch_id')
                        ->label('Hubungkan ke Batch')
                        ->relationship('fatteningBatch', 'batch_code')
                        ->helperText('Kosongkan jika ingin membuat batch baru otomatis.')
                        ->searchable()
                        ->preload(),
                    Textarea::make('notes')
                        ->label('Catatan')
                        ->rows(3)
                        ->columnSpanFull(),
                    self::photoUpload('proof_photo_paths', 'Foto Bukti Pembelian', 'sickas-farm/bukti-pembelian'),
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
                TextColumn::make('purchase_date')->label('Tanggal')->formatStateUsing(fn ($state): string => SickasFormatter::date($state))->sortable(),
                TextColumn::make('purchase_number')->label('Invoice')->searchable()->toggleable(),
                TextColumn::make('purchase_type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'bulk' => 'success',
                        'per_head' => 'info',
                        'per_kg' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'bulk' => 'Borongan',
                        'per_head' => 'Per Ekor',
                        'per_kg' => 'Per Kg',
                        default => '-',
                    }),
                TextColumn::make('livestockType.name')
                    ->label('Jenis Ternak')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->visibleFrom('md'),
                TextColumn::make('supplier.name')->label('Supplier')->searchable()->visibleFrom('md'),
                TextColumn::make('pen.name')->label('Kandang')->searchable()->visibleFrom('lg'),
                TextColumn::make('fatteningBatch.batch_code')->label('Batch')->searchable()->visibleFrom('md'),
                TextColumn::make('head_count')->label('Jumlah')->formatStateUsing(fn ($state): string => SickasFormatter::number($state)),
                TextColumn::make('total_weight_kg')->label('Berat')->formatStateUsing(fn ($state): string => SickasFormatter::kg($state))->visibleFrom('md'),
                TextColumn::make('total_purchase_price')->label('Harga Beli')->formatStateUsing(fn ($state): string => SickasFormatter::rupiah($state))->sortable(),
                TextColumn::make('transport_cost')->label('Transport')->formatStateUsing(fn ($state): string => SickasFormatter::rupiah($state))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('other_cost')->label('Biaya Lain')->formatStateUsing(fn ($state): string => SickasFormatter::rupiah($state))->toggleable(isToggledHiddenByDefault: true),
                self::photoColumn('proof_photo_paths', 'Foto Bukti'),
            ])
            ->filters([
                Filter::make('purchase_date')
                    ->label('Tanggal Pembelian')
                    ->schema([
                        DatePicker::make('from')->label('Dari Tanggal')->native(false),
                        DatePicker::make('until')->label('Sampai Tanggal')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('purchase_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('purchase_date', '<=', $date))),
            ])
            ->headerActions([
                Action::make('export_excel')
                    ->label('Export Excel')
                    ->color('success')
                    ->action(fn ($livewire) => app(ReportExportService::class)->downloadPurchasesExcel([
                        'from' => $livewire->getTableFilterState('purchase_date')['from'] ?? null,
                        'until' => $livewire->getTableFilterState('purchase_date')['until'] ?? null,
                    ])),
                Action::make('export_pdf')
                    ->label('Export PDF')
                    ->color('danger')
                    ->action(fn ($livewire) => app(ReportExportService::class)->downloadPurchasesPdf([
                        'from' => $livewire->getTableFilterState('purchase_date')['from'] ?? null,
                        'until' => $livewire->getTableFilterState('purchase_date')['until'] ?? null,
                    ])),
            ])
            ->recordActions([
                ViewAction::make()->label('Detail'),
                Action::make('invoice_pdf')
                    ->label('Preview Invoice')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (SheepPurchase $record): string => route('sickas-farm.invoices.purchase.preview', $record))
                    ->openUrlInNewTab(),
                EditAction::make()->label('Ubah'),
                DeleteAction::make()->label('Hapus'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Hapus Terpilih'),
                ]),
            ])
            ->defaultSort('purchase_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSheepPurchases::route('/'),
        ];
    }
}
