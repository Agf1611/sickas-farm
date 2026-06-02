<?php

namespace App\Filament\Resources\Suppliers;

use App\Filament\Resources\Suppliers\Pages\ManageSuppliers;
use App\Models\Supplier;
use App\Support\SickasFormatter;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Supplier';

    protected static ?string $modelLabel = 'Supplier';

    protected static ?string $pluralModelLabel = 'Supplier';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')
                ->label('Kode Supplier')
                ->maxLength(255),
            TextInput::make('name')
                ->label('Nama Supplier')
                ->maxLength(255)
                ->required(),
            TextInput::make('phone')
                ->label('No. HP')
                ->tel()
                ->maxLength(255),
            Textarea::make('address')
                ->label('Alamat')
                ->rows(3)
                ->columnSpanFull(),
            Textarea::make('notes')
                ->label('Catatan')
                ->rows(3)
                ->columnSpanFull(),
            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable(),
                TextColumn::make('name')->label('Nama Supplier')->searchable(),
                TextColumn::make('phone')->label('No. HP')->searchable()->visibleFrom('md'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('created_at')->label('Dibuat')->formatStateUsing(fn ($state): string => SickasFormatter::dateTime($state))->sortable()->toggleable(isToggledHiddenByDefault: true),
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

    public static function getPages(): array
    {
        return [
            'index' => ManageSuppliers::route('/'),
        ];
    }
}
