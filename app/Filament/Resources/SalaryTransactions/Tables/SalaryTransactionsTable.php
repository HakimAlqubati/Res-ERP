<?php

namespace App\Filament\Resources\SalaryTransactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SalaryTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table->striped()
            ->columns([
                TextColumn::make('employee.name')
                    ->label(__('Employee'))->toggleable()
                    ->sortable()
                    ->searchable(),
                // TextColumn::make('payroll')
                //     ->label(__('Payroll'))
                //     ->sortable()
                //     ->searchable(),
                // السنة والشهر
                TextColumn::make('year')->toggleable()
                    ->label(__('Year'))->alignCenter()
                    ->sortable()->toggleable(),

                TextColumn::make('month')->toggleable()->alignCenter()
                    ->label(__('Month'))->alignCenter()
                    ->formatStateUsing(function ($record) {
                        $months = getMonthArrayWithKeys();
                        $key = str_pad($record->month, 2, '0', STR_PAD_LEFT); // يحول 1 → 01 ، 9 → 09 ، 10 تبقى 10
                        return $months[$key] ?? '';
                    })->sortable(),

                // نوع العملية (خصم / إضافة)
                TextColumn::make('operation')->toggleable()
                    ->label(__('Operation'))->alignCenter()
                    ->badge()
                    ->color(fn($state) => $state === '+' ? 'success' : 'danger'),

                // النوع الفرعي (مثلاً غياب، إضافي، سلفة)
                TextColumn::make('type')->toggleable()
                    ->label(__('Type'))
                    // ->alignCenter()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('sub_type')
                    ->label(__('Sub Type'))->toggleable()
                    // ->alignCenter()
                    ->sortable()
                    ->searchable(),

                // المبلغ
                TextColumn::make('amount')->alignCenter()
                    ->label(__('Amount'))->toggleable()
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))->sortable(),

                // الكمية / المعدل (لو موجودة)
                // TextColumn::make('qty')
                //     ->label(__('Qty'))->alignCenter()
                //     ->numeric()
                //     ->sortable(),

                // TextColumn::make('rate')->alignCenter()
                //     ->label(__('Rate'))
                //     ->numeric()
                //     ->sortable(),

                // التاريخ
                TextColumn::make('date')->alignCenter()->toggleable()
                    ->label(__('Date'))->toggleable(isToggledHiddenByDefault: true)
                    ->date('Y-m-d')
                    ->sortable(),

                // // الحالة
                // TextColumn::make('status')->alignCenter()
                //     ->label(__('Status'))
                //     ->badge()
                //     ->colors([
                //         'warning' => 'pending',
                //         'success' => 'approved',
                //         'danger'  => 'rejected',
                //     ])
                //     ->sortable(),

                // الوصف
                TextColumn::make('description')
                    ->label(__('Description'))->toggleable()
                    ->limit(40)
                    ->toggleable(),
            ])->deferFilters(false)
            ->filters([
                TrashedFilter::make(),
                // تصفية حسب الموظف
                SelectFilter::make('employee_id')
                    ->label(__('Employee'))

                    ->options(function () {
                        return \App\Models\Employee::active()->orderBy('name')->pluck('name', 'id');
                    }),

                // السنة
                SelectFilter::make('year')
                    ->label(__('Year'))
                    ->options(array_combine(
                        range(date('Y') - 2, date('Y') + 1),
                        range(date('Y') - 2, date('Y') + 1)
                    )),

                // الشهر
                SelectFilter::make('month')
                    ->label(__('Month'))
                    ->options(config('constants.months')),


                // تصفية حسب نوع العملية (+ أو -)
                SelectFilter::make('operation')
                    ->label(__('Operation'))
                    ->options([
                        '+' => __('Addition'),
                        '-' => __('Deduction'),
                    ]),

                // تصفية حسب الحالة
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'pending'  => __('Pending'),
                        'approved' => __('Approved'),
                        'rejected' => __('Rejected'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                // EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
