<?php

namespace App\Filament\Resources\Sheep\RelationManagers;

use App\Support\SickasFormatter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WeighingRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'weighingRecords';

    protected static ?string $title = 'Riwayat Timbang Per Ekor';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('weighed_at')
                ->label('Tanggal Timbang')
                ->native(false)
                ->required(),
            TextInput::make('weight_kg')
                ->label('Berat')
                ->numeric()
                ->minValue(0)
                ->suffix('kg')
                ->required(),
            Textarea::make('notes')
                ->label('Catatan')
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('weighed_at')
            ->modifyQueryUsing(fn ($query) => $query->where(function ($query): void {
                $query->where('weight_type', 'per_ekor')->orWhere('record_type', 'individual');
            }))
            ->columns([
                TextColumn::make('weighed_at')->label('Tanggal')->formatStateUsing(fn ($state): string => SickasFormatter::date($state))->sortable(),
                TextColumn::make('weight_kg')->label('Berat')->formatStateUsing(fn ($state): string => SickasFormatter::kg($state))->sortable(),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'actual_individual' => 'Aktual Per Ekor',
                        'estimated' => 'Estimasi',
                        default => 'Aktual',
                    })
                    ->color(fn (?string $state): string => $state === 'estimated' ? 'warning' : 'info'),
                TextColumn::make('notes')->label('Catatan')->limit(40),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Timbang')
                    ->mutateDataUsing(function (array $data): array {
                        $sheep = $this->getOwnerRecord();

                        $data['record_type'] = 'individual';
                        $data['weight_type'] = 'per_ekor';
                        $data['source'] = 'actual_individual';
                        $data['fattening_batch_id'] = $sheep->fattening_batch_id;

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
            ])
            ->defaultSort('weighed_at', 'desc');
    }
}
