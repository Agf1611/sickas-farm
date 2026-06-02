<?php

namespace App\Filament\Resources\LivestockTypes;

use App\Filament\Resources\LivestockTypes\Pages\ManageLivestockTypes;
use App\Models\LivestockType;
use App\Support\SickasFormatter;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LivestockTypeResource extends Resource
{
    protected static ?string $model = LivestockType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquaresPlus;

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Jenis Ternak';

    protected static ?string $modelLabel = 'Jenis Ternak';

    protected static ?string $pluralModelLabel = 'Jenis Ternak';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Jenis Ternak')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Jenis Ternak')
                        ->maxLength(255)
                        ->required(),
                    TextInput::make('code')
                        ->label('Kode Singkat')
                        ->maxLength(20)
                        ->unique(ignoreRecord: true)
                        ->required()
                        ->helperText('Contoh: DMB, KMB, SPI. Kode tidak boleh sama.'),
                    TextInput::make('quantity_unit')
                        ->label('Satuan Jumlah')
                        ->default('ekor')
                        ->maxLength(50)
                        ->required(),
                    TextInput::make('weight_unit')
                        ->label('Satuan Berat')
                        ->default('kg')
                        ->maxLength(50)
                        ->required(),
                ])
                ->columns(2),
            Section::make('Monitoring dan Target')
                ->schema([
                    Toggle::make('uses_weight_monitoring')
                        ->label('Gunakan Monitoring Berat')
                        ->default(true)
                        ->required(),
                    TextInput::make('default_sale_target_weight')
                        ->label('Target Berat Jual Default')
                        ->numeric()
                        ->suffix('kg')
                        ->minValue(0),
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true)
                        ->required(),
                    Textarea::make('notes')
                        ->label('Keterangan')
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
                TextColumn::make('name')
                    ->label('Nama Jenis')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('quantity_unit')
                    ->label('Satuan Jumlah')
                    ->visibleFrom('md'),
                TextColumn::make('weight_unit')
                    ->label('Satuan Berat')
                    ->visibleFrom('md'),
                IconColumn::make('uses_weight_monitoring')
                    ->label('Monitoring Berat')
                    ->boolean()
                    ->visibleFrom('lg'),
                TextColumn::make('default_sale_target_weight')
                    ->label('Target Jual')
                    ->formatStateUsing(fn ($state): string => SickasFormatter::kg($state))
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Nonaktif')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->formatStateUsing(fn ($state): string => SickasFormatter::dateTime($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLivestockTypes::route('/'),
        ];
    }
}
