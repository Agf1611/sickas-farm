<?php

namespace App\Filament\Resources\SheepBatches\RelationManagers;

use App\Filament\Concerns\HasSickasPhotoUpload;
use App\Models\FatteningBatch;
use App\Models\Sheep;
use App\Services\GrowthMonitoringService;
use App\Support\SickasFormatter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SheepRelationManager extends RelationManager
{
    use HasSickasPhotoUpload;

    protected static string $relationship = 'sheep';

    protected static ?string $title = 'Data Ternak Dalam Batch';

    public function form(Schema $schema): Schema
    {
        /** @var FatteningBatch $batch */
        $batch = $this->getOwnerRecord();

        return $schema->components([
            TextInput::make('tag_number')
                ->label('Nomor Ternak')
                ->placeholder('Otomatis saat disimpan')
                ->disabled()
                ->dehydrated(false),
            Select::make('livestock_type_id')
                ->label('Jenis Ternak')
                ->relationship('livestockType', 'name')
                ->default($batch->livestock_type_id)
                ->searchable()
                ->preload()
                ->required(),
            Select::make('pen_id')
                ->label('Kandang')
                ->relationship('pen', 'name')
                ->default($batch->pen_id)
                ->searchable()
                ->preload(),
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
            TextInput::make('initial_weight_kg')
                ->label('Bobot Awal')
                ->numeric()
                ->suffix('kg'),
            TextInput::make('purchase_price')
                ->label('Harga Beli')
                ->numeric()
                ->prefix('Rp'),
            Toggle::make('is_estimated')
                ->label('Data Rata-rata / Belum Lengkap')
                ->helperText('Matikan jika data ternak ini sudah dilengkapi.')
                ->default(false),
            Textarea::make('notes')
                ->label('Catatan')
                ->rows(3)
                ->columnSpanFull(),
            self::photoUpload('photo_paths', 'Foto Ternak', 'sickas-farm/domba'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tag_number')
            ->columns([
                TextColumn::make('tag_number')->label('Nomor Ternak')->searchable(),
                self::photoColumn('photo_paths', 'Foto'),
                TextColumn::make('livestockType.name')->label('Jenis')->badge()->color('info'),
                TextColumn::make('initial_weight_kg')->label('Bobot Awal')->formatStateUsing(fn ($state): string => SickasFormatter::kg($state)),
                TextColumn::make('current_weight_kg')->label('Bobot Saat Ini')->formatStateUsing(fn ($state): string => SickasFormatter::kg($state))->visibleFrom('md'),
                TextColumn::make('purchase_price')->label('Harga Beli')->formatStateUsing(fn ($state): string => SickasFormatter::rupiah($state))->visibleFrom('md'),
                TextColumn::make('growth_status')
                    ->label('Pertumbuhan')
                    ->state(fn (Sheep $record): string => app(GrowthMonitoringService::class)->calculateSheepGrowth($record)['status'])
                    ->badge()
                    ->color(fn (string $state): string => app(GrowthMonitoringService::class)->colorForStatus($state))
                    ->visibleFrom('md'),
                TextColumn::make('is_estimated')
                    ->label('Detail')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Belum Lengkap' : 'Lengkap')
                    ->color(fn (bool $state): string => $state ? 'warning' : 'success'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'sold' => 'Terjual',
                        'dead' => 'Mati',
                        'lost' => 'Hilang',
                        'culled' => 'Afkir',
                        'sick' => 'Sakit',
                        default => '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'sold' => 'info',
                        'dead' => 'danger',
                        'lost', 'sick' => 'warning',
                        'culled' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Ternak')
                    ->mutateDataUsing(function (array $data): array {
                        /** @var FatteningBatch $batch */
                        $batch = $this->getOwnerRecord();

                        $data['livestock_type_id'] ??= $batch->livestock_type_id;
                        $data['pen_id'] ??= $batch->pen_id;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()->label('Ubah'),
                DeleteAction::make()->label('Hapus'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Hapus Terpilih'),
                ]),
            ]);
    }
}
