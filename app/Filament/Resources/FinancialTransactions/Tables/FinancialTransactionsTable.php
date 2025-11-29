<?php

namespace App\Filament\Resources\FinancialTransactions\Tables;

use App\Enums\FinancialCategoryCode;
use App\Imports\FinancialTransactionsFromExcelImport;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use App\Models\FinancialTransaction;
use App\Models\FinancialCategory;
use App\Models\Branch;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class FinancialTransactionsTable
{
    public static function configure(Table $table): Table
    {

        //     $i = (new FinancialTransactionsFromExcelImport(branchId: 1))->parseDate(45963);
        //  dd($i);
        return $table->striped()->defaultSort('id', 'desc')
            ->paginated([10, 25, 50, 100])
            ->headerActions([
                Action::make('import_items_quantities')
                    ->label(__('lang.import'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->modalHeading('Import Items Quantities from Excel')
                    ->modalWidth('lg')
                    ->schema([
                        // 1) ملف الإكسل
                        FileUpload::make('file')
                            ->label('Upload Excel file')
                            ->required()
                            // ->acceptedFileTypes([
                            //     'application/vnd.ms-excel',
                            //     'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            //     '.xls',
                            //     '.xlsx',
                            //     '.csv',
                            // ])
                            ->disk('public')
                            ->directory('product_items_imports'),

                        // 2) بيانات رأس الاستيراد
                        Select::make('branch_id')->columnSpanFull()->label(__('lang.branch'))->searchable()
                            ->options(
                                Branch::query()
                                    ->branches()
                                    ->pluck('name', 'id')
                            )->required(),



                    ])
                    ->action(function (array $data) {
                        // مسار الملف على القرص العام
                        $fullPath = Storage::disk('public')->path($data['file']);

                        $branchId = $data['branch_id'];
                        // تهيئة المستورد مع رأس الاستيراد
                        $import = new FinancialTransactionsFromExcelImport(
                            branchId: (int) $branchId
                        );

                        try {
                            Excel::import($import, $fullPath);

                            $count = $import->getSuccessfulImportsCount();
                            showSuccessNotifiMessage("Imported successfully. Lines: {$count}");
                        } catch (Throwable $e) {
                            showWarningNotifiMessage("❌ Import failed: " . $e->getMessage());
                        }
                    })
                    ->requiresConfirmation(),
            ])
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Date')
                    ->date()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('month')
                    ->label('Month')
                    ->sortable()->alignCenter()
                    ->toggleable(),

                TextColumn::make('year')
                    ->label('Year')
                    ->sortable()->alignCenter()
                    ->toggleable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'success' => FinancialTransaction::TYPE_INCOME,
                        'danger' => FinancialTransaction::TYPE_EXPENSE,
                    ])
                    ->formatStateUsing(fn($state) => FinancialTransaction::TYPES[$state] ?? $state)
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Amount')
                    // ->money(getDefaultCurrency())
                    ->sortable()
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Sum::make()->label('')
                            ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state)),
                    ]),

                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => FinancialTransaction::STATUS_PAID,
                        'warning' => FinancialTransaction::STATUS_PENDING,
                        'danger' => FinancialTransaction::STATUS_OVERDUE,
                    ])
                    ->formatStateUsing(fn($state) => FinancialTransaction::STATUSES[$state] ?? $state)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('paymentMethod.name')
                    ->label('Payment Method')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reference_type')
                    ->label('Reference')
                    ->formatStateUsing(fn($state, $record) => $state ? class_basename($state) . ' #' . $record->reference_id : '-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(FinancialTransaction::TYPES)
                    ->label('Type'),

                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->options(FinancialTransaction::STATUSES)
                    ->label('Status'),

                Filter::make('transaction_date')
                    ->form([
                        DatePicker::make('from')
                            ->label('From Date'),
                        DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    }),

                TrashedFilter::make(),
            ], FiltersLayout::Modal)
            ->filtersFormColumns(4)
            ->recordActions([
                // EditAction::make()
                //     ->hidden(fn($record) => $record->reference_type !== null),
                ViewAction::make(),
                // DeleteAction::make()
                //     ->hidden(fn($record) => $record->reference_type !== null),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->reference_type === null) {
                                    $record->delete();
                                }
                            });
                        }),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
