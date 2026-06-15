<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource;

use App\Filament\Tables\Columns\SoftDeleteColumn;
use App\Models\Branch;
use App\Models\PayrollRun;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;

class PayrollTable
{
    /**
     * Get the table columns for PayrollResource.
     */
    public static function getColumns(): array
    {
        return [
            SoftDeleteColumn::make(),
            TextColumn::make('name')
                ->label('Name')->searchable()->sortable(),
            TextColumn::make('branch.name')
                ->label('Branch')->sortable(),
            TextColumn::make('year')
                ->sortable(),
            TextColumn::make('month')
                ->formatStateUsing(function ($record) {
                    $months = getMonthArrayWithKeys();
                    $key = str_pad($record->month, 2, '0', STR_PAD_LEFT);
                    return $months[$key] ?? '';
                })
                ->sortable(),
            TextColumn::make('employees_count')
                ->label(__('lang.employees_count'))
                ->alignCenter(),
            TextColumn::make('total_net')
                ->label('Net Salary')
                ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                ->sortable()
                ->summarize([
                        \Filament\Tables\Columns\Summarizers\Sum::make()->label('')
                            ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state)),
                    ])
                ,
            TextColumn::make('creator.name')
                ->label(__('Created By'))
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('approver.name')
                ->label(__('Approved By'))
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('created_at')
                ->label(__('Created At'))
                ->dateTime('Y-m-d H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('status')
                ->label(__('Status'))
                ->sortable()
                ->badge()
                ->formatStateUsing(fn($state) => PayrollRun::statuses()[$state] ?? $state)
                ->colors([
                    'warning' => PayrollRun::STATUS_PENDING,
                    'info'    => PayrollRun::STATUS_COMPLETED,
                    'success' => PayrollRun::STATUS_APPROVED,
                ]),
        ];
    }

    /**
     * Get the table filters for PayrollResource.
     */
    public static function getFilters(): array
    {
        return [
            TrashedFilter::make(),
            SelectFilter::make('branch_id')->label(__('Branch'))
                ->searchable()
                ->options(Branch::selectable()->forBranchManager('id')->pluck('name', 'id')),
            SelectFilter::make('year')
                ->label(__('Year'))
                ->options(array_combine(
                    range(date('Y') - 3, date('Y') + 1),
                    range(date('Y') - 3, date('Y') + 1)
                ))
                ->default(date('Y'))
                ,
            SelectFilter::make('month')
                ->label(__('Month'))
                ->options(getMonthArrayWithKeys())
                // ->default(date('m'))
                ,
            SelectFilter::make('status')
                ->label(__('Status'))
                ->options(PayrollRun::statuses()),
        ];
    }
}
