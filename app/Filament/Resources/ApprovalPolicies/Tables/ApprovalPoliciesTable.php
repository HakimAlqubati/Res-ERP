<?php

namespace App\Filament\Resources\ApprovalPolicies\Tables;

use App\Models\AdvanceWage;
use App\Models\EmployeeApplicationV2;
use App\Models\EmployeeOvertime;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalMode;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalPolicy;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ApprovalPoliciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('branch')->withCount('policySteps'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('approvable_type')
                    ->label(__('Subject'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::approvableTypeLabel($state))
                    ->color(fn (?string $state): string => match ($state) {
                        EmployeeApplicationV2::class => 'info',
                        EmployeeOvertime::class => 'warning',
                        AdvanceWage::class => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('application_type_id')
                    ->label(__('Application Type'))
                    ->formatStateUsing(fn ($state): string => $state
                        ? (EmployeeApplicationV2::APPLICATION_TYPE_NAMES[(int) $state] ?? (string) $state)
                        : __('All'))
                    ->placeholder(__('All')),

                TextColumn::make('branch.name')
                    ->label(__('Branch'))
                    ->sortable()
                    ->searchable()
                    ->placeholder(__('Global')),

                TextColumn::make('policy_steps_count')
                    ->label(__('Route Steps'))
                    ->alignCenter()
                    ->sortable(),

                IconColumn::make('active')
                    ->label(__('Active'))
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('approvable_type')
                    ->label(__('Subject'))
                    ->options(self::approvableTypeOptions()),

                SelectFilter::make('application_type_id')
                    ->label(__('Employee Application Type'))
                    ->options(EmployeeApplicationV2::APPLICATION_TYPE_NAMES),

                SelectFilter::make('branch_id')
                    ->label(__('Branch'))
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('active')
                    ->label(__('Active')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function approvableTypeOptions(): array
    {
        return [
            EmployeeApplicationV2::class => __('Employee Applications'),
            EmployeeOvertime::class => __('Employee Overtime'),
            AdvanceWage::class => __('Advance Wages'),
        ];
    }

    private static function approvableTypeLabel(?string $state): string
    {
        return self::approvableTypeOptions()[$state] ?? class_basename((string) $state);
    }
}
