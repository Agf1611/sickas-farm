<?php

namespace App\Filament\Resources\SheepBatches;

use App\Filament\Resources\SheepBatches\Pages\ManageSheepBatches;
use App\Filament\Resources\SheepBatches\Pages\ViewSheepBatch;
use App\Filament\Resources\SheepBatches\RelationManagers\SheepRelationManager;
use App\Models\FatteningBatch;
use App\Models\LivestockType;
use App\Services\GrowthMonitoringService;
use App\Services\QrCodeService;
use App\Support\SickasFormatter;
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
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SheepBatchResource extends Resource
{
    protected static ?string $model = FatteningBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional Ternak';

    protected static ?string $navigationLabel = 'Batch Penggemukan';

    protected static ?string $modelLabel = 'Batch Penggemukan';

    protected static ?string $pluralModelLabel = 'Batch Penggemukan';

    protected static ?string $recordTitleAttribute = 'batch_code';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Batch')
                ->schema([
                    TextInput::make('batch_code')
                        ->label('Kode Batch')
                        ->placeholder('Otomatis saat disimpan')
                        ->disabled()
                        ->dehydrated(false),
                    Select::make('livestock_type_id')
                        ->label('Jenis Ternak')
                        ->options(fn (): array => self::livestockTypeOptions())
                        ->default(fn (): ?int => self::defaultLivestockTypeId())
                        ->searchable()
                        ->preload()
                        ->required(),
                    DatePicker::make('start_date')->label('Tanggal Mulai')->native(false)->required(),
                    DatePicker::make('end_date')->label('Tanggal Selesai')->native(false),
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'active' => 'Aktif',
                            'closed' => 'Selesai',
                            'cancelled' => 'Dibatalkan',
                        ])
                        ->default('active')
                        ->required(),
                    Placeholder::make('detail_status_preview')
                        ->label('Status Detail Ternak')
                        ->content(fn (?FatteningBatch $record): string => match ($record?->detail_status) {
                            'complete' => 'Detail Lengkap',
                            'incomplete' => 'Detail Belum Lengkap',
                            default => 'Detail tersedia setelah data disimpan.',
                        }),
                    Toggle::make('is_historical')
                        ->label('Histori Manual')
                        ->helperText('Aktifkan untuk data lama sebelum aplikasi digunakan.'),
                ])
                ->columns(2),
            Section::make('Kandang dan Modal')
                ->schema([
                    Select::make('pen_id')
                        ->label('Kandang')
                        ->relationship('pen', 'name')
                        ->searchable()
                        ->preload(),
                    Select::make('supplier_id')
                        ->label('Supplier')
                        ->relationship('supplier', 'name')
                        ->searchable()
                        ->preload(),
                    TextInput::make('initial_head_count')->label('Jumlah Awal')->numeric()->minValue(0)->required(),
                    TextInput::make('current_head_count')->label('Jumlah Saat Ini')->numeric()->minValue(0)->required(),
                    TextInput::make('initial_total_weight_kg')->label('Total Bobot Awal')->numeric()->suffix('kg'),
                    TextInput::make('target_sale_average_weight_kg')
                        ->label('Target Berat Jual Rata-rata')
                        ->numeric()
                        ->suffix('kg')
                        ->placeholder('Default 30 kg'),
                    TextInput::make('purchase_capital')->label('Modal Pembelian')->numeric()->prefix('Rp')->required(),
                    Textarea::make('historical_notes')
                        ->label('Catatan Sumber Data Histori')
                        ->placeholder('Contoh: Data dari jurnal BUMDes 2025')
                        ->rows(3)
                        ->visible(fn ($get): bool => (bool) $get('is_historical'))
                        ->columnSpanFull(),
                    Textarea::make('notes')->label('Catatan')->rows(3)->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Monitoring Pertumbuhan')
                ->schema([
                    Placeholder::make('latest_batch_total_weight')
                        ->label('Total Berat Timbang Batch Terakhir')
                        ->content(fn (?FatteningBatch $record): string => $record ? self::latestBatchWeight($record) : '-'),
                    Placeholder::make('latest_individual_total_weight')
                        ->label('Total Berat dari Timbang Per Ekor')
                        ->content(fn (?FatteningBatch $record): string => $record ? self::latestIndividualSummaryWeight($record) : '-'),
                    Placeholder::make('growth_latest_weight')
                        ->label('Berat Terakhir')
                        ->content(fn (?FatteningBatch $record): string => $record ? self::formatKg(self::growth($record)['latest_weight']) : '-'),
                    Placeholder::make('growth_gain')
                        ->label('Kenaikan Berat')
                        ->content(fn (?FatteningBatch $record): string => $record ? self::formatKg(self::growth($record)['weight_gain']) : '-'),
                    Placeholder::make('growth_adg')
                        ->label('ADG')
                        ->content(fn (?FatteningBatch $record): string => $record ? self::formatAdg(self::growth($record)['adg']) : '-'),
                    Placeholder::make('growth_status')
                        ->label('Status Pertumbuhan')
                        ->content(fn (?FatteningBatch $record): string => $record ? self::growth($record)['status'] : '-'),
                    Placeholder::make('target_sale_average_weight')
                        ->label('Target Berat Jual')
                        ->content(fn (?FatteningBatch $record): string => $record ? self::formatKg(self::growth($record)['target_sale_average_weight']) : '30,00 kg'),
                    Placeholder::make('selling_indicator')
                        ->label('Indikator Keputusan Jual')
                        ->content(fn (?FatteningBatch $record): string => $record ? self::growth($record)['selling_indicator'] : '-'),
                    Placeholder::make('weighing_alert_status')
                        ->label('Peringatan Timbang')
                        ->content(fn (?FatteningBatch $record): string => $record ? self::growth($record)['weighing_alert_status'] : '-'),
                    Placeholder::make('last_weighing')
                        ->label('Timbang Terakhir')
                        ->content(fn (?FatteningBatch $record): string => $record ? self::formatLastWeighing(self::growth($record)) : '-'),
                    Placeholder::make('growth_recommendation')
                        ->label('Rekomendasi')
                        ->content(fn (?FatteningBatch $record): string => $record ? self::growth($record)['recommendation'].' '.self::growth($record)['weighing_alert_description'].' '.self::growth($record)['selling_indicator_description'] : 'Tersedia setelah data disimpan.')
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('QR Code')
                ->schema([
                    Placeholder::make('qr_code')
                        ->label('Kode QR')
                        ->content(fn (?FatteningBatch $record) => $record
                            ? app(QrCodeService::class)->previewHtml(
                                $record->batch_code,
                                app(QrCodeService::class)->batchDetailUrl($record),
                            )
                            : 'QR tersedia setelah data disimpan.')
                        ->columnSpanFull(),
                ])
                ->visible(fn (?FatteningBatch $record): bool => filled($record?->getKey())),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('batch_code')->label('Kode Batch')->searchable()->sortable(),
                TextColumn::make('livestockType.name')
                    ->label('Jenis Ternak')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('is_historical')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Histori Manual' : 'Normal')
                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray'),
                TextColumn::make('detail_status')
                    ->label('Detail')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'complete' => 'Detail Lengkap',
                        'incomplete' => 'Detail Belum Lengkap',
                        default => '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'complete' => 'success',
                        'incomplete' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('pen.name')->label('Kandang')->searchable()->visibleFrom('md'),
                TextColumn::make('supplier.name')->label('Supplier')->searchable()->visibleFrom('lg'),
                TextColumn::make('start_date')->label('Mulai')->formatStateUsing(fn ($state): string => SickasFormatter::date($state))->sortable(),
                TextColumn::make('current_head_count')->label('Populasi')->formatStateUsing(fn ($state): string => SickasFormatter::number($state))->sortable(),
                TextColumn::make('initial_total_weight_kg')->label('Bobot Awal')->formatStateUsing(fn ($state): string => SickasFormatter::kg($state))->visibleFrom('lg'),
                TextColumn::make('purchase_capital')->label('Modal')->formatStateUsing(fn ($state): string => SickasFormatter::rupiah($state))->sortable()->visibleFrom('md'),
                TextColumn::make('growth_adg')
                    ->label('ADG')
                    ->state(fn (FatteningBatch $record): ?float => self::growth($record)['adg'])
                    ->formatStateUsing(fn (?float $state): string => self::formatAdg($state))
                    ->visibleFrom('md'),
                TextColumn::make('growth_status')
                    ->label('Pertumbuhan')
                    ->state(fn (FatteningBatch $record): string => self::growth($record)['status'])
                    ->badge()
                    ->color(fn (string $state): string => app(GrowthMonitoringService::class)->colorForStatus($state))
                    ->visibleFrom('md'),
                TextColumn::make('weighing_alert_status')
                    ->label('Peringatan Timbang')
                    ->state(fn (FatteningBatch $record): string => self::growth($record)['weighing_alert_status'])
                    ->badge()
                    ->color(fn (string $state): string => app(GrowthMonitoringService::class)->colorForWeighingAlert($state))
                    ->visibleFrom('md'),
                TextColumn::make('selling_indicator')
                    ->label('Indikator Jual')
                    ->state(fn (FatteningBatch $record): string => self::growth($record)['selling_indicator'])
                    ->badge()
                    ->color(fn (string $state): string => app(GrowthMonitoringService::class)->colorForSellingIndicator($state))
                    ->visibleFrom('lg'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'closed' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'closed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default => '-',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'closed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ]),
                SelectFilter::make('livestock_type_id')
                    ->label('Jenis Ternak')
                    ->relationship('livestockType', 'name'),
                SelectFilter::make('is_historical')
                    ->label('Sumber Data')
                    ->options([
                        '1' => 'Histori Manual',
                        '0' => 'Normal',
                    ]),
                Filter::make('start_date')
                    ->label('Tanggal Mulai')
                    ->schema([
                        DatePicker::make('from')->label('Dari Tanggal')->native(false),
                        DatePicker::make('until')->label('Sampai Tanggal')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('start_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('start_date', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make()->label('Detail'),
                EditAction::make()->label('Ubah'),
                DeleteAction::make()->label('Hapus'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Hapus Terpilih'),
                ]),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSheepBatches::route('/'),
            'view' => ViewSheepBatch::route('/{record}'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            SheepRelationManager::class,
        ];
    }

    private static function growth(FatteningBatch $batch): array
    {
        return app(GrowthMonitoringService::class)->calculateBatchGrowth($batch);
    }

    private static function formatKg(?float $value): string
    {
        return SickasFormatter::kg($value);
    }

    private static function formatAdg(?float $value): string
    {
        return SickasFormatter::adg($value);
    }

    private static function formatLastWeighing(array $growth): string
    {
        if (! $growth['latest_weighed_at']) {
            return 'Belum ada data timbang.';
        }

        $days = $growth['days_since_last_weighing'];

        return $days ? SickasFormatter::date($growth['latest_weighed_at']).' ('.$days.' hari lalu)' : SickasFormatter::date($growth['latest_weighed_at']);
    }

    private static function latestBatchWeight(FatteningBatch $batch): string
    {
        $record = $batch->weighingRecords()
            ->where(function ($query): void {
                $query->where('weight_type', 'batch')->orWhere('record_type', 'batch');
            })
            ->where('source', 'actual_batch')
            ->whereNotNull('total_weight_kg')
            ->latest('weighed_at')
            ->latest('id')
            ->first();

        if (! $record) {
            return 'Belum ada timbang batch aktual.';
        }

        return SickasFormatter::kg((float) $record->total_weight_kg).' / '.SickasFormatter::kg((float) $record->average_weight_kg).' rata-rata';
    }

    private static function latestIndividualSummaryWeight(FatteningBatch $batch): string
    {
        $record = $batch->weighingRecords()
            ->where(function ($query): void {
                $query->where('weight_type', 'batch')->orWhere('record_type', 'batch');
            })
            ->where('source', 'actual_individual')
            ->whereNotNull('total_weight_kg')
            ->latest('weighed_at')
            ->latest('id')
            ->first();

        if (! $record) {
            return 'Belum ada ringkasan timbang per ekor.';
        }

        return SickasFormatter::kg((float) $record->total_weight_kg).' dari '.SickasFormatter::number($record->qty ?: $record->head_count).' ekor';
    }

    private static function defaultLivestockTypeId(): ?int
    {
        return LivestockType::query()
            ->where('code', 'DMB')
            ->value('id');
    }

    /**
     * @return array<int, string>
     */
    private static function livestockTypeOptions(): array
    {
        return LivestockType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
