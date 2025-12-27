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
            SelectFilter::make('branch_id')->label('Branch')
                ->options(Branch::selectable()->forBranchManager('id')->pluck('name', 'id')),
            TrashedFilter::make(),
        ];
    }
}
