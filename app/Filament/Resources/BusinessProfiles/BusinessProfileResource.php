<?php

namespace App\Filament\Resources\BusinessProfiles;

use App\Filament\Resources\BusinessProfiles\Pages\ManageBusinessProfile;
use App\Models\BusinessProfile;
use App\Support\SickasFormatter;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BusinessProfileResource extends Resource
{
    protected static ?string $model = BusinessProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Profil Usaha';

    protected static ?string $modelLabel = 'Profil Usaha';

    protected static ?string $pluralModelLabel = 'Profil Usaha';

    protected static ?string $recordTitleAttribute = 'business_name';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Usaha')
                ->schema([
                    TextInput::make('app_name')
                        ->label('Nama Aplikasi')
                        ->default('SICKAS FARM')
                        ->maxLength(255)
                        ->required(),
                    TextInput::make('business_name')
                        ->label('Nama Usaha')
                        ->default('SICKAS FARM')
                        ->maxLength(255)
                        ->required(),
                    TextInput::make('bumdes_name')
                        ->label('Nama BUMDes')
                        ->default('BUMDes Ketapang Ternak Domba')
                        ->maxLength(255)
                        ->required(),
                    TextInput::make('unit_name')
                        ->label('Nama Unit Usaha')
                        ->default('Ketapang Ternak Domba')
                        ->maxLength(255)
                        ->required(),
                    FileUpload::make('logo_path')
                        ->label('Logo Usaha')
                        ->image()
                        ->disk('public')
                        ->visibility('public')
                        ->directory('sickas-farm/profil-usaha')
                        ->acceptedFileTypes([
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ])
                        ->maxSize(2048)
                        ->imagePreviewHeight('120')
                        ->downloadable()
                        ->openable()
                        ->deleteUploadedFileUsing(fn (...$arguments): null => null)
                        ->helperText('Format JPG, JPEG, PNG, atau WEBP. Maksimal 2 MB.')
                        ->columnSpanFull(),
                    FileUpload::make('banner_path')
                        ->label('Background Banner Dashboard')
                        ->image()
                        ->disk('public')
                        ->visibility('public')
                        ->directory('sickas-farm/profil-usaha/banner')
                        ->acceptedFileTypes([
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ])
                        ->maxSize(4096)
                        ->imagePreviewHeight('180')
                        ->downloadable()
                        ->openable()
                        ->deleteUploadedFileUsing(fn (...$arguments): null => null)
                        ->helperText('Foto peternakan untuk banner dashboard dan halaman login. Format JPG, JPEG, PNG, atau WEBP. Maksimal 4 MB.')
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Alamat dan Kontak')
                ->schema([
                    Textarea::make('address')
                        ->label('Alamat')
                        ->rows(3)
                        ->columnSpanFull(),
                    TextInput::make('village')->label('Desa/Kelurahan')->maxLength(255),
                    TextInput::make('district')->label('Kecamatan')->maxLength(255),
                    TextInput::make('regency')->label('Kabupaten/Kota')->maxLength(255),
                    TextInput::make('province')->label('Provinsi')->maxLength(255),
                    TextInput::make('postal_code')->label('Kode Pos')->maxLength(255),
                    TextInput::make('phone')->label('Telepon / WhatsApp')->tel()->maxLength(255),
                    TextInput::make('email')->label('Email')->email()->maxLength(255),
                ])
                ->columns(2),
            Section::make('Legalitas dan Penanggung Jawab')
                ->schema([
                    TextInput::make('legal_number')
                        ->label('Nomor Legalitas')
                        ->maxLength(255),
                    TextInput::make('director_name')
                        ->label('Nama Direktur')
                        ->maxLength(255),
                    TextInput::make('unit_head_name')
                        ->label('Nama Kepala Unit')
                        ->maxLength(255),
                    Textarea::make('report_footer')
                        ->label('Catatan Footer Laporan')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Default Satuan')
                ->schema([
                    TextInput::make('default_currency')
                        ->label('Mata Uang Default')
                        ->default('IDR')
                        ->maxLength(10)
                        ->required(),
                    TextInput::make('default_weight_unit')
                        ->label('Satuan Berat Default')
                        ->default('kg')
                        ->maxLength(20)
                        ->required(),
                    TextInput::make('default_quantity_unit')
                        ->label('Satuan Jumlah Default')
                        ->default('ekor')
                        ->maxLength(20)
                        ->required(),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->oldest('id')->limit(1))
            ->columns([
                ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->disk('public')
                    ->square()
                    ->toggleable(),
                ImageColumn::make('banner_path')
                    ->label('Banner')
                    ->disk('public')
                    ->height(44)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('business_name')
                    ->label('Nama Usaha')
                    ->searchable(),
                TextColumn::make('bumdes_name')
                    ->label('BUMDes')
                    ->searchable()
                    ->visibleFrom('md'),
                TextColumn::make('unit_name')
                    ->label('Unit Usaha')
                    ->searchable()
                    ->visibleFrom('md'),
                TextColumn::make('phone')
                    ->label('Kontak')
                    ->searchable()
                    ->visibleFrom('lg'),
                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->formatStateUsing(fn ($state): string => SickasFormatter::dateTime($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make()->label('Detail'),
                EditAction::make()->label('Ubah'),
            ]);
    }

    public static function canCreate(): bool
    {
        return BusinessProfile::query()->doesntExist();
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBusinessProfile::route('/'),
        ];
    }
}
