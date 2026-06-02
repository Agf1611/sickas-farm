<?php

namespace App\Filament\Resources\Sheep;

use App\Filament\Concerns\HasSickasPhotoUpload;
use App\Filament\Resources\Sheep\Pages\ManageSheep;
use App\Filament\Resources\Sheep\Pages\ViewSheep;
use App\Filament\Resources\Sheep\RelationManagers\WeighingRecordsRelationManager;
use App\Models\FatteningBatch;
use App\Models\LivestockType;
use App\Models\Sheep;
use App\Services\GrowthMonitoringService;
use App\Services\MarketValueEstimationService;
use App\Services\QrCodeService;
use App\Support\SickasFormatter;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class SheepResource extends Resource
{
    use HasSickasPhotoUpload;

    protected static ?string $model = Sheep::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional Ternak';

    protected static ?string $navigationLabel = 'Data Ternak';

    protected static ?string $modelLabel = 'Data Ternak';

    protected static ?string $pluralModelLabel = 'Data Ternak';

    protected static ?string $recordTitleAttribute = 'tag_number';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Ternak')
                ->schema([
                    TextInput::make('tag_number')
                        ->label('Nomor Ternak')
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
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'active' => 'Aktif',
                            'sold' => 'Terjual',
                            'dead' => 'Mati',
                            'lost' => 'Hilang',
                            'culled' => 'Afkir',
                            'sick' => 'Sakit',
                        ])
                        ->default('active')
                        ->required(),
                    Select::make('fattening_batch_id')
                        ->label('Batch Penggemukan')
                        ->relationship('fatteningBatch', 'batch_code')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function ($state, $set): void {
                            $batch = filled($state) ? FatteningBatch::query()->find($state) : null;

                            if ($batch?->livestock_type_id) {
                                $set('livestock_type_id', $batch->livestock_type_id);
                            }

                            if ($batch?->pen_id) {
                                $set('pen_id', $batch->pen_id);
                            }
                        }),
                    Select::make('pen_id')
                        ->label('Kandang')
                        ->relationship('pen', 'name')
                        ->searchable()
                        ->preload(),
                ])
                ->columns(2),
            Section::make('Detail Fisik dan Harga')
                ->schema([
                    Select::make('sex')
                        ->label('Jenis Kelamin')
                        ->options([
                            'male' => 'Jantan',
                            'female' => 'Betina',
                        ]),
                    TextInput::make('estimated_age_months')->label('Perkiraan Umur')->numeric()->suffix('bulan')->minValue(0),
                    TextInput::make('initial_weight_kg')->label('Bobot Awal')->numeric()->suffix('kg'),
                    TextInput::make('purchase_price')->label('Harga Beli')->numeric()->prefix('Rp'),
                    Toggle::make('is_estimated')
                        ->label('Data Rata-rata / Belum Lengkap')
                        ->helperText('Matikan jika bobot, harga, dan catatan ternak sudah dilengkapi.')
                        ->default(false),
                    Textarea::make('notes')->label('Catatan')->rows(3)->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Foto Ternak')
                ->schema([
                    self::photoUpload('photo_paths', 'Foto Ternak', 'sickas-farm/domba'),
                ]),
            Section::make('Monitoring Pertumbuhan')
                ->schema([
                    Placeholder::make('growth_latest_weight')
                        ->label('Berat Terakhir')
                        ->content(fn (?Sheep $record): string => $record ? self::formatKg(self::growth($record)['latest_weight']) : '-'),
                    Placeholder::make('growth_gain')
                        ->label('Kenaikan Berat')
                        ->content(fn (?Sheep $record): string => $record ? self::formatKg(self::growth($record)['weight_gain']) : '-'),
                    Placeholder::make('growth_adg')
                        ->label('ADG')
                        ->content(fn (?Sheep $record): string => $record ? self::formatAdg(self::growth($record)['adg']) : '-'),
                    Placeholder::make('growth_status')
                        ->label('Status Pertumbuhan')
                        ->content(fn (?Sheep $record): string => $record ? self::growth($record)['status'] : '-'),
                    Placeholder::make('growth_recommendation')
                        ->label('Rekomendasi')
                        ->content(fn (?Sheep $record): string => $record ? self::growth($record)['recommendation'] : 'Tersedia setelah data disimpan.')
                        ->columnSpanFull(),
                    Placeholder::make('weight_chart')
                        ->label('Grafik Berat Per Ekor')
                        ->content(fn (?Sheep $record): HtmlString|string => $record ? self::weightSparkline($record) : 'Tersedia setelah data disimpan.')
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Estimasi Nilai Jual')
                ->schema([
                    Placeholder::make('market_unit_price')
                        ->label('Harga Pasaran Dipakai')
                        ->content(fn (?Sheep $record): string => $record ? SickasFormatter::rupiah(self::marketEstimate($record)['unit_price']) : '-'),
                    Placeholder::make('estimated_market_value')
                        ->label('Estimasi Nilai Jual')
                        ->content(fn (?Sheep $record): string => $record ? SickasFormatter::rupiah(self::marketEstimate($record)['estimated_value']) : '-'),
                    Placeholder::make('estimated_profit_loss')
                        ->label('Estimasi Untung / Rugi')
                        ->content(fn (?Sheep $record): string => $record ? SickasFormatter::rupiah(self::marketEstimate($record)['estimated_profit_loss']) : '-'),
                    Placeholder::make('market_note')
                        ->label('Catatan')
                        ->content('Estimasi memakai harga pasaran aktif terbaru. Ini bukan transaksi penjualan.')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->visible(fn (?Sheep $record): bool => filled($record?->getKey())),
            Section::make('QR Code')
                ->schema([
                    Placeholder::make('qr_code')
                        ->label('Kode QR')
                        ->content(fn (?Sheep $record) => $record
                            ? app(QrCodeService::class)->previewHtml(
                                $record->tag_number,
                                app(QrCodeService::class)->sheepDetailUrl($record),
                            )
                            : 'QR tersedia setelah data disimpan.')
                        ->columnSpanFull(),
                ])
                ->visible(fn (?Sheep $record): bool => filled($record?->getKey())),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tag_number')->label('Nomor Ternak')->searchable()->sortable(),
                self::photoColumn('photo_paths', 'Foto'),
                TextColumn::make('livestockType.name')
                    ->label('Jenis Ternak')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fatteningBatch.batch_code')->label('Batch')->searchable()->visibleFrom('md'),
                TextColumn::make('pen.name')->label('Kandang')->searchable()->visibleFrom('lg'),
                TextColumn::make('sex')
                    ->label('Kelamin')
                    ->visibleFrom('md')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'male' => 'Jantan',
                        'female' => 'Betina',
                        default => '-',
                    }),
                TextColumn::make('initial_weight_kg')->label('Bobot Awal')->formatStateUsing(fn ($state): string => SickasFormatter::kg($state))->visibleFrom('md'),
                TextColumn::make('current_weight_kg')
                    ->label('Bobot Saat Ini')
                    ->formatStateUsing(fn ($state): string => SickasFormatter::kg($state))
                    ->visibleFrom('lg'),
                TextColumn::make('purchase_price')->label('Harga Beli')->formatStateUsing(fn ($state): string => SickasFormatter::rupiah($state))->sortable()->visibleFrom('lg'),
                TextColumn::make('estimated_market_value')
                    ->label('Estimasi Jual')
                    ->state(fn (Sheep $record): float => self::marketEstimate($record)['estimated_value'])
                    ->formatStateUsing(fn ($state): string => SickasFormatter::rupiah($state))
                    ->color('success')
                    ->visibleFrom('lg'),
                TextColumn::make('is_estimated')
                    ->label('Detail')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Belum Lengkap' : 'Lengkap')
                    ->color(fn (bool $state): string => $state ? 'warning' : 'success')
                    ->visibleFrom('md'),
                TextColumn::make('growth_adg')
                    ->label('ADG')
                    ->state(fn (Sheep $record): ?float => self::growth($record)['adg'])
                    ->formatStateUsing(fn (?float $state): string => self::formatAdg($state))
                    ->visibleFrom('md'),
                TextColumn::make('growth_status')
                    ->label('Pertumbuhan')
                    ->state(fn (Sheep $record): string => self::growth($record)['status'])
                    ->badge()
                    ->color(fn (string $state): string => app(GrowthMonitoringService::class)->colorForStatus($state))
                    ->visibleFrom('md'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'sold' => 'info',
                        'dead' => 'danger',
                        'lost' => 'warning',
                        'culled' => 'gray',
                        'sick' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'sold' => 'Terjual',
                        'dead' => 'Mati',
                        'lost' => 'Hilang',
                        'culled' => 'Afkir',
                        'sick' => 'Sakit',
                        default => '-',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'sold' => 'Terjual',
                        'dead' => 'Mati',
                        'lost' => 'Hilang',
                        'culled' => 'Afkir',
                        'sick' => 'Sakit',
                    ]),
                SelectFilter::make('livestock_type_id')
                    ->label('Jenis Ternak')
                    ->relationship('livestockType', 'name'),
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
            ->defaultSort('tag_number');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSheep::route('/'),
            'view' => ViewSheep::route('/{record}'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            WeighingRecordsRelationManager::class,
        ];
    }

    private static function growth(Sheep $sheep): array
    {
        return app(GrowthMonitoringService::class)->calculateSheepGrowth($sheep);
    }

    /**
     * @return array<string, mixed>
     */
    private static function marketEstimate(Sheep $sheep): array
    {
        return app(MarketValueEstimationService::class)->estimateSheep($sheep);
    }

    private static function formatKg(?float $value): string
    {
        return SickasFormatter::kg($value);
    }

    private static function formatAdg(?float $value): string
    {
        return SickasFormatter::adg($value);
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

    private static function weightSparkline(Sheep $sheep): HtmlString
    {
        $points = collect();

        if ($sheep->initial_weight_kg !== null) {
            $points->push([
                'label' => 'Awal',
                'weight' => (float) $sheep->initial_weight_kg,
            ]);
        }

        $sheep->weighingRecords()
            ->where(function ($query): void {
                $query->where('weight_type', 'per_ekor')->orWhere('record_type', 'individual');
            })
            ->whereNotNull('weight_kg')
            ->orderBy('weighed_at')
            ->limit(12)
            ->get()
            ->each(fn ($record) => $points->push([
                'label' => SickasFormatter::date($record->weighed_at),
                'weight' => (float) $record->weight_kg,
            ]));

        if ($points->count() < 2) {
            return new HtmlString('<div class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">Belum cukup data timbang per ekor untuk grafik.</div>');
        }

        $weights = $points->pluck('weight');
        $min = (float) $weights->min();
        $max = (float) $weights->max();
        $range = max(0.01, $max - $min);
        $width = 520;
        $height = 120;
        $step = $points->count() > 1 ? $width / ($points->count() - 1) : $width;

        $polyline = $points
            ->values()
            ->map(function (array $point, int $index) use ($min, $range, $height, $step): string {
                $x = round($index * $step, 2);
                $y = round($height - (((float) $point['weight'] - $min) / $range * ($height - 20)) - 10, 2);

                return $x.','.$y;
            })
            ->implode(' ');

        $first = $points->first()['weight'];
        $last = $points->last()['weight'];

        return new HtmlString(<<<HTML
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2 text-sm">
                    <span class="font-medium text-gray-700 dark:text-gray-200">{$points->count()} titik timbang</span>
                    <span class="text-gray-500 dark:text-gray-400">{$first} kg ke {$last} kg</span>
                </div>
                <svg viewBox="0 0 {$width} {$height}" class="h-32 w-full overflow-visible">
                    <line x1="0" y1="{$height}" x2="{$width}" y2="{$height}" stroke="rgba(100,116,139,.25)" />
                    <polyline points="{$polyline}" fill="none" stroke="#10b981" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
        HTML);
    }
}
