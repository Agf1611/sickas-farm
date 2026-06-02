<?php

namespace App\Filament\Resources\SaleProposals\RelationManagers;

use App\Models\SaleProposal;
use App\Models\SaleProposalItem;
use App\Models\Sheep;
use App\Support\SickasFormatter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Ternak Dalam Ajuan';

    public function form(Schema $schema): Schema
    {
        /** @var SaleProposal $proposal */
        $proposal = $this->getOwnerRecord();

        return $schema->components([
            Select::make('sheep_id')
                ->label('Ternak')
                ->options(fn (): array => Sheep::query()
                    ->where('fattening_batch_id', $proposal->fattening_batch_id)
                    ->where('status', 'active')
                    ->orderBy('tag_number')
                    ->pluck('tag_number', 'id')
                    ->all())
                ->searchable()
                ->required(),
            TextInput::make('latest_weight_kg')
                ->label('Berat Terakhir')
                ->numeric()
                ->suffix('kg')
                ->helperText('Kosongkan agar sistem memakai berat terakhir ternak.'),
            TextInput::make('estimated_price')
                ->label('Estimasi Harga')
                ->numeric()
                ->prefix('Rp')
                ->helperText('Kosongkan agar sistem memakai harga pasaran.'),
            Textarea::make('notes')
                ->label('Catatan')
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sheep.tag_number')
            ->columns([
                TextColumn::make('sheep.tag_number')->label('Nomor Ternak')->searchable(),
                TextColumn::make('sheep.livestockType.name')->label('Jenis')->badge()->color('info'),
                TextColumn::make('latest_weight_kg')->label('Berat')->formatStateUsing(fn ($state): string => SickasFormatter::kg($state)),
                TextColumn::make('estimated_price')->label('Estimasi Harga')->formatStateUsing(fn ($state): string => SickasFormatter::rupiah($state))->alignEnd(),
                TextColumn::make('estimated_profit_loss')->label('Estimasi L/R')->formatStateUsing(fn ($state): string => SickasFormatter::rupiah($state))->alignEnd(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Ternak')
                    ->mutateDataUsing(fn (array $data): array => $data),
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
