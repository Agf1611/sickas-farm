<?php

namespace App\Filament\Resources\WeightRecords;

use App\Filament\Resources\WeightRecords\Pages\ManageWeightRecords;
use App\Models\FatteningBatch;
use App\Models\Sheep;
use App\Models\WeighingRecord;
use App\Support\SickasFormatter;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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

class WeightRecordResource extends Resource
{
    protected static ?string $model = WeighingRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional Ternak';

    protected static ?string $navigationLabel = 'Timbang Ternak';

    protected static ?string $modelLabel = 'Timbang Ternak';

    protected static ?string $pluralModelLabel = 'Timbang Ternak';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Timbang')
                ->schema([
                    DatePicker::make('weighed_at')->label('Tanggal Timbang')->native(false)->required(),
                    Select::make('weight_type')
                        ->label('Jenis Timbang')
                        ->options([
                            'batch' => 'Per Batch',
                            'per_ekor' => 'Per Ekor',
                        ])
                        ->default('batch')
                        ->live()
                        ->required(),
                    Select::make('source')
                        ->label('Sumber Data')
                        ->options([
                            'actual_batch' => 'Aktual Batch',
                            'actual_individual' => 'Aktual Per Ekor',
                            'estimated' => 'Estimasi',
                        ])
                        ->default(fn ($get): string => $get('weight_type') === 'per_ekor' ? 'actual_individual' : 'actual_batch')
                        ->required(),
                    Select::make('fattening_batch_id')
                        ->label('Batch Penggemukan')
                        ->relationship('fatteningBatch', 'batch_code')
                        ->searchable()
                        ->preload()
                        ->live(),
                    Select::make('sheep_id')
                        ->label('Ternak')
                        ->options(function ($get): array {
                            return Sheep::query()
                                ->when($get('fattening_batch_id'), fn ($query, $batchId): mixed => $query->where('fattening_batch_id', $batchId))
                                ->whereIn('status', ['active', 'sick'])
                                ->orderBy('tag_number')
                                ->pluck('tag_number', 'id')
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->visible(fn ($get): bool => $get('weight_type') === 'per_ekor')
                        ->required(fn ($get): bool => $get('weight_type') === 'per_ekor')
                        ->live()
                        ->afterStateUpdated(function ($state, $set): void {
                            $sheep = filled($state) ? Sheep::query()->find($state) : null;

                            if ($sheep?->fattening_batch_id) {
                                $set('fattening_batch_id', $sheep->fattening_batch_id);
                            }
                        }),
                    TextInput::make('qty')
                        ->label('Jumlah Ekor')
                        ->numeric()
                        ->minValue(1)
                        ->visible(fn ($get): bool => $get('weight_type') === 'batch')
                        ->required(fn ($get): bool => $get('weight_type') === 'batch'),
                    TextInput::make('total_weight_kg')
                        ->label('Total Bobot')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('kg')
                        ->visible(fn ($get): bool => $get('weight_type') === 'batch')
                        ->required(fn ($get): bool => $get('weight_type') === 'batch'),
                    TextInput::make('average_weight_kg')
                        ->label('Rata-rata Bobot')
                        ->disabled()
                        ->dehydrated(false)
                        ->suffix('kg')
                        ->visible(fn ($get): bool => $get('weight_type') === 'batch')
                        ->formatStateUsing(function (?WeighingRecord $record): ?string {
                            return $record?->average_weight_kg;
                        }),
                    TextInput::make('weight_kg')
                        ->label('Bobot per Ekor')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('kg')
                        ->visible(fn ($get): bool => $get('weight_type') === 'per_ekor')
                        ->required(fn ($get): bool => $get('weight_type') === 'per_ekor'),
                    Textarea::make('notes')->label('Catatan')->rows(3)->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('weighed_at')->label('Tanggal')->formatStateUsing(fn ($state): string => SickasFormatter::date($state))->sortable(),
                TextColumn::make('weight_type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'batch' => 'success',
                        'per_ekor' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'batch' => 'Per Batch',
                        'per_ekor' => 'Per Ekor',
                        default => '-',
                    }),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'actual_batch' => 'Aktual Batch',
                        'actual_individual' => 'Aktual Per Ekor',
                        'estimated' => 'Estimasi',
                        default => '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'actual_batch' => 'success',
                        'actual_individual' => 'info',
                        'estimated' => 'warning',
                        default => 'gray',
                    })
                    ->visibleFrom('md'),
                TextColumn::make('fatteningBatch.batch_code')->label('Batch')->searchable()->visibleFrom('md'),
                TextColumn::make('sheep.tag_number')->label('Ternak')->searchable()->visibleFrom('md'),
                TextColumn::make('qty')->label('Jumlah')->formatStateUsing(fn ($state): string => SickasFormatter::number($state))->visibleFrom('lg'),
                TextColumn::make('total_weight_kg')->label('Total Bobot')->formatStateUsing(fn ($state): string => SickasFormatter::kg($state)),
                TextColumn::make('average_weight_kg')->label('Rata-rata')->formatStateUsing(fn ($state): string => SickasFormatter::kg($state))->visibleFrom('lg'),
                TextColumn::make('weight_kg')->label('Bobot/Ekor')->formatStateUsing(fn ($state): string => SickasFormatter::kg($state))->visibleFrom('md'),
            ])
            ->filters([
                Filter::make('weighed_at')
                    ->label('Tanggal Timbang')
                    ->schema([
                        DatePicker::make('from')->label('Dari Tanggal')->native(false),
                        DatePicker::make('until')->label('Sampai Tanggal')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('weighed_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('weighed_at', '<=', $date))),
            ])
            ->recordActions([
                EditAction::make()->label('Ubah'),
                DeleteAction::make()->label('Hapus'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Hapus Terpilih'),
                ]),
            ])
            ->defaultSort('weighed_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWeightRecords::route('/'),
        ];
    }
}
