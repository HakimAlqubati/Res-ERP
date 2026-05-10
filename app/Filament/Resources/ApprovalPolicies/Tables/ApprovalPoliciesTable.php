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
            ->modifyQueryUsing(fn (Builder $query) => $query->with('branch'))
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

                TextColumn::make('mode')
                    ->label(__('Mode'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::modeLabel($state))
                    ->color(fn (?string $state): string => match ($state) {
                        ApprovalMode::DIRECT_MANAGER => 'info',
                        ApprovalMode::BRANCH_MANAGER => 'success',
                        ApprovalMode::MANAGER_CHAIN => 'warning',
                        ApprovalMode::CUSTOM_USERS => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('levels')
                    ->label(__('Levels'))
                    ->getStateUsing(fn (ApprovalPolicy $record): string => $record->mode === ApprovalMode::MANAGER_CHAIN
                        ? ($record->levels ? (string) $record->levels : __('Full chain'))
                        : '-'),

                TextColumn::make('custom_approver_user_ids')
                    ->label(__('Custom Users'))
                    ->getStateUsing(fn (ApprovalPolicy $record): string => is_array($record->custom_approver_user_ids) && count($record->custom_approver_user_ids) > 0
                        ? (string) count($record->custom_approver_user_ids)
                        : '-')
                    ->alignCenter(),

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

                SelectFilter::make('mode')
                    ->label(__('Mode'))
                    ->options(self::modeOptions()),

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

    private static function modeOptions(): array
    {
        return [
            ApprovalMode::DIRECT_MANAGER => __('Direct Manager'),
            ApprovalMode::BRANCH_MANAGER => __('Branch Manager'),
            ApprovalMode::MANAGER_CHAIN => __('Manager Chain'),
            ApprovalMode::CUSTOM_USERS => __('Custom Users'),
        ];
    }

    private static function approvableTypeLabel(?string $state): string
    {
        return self::approvableTypeOptions()[$state] ?? class_basename((string) $state);
    }

    private static function modeLabel(?string $state): string
    {
        return self::modeOptions()[$state] ?? (string) $state;
    }
}
