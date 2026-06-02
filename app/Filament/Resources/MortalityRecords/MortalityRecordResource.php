<?php

namespace App\Filament\Resources\MortalityRecords;

use App\Filament\Concerns\HasSickasPhotoUpload;
use App\Filament\Resources\MortalityRecords\Pages\ManageMortalityRecords;
use App\Models\SheepIncidentRecord;
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
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MortalityRecordResource extends Resource
{
    use HasSickasPhotoUpload;

    protected static ?string $model = SheepIncidentRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional Ternak';

    protected static ?string $navigationLabel = 'Kematian / Afkir';

    protected static ?string $modelLabel = 'Kematian / Afkir';

    protected static ?string $pluralModelLabel = 'Kematian / Afkir';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Kejadian')
                ->schema([
                    DatePicker::make('incident_date')->label('Tanggal Kejadian')->native(false)->required(),
                    Select::make('incident_type')
                        ->label('Jenis Kejadian')
                        ->options([
                            'dead' => 'Mati',
                            'lost' => 'Hilang',
                            'culled' => 'Afkir',
                            'sick' => 'Sakit',
                        ])
                        ->required(),
                    Select::make('fattening_batch_id')
                        ->label('Batch Penggemukan')
                        ->relationship('fatteningBatch', 'batch_code')
                        ->searchable()
                        ->preload(),
                    Select::make('sheep_id')
                        ->label('Ternak')
                        ->relationship('sheep', 'tag_number')
                        ->searchable()
                        ->preload(),
                    TextInput::make('head_count')->label('Jumlah Ekor')->numeric()->minValue(1)->default(1)->required(),
                    TextInput::make('reason')->label('Penyebab')->maxLength(255),
                    Textarea::make('notes')->label('Catatan')->rows(3)->columnSpanFull(),
                    self::photoUpload('photo_paths', 'Foto Kematian / Afkir', 'sickas-farm/kematian-afkir'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('incident_date')->label('Tanggal')->formatStateUsing(fn ($state): string => SickasFormatter::date($state))->sortable(),
                TextColumn::make('incident_type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'dead' => 'danger',
                        'lost' => 'warning',
                        'culled' => 'gray',
                        'sick' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'dead' => 'Mati',
                        'lost' => 'Hilang',
                        'culled' => 'Afkir',
                        'sick' => 'Sakit',
                        default => '-',
                    }),
                TextColumn::make('fatteningBatch.batch_code')->label('Batch')->searchable()->visibleFrom('md'),
                TextColumn::make('sheep.tag_number')->label('Ternak')->searchable()->visibleFrom('md'),
                TextColumn::make('head_count')->label('Jumlah')->formatStateUsing(fn ($state): string => SickasFormatter::number($state)),
                TextColumn::make('reason')->label('Penyebab')->searchable()->visibleFrom('lg'),
                self::photoColumn('photo_paths', 'Foto'),
            ])
            ->filters([
                Filter::make('incident_date')
                    ->label('Tanggal Kejadian')
                    ->schema([
                        DatePicker::make('from')->label('Dari Tanggal')->native(false),
                        DatePicker::make('until')->label('Sampai Tanggal')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('incident_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('incident_date', '<=', $date))),
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
            ->defaultSort('incident_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMortalityRecords::route('/'),
        ];
    }
}
