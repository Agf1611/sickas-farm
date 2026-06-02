<?php

namespace App\Filament\Resources\LivestockMarketPrices;

use App\Filament\Resources\LivestockMarketPrices\Pages\ManageLivestockMarketPrices;
use App\Models\LivestockMarketPrice;
use App\Support\SickasFormatter;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
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

class LivestockMarketPriceResource extends Resource
{
    protected static ?string $model = LivestockMarketPrice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Harga Pasaran';

    protected static ?string $modelLabel = 'Harga Pasaran';

    protected static ?string $pluralModelLabel = 'Harga Pasaran';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Harga Pasaran')
                ->schema([
                    Select::make('livestock_type_id')
                        ->label('Jenis Ternak')
                        ->relationship('livestockType', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    DatePicker::make('effective_date')
                        ->label('Tanggal Berlaku')
                        ->native(false)
                        ->default(now())
                        ->required(),
                    Select::make('price_type')
                        ->label('Tipe Harga')
                        ->options([
                            'per_kg' => 'Per Kg',
                            'per_head' => 'Per Ekor',
                        ])
                        ->default('per_kg')
                        ->required(),
                    TextInput::make('price_per_kg')
                        ->label('Harga per Kg')
                        ->numeric()
                        ->prefix('Rp'),
                    TextInput::make('price_per_head')
                        ->label('Harga per Ekor')
                        ->numeric()
                        ->prefix('Rp'),
                    TextInput::make('source')
                        ->label('Sumber Harga')
                        ->maxLength(255)
                        ->placeholder('Contoh: Pasar Hewan, pengepul, survei lokal'),
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true)
                        ->required(),
                    Textarea::make('notes')
                        ->label('Catatan')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('effective_date')
                    ->label('Tanggal')
                    ->formatStateUsing(fn ($state): string => SickasFormatter::date($state))
                    ->sortable(),
                TextColumn::make('livestockType.name')
                    ->label('Jenis Ternak')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price_type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state === 'per_head' ? 'Per Ekor' : 'Per Kg')
                    ->color(fn (?string $state): string => $state === 'per_head' ? 'warning' : 'success'),
                TextColumn::make('price_per_kg')
                    ->label('Harga/Kg')
                    ->formatStateUsing(fn ($state): string => SickasFormatter::rupiah($state))
                    ->alignEnd(),
                TextColumn::make('price_per_head')
                    ->label('Harga/Ekor')
                    ->formatStateUsing(fn ($state): string => SickasFormatter::rupiah($state))
                    ->alignEnd()
                    ->visibleFrom('md'),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Nonaktif')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('livestock_type_id')
                    ->label('Jenis Ternak')
                    ->relationship('livestockType', 'name'),
                SelectFilter::make('price_type')
                    ->label('Tipe Harga')
                    ->options([
                        'per_kg' => 'Per Kg',
                        'per_head' => 'Per Ekor',
                    ]),
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
            ->defaultSort('effective_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLivestockMarketPrices::route('/'),
        ];
    }
}
