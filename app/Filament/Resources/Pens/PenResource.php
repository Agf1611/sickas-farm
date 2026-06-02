<?php

namespace App\Filament\Resources\Pens;

use App\Filament\Concerns\HasSickasPhotoUpload;
use App\Filament\Resources\Pens\Pages\ManagePens;
use App\Models\Pen;
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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PenResource extends Resource
{
    use HasSickasPhotoUpload;

    protected static ?string $model = Pen::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Kandang';

    protected static ?string $modelLabel = 'Kandang';

    protected static ?string $pluralModelLabel = 'Kandang';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Kode Kandang')
                    ->maxLength(255)
                    ->required(),
                TextInput::make('name')
                    ->label('Nama Kandang')
                    ->maxLength(255)
                    ->required(),
                TextInput::make('capacity')
                    ->label('Kapasitas')
                    ->numeric(),
                TextInput::make('location')
                    ->label('Lokasi')
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Keterangan')
                    ->rows(3)
                    ->columnSpanFull(),
                self::photoUpload('condition_photo_paths', 'Foto Kondisi Kandang', 'sickas-farm/kandang'),
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
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nama Kandang')
                    ->searchable(),
                TextColumn::make('capacity')
                    ->label('Kapasitas')
                    ->formatStateUsing(fn (int|string|null $state): string => SickasFormatter::number($state))
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('location')
                    ->label('Lokasi')
                    ->searchable()
                    ->visibleFrom('md'),
                self::photoColumn('condition_photo_paths', 'Foto Kandang'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->formatStateUsing(fn ($state): string => SickasFormatter::dateTime($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->formatStateUsing(fn ($state): string => SickasFormatter::dateTime($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePens::route('/'),
        ];
    }
}
