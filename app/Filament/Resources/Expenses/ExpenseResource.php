<?php

namespace App\Filament\Resources\Expenses;

use App\Filament\Concerns\HasSickasPhotoUpload;
use App\Filament\Resources\Expenses\Pages\ManageExpenses;
use App\Models\Expense;
use App\Services\ReportExportService;
use App\Support\SickasFormatter;
use BackedEnum;
use Filament\Actions\Action;
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

class ExpenseResource extends Resource
{
    use HasSickasPhotoUpload;

    protected static ?string $model = Expense::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Pengeluaran';

    protected static ?string $modelLabel = 'Pengeluaran';

    protected static ?string $pluralModelLabel = 'Pengeluaran';

    protected static ?string $recordTitleAttribute = 'description';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Pengeluaran')
                ->schema([
                    DatePicker::make('expense_date')->label('Tanggal Pengeluaran')->native(false)->required(),
                    Select::make('expense_category_id')
                        ->label('Kategori')
                        ->relationship('expenseCategory', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('description')->label('Keterangan')->maxLength(255)->required(),
                    TextInput::make('amount')->label('Nominal')->numeric()->prefix('Rp')->required(),
                ])
                ->columns(2),
            Section::make('Detail Tambahan')
                ->schema([
                    Select::make('fattening_batch_id')
                        ->label('Batch Penggemukan')
                        ->relationship('fatteningBatch', 'batch_code')
                        ->searchable()
                        ->preload(),
                    Select::make('pen_id')
                        ->label('Kandang')
                        ->relationship('pen', 'name')
                        ->searchable()
                        ->preload(),
                    Textarea::make('notes')->label('Catatan')->rows(3)->columnSpanFull(),
                    self::photoUpload('receipt_photo_paths', 'Foto Nota Pengeluaran', 'sickas-farm/nota-pengeluaran'),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('expense_date')->label('Tanggal')->formatStateUsing(fn ($state): string => SickasFormatter::date($state))->sortable(),
                TextColumn::make('expenseCategory.name')->label('Kategori')->searchable(),
                TextColumn::make('description')->label('Keterangan')->searchable()->visibleFrom('md'),
                TextColumn::make('fatteningBatch.batch_code')->label('Batch')->searchable()->visibleFrom('md'),
                TextColumn::make('pen.name')->label('Kandang')->searchable()->visibleFrom('lg'),
                TextColumn::make('amount')->label('Nominal')->formatStateUsing(fn ($state): string => SickasFormatter::rupiah($state))->sortable(),
                self::photoColumn('receipt_photo_paths', 'Foto Nota'),
            ])
            ->filters([
                Filter::make('expense_date')
                    ->label('Tanggal Pengeluaran')
                    ->schema([
                        DatePicker::make('from')->label('Dari Tanggal')->native(false),
                        DatePicker::make('until')->label('Sampai Tanggal')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('expense_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('expense_date', '<=', $date))),
            ])
            ->headerActions([
                Action::make('export_excel')
                    ->label('Export Excel')
                    ->color('success')
                    ->action(fn ($livewire) => app(ReportExportService::class)->downloadExpensesExcel([
                        'from' => $livewire->getTableFilterState('expense_date')['from'] ?? null,
                        'until' => $livewire->getTableFilterState('expense_date')['until'] ?? null,
                    ])),
                Action::make('export_pdf')
                    ->label('Export PDF')
                    ->color('danger')
                    ->action(fn ($livewire) => app(ReportExportService::class)->downloadExpensesPdf([
                        'from' => $livewire->getTableFilterState('expense_date')['from'] ?? null,
                        'until' => $livewire->getTableFilterState('expense_date')['until'] ?? null,
                    ])),
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
            ->defaultSort('expense_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageExpenses::route('/'),
        ];
    }
}
