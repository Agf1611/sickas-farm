<?php

namespace App\Filament\Resources\StockMovements;

use App\Filament\Resources\StockMovements\Pages\ManageStockMovements;
use App\Models\StockMovement;
use App\Support\SickasFormatter;
use BackedEnum;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional Ternak';

    protected static ?string $navigationLabel = 'Kartu Stok Ternak';

    protected static ?string $modelLabel = 'Kartu Stok Ternak';

    protected static ?string $pluralModelLabel = 'Kartu Stok Ternak';

    protected static ?int $navigationSort = 9;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Kartu Stok')
                ->schema([
                    DatePicker::make('movement_date')->label('Tanggal')->native(false)->required(),
                    Select::make('movement_type')
                        ->label('Jenis Mutasi')
                        ->options(self::movementTypeOptions())
                        ->required(),
                    Select::make('fattening_batch_id')->label('Batch')->relationship('fatteningBatch', 'batch_code')->searchable()->preload(),
                    Select::make('livestock_type_id')->label('Jenis Ternak')->relationship('livestockType', 'name')->searchable()->preload(),
                    TextInput::make('quantity_in')->label('Masuk')->numeric()->minValue(0),
                    TextInput::make('quantity_out')->label('Keluar')->numeric()->minValue(0),
                    TextInput::make('balance_after')->label('Saldo Setelah Mutasi')->numeric(),
                    Textarea::make('notes')->label('Catatan')->rows(3)->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('movement_date')->label('Tanggal')->formatStateUsing(fn ($state): string => SickasFormatter::date($state))->sortable(),
                TextColumn::make('movement_type')
                    ->label('Mutasi')
                    ->formatStateUsing(fn (?string $state): string => self::movementTypeOptions()[$state] ?? '-')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'purchase' => 'success',
                        'sale' => 'info',
                        'death', 'lost', 'culled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('fatteningBatch.batch_code')->label('Batch')->searchable(),
                TextColumn::make('livestockType.name')->label('Jenis')->badge()->color('info')->visibleFrom('md'),
                TextColumn::make('pen.name')->label('Kandang')->visibleFrom('lg'),
                TextColumn::make('quantity_in')->label('Masuk')->formatStateUsing(fn ($state): string => SickasFormatter::number($state))->alignEnd(),
                TextColumn::make('quantity_out')->label('Keluar')->formatStateUsing(fn ($state): string => SickasFormatter::number($state))->alignEnd(),
                TextColumn::make('balance_after')->label('Saldo')->formatStateUsing(fn ($state): string => $state === null ? '-' : SickasFormatter::number($state))->alignEnd(),
                TextColumn::make('notes')->label('Catatan')->limit(35)->toggleable(),
            ])
            ->filters([
                SelectFilter::make('movement_type')->label('Jenis Mutasi')->options(self::movementTypeOptions()),
                SelectFilter::make('livestock_type_id')->label('Jenis Ternak')->relationship('livestockType', 'name'),
                SelectFilter::make('fattening_batch_id')->label('Batch')->relationship('fatteningBatch', 'batch_code'),
            ])
            ->recordActions([
                ViewAction::make()->label('Detail'),
            ])
            ->defaultSort('movement_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStockMovements::route('/'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function movementTypeOptions(): array
    {
        return [
            'purchase' => 'Pembelian',
            'sale' => 'Penjualan',
            'death' => 'Mati',
            'lost' => 'Hilang',
            'culled' => 'Afkir',
            'transfer' => 'Pindah Kandang',
            'adjustment' => 'Penyesuaian',
        ];
    }
}
