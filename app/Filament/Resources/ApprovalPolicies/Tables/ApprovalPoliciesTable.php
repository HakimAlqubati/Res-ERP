<?php

namespace App\Filament\Resources\ApprovalPolicies\Tables;

use App\Models\Branch;
use App\Models\EmployeeApplicationV2;
use App\Modules\HR\ApprovalPolicies\Support\ApprovalSubjectRegistry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
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
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('policySteps'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('approvable_type')
                    ->label(__('Subject'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ApprovalSubjectRegistry::subjectTypeLabel($state))
                    ->color(fn (?string $state): string => ApprovalSubjectRegistry::subjectBadgeColor($state)),

                TextColumn::make('application_type_id')
                    ->label(__('Request Type'))
                    ->formatStateUsing(fn ($state): string => $state
                        ? (EmployeeApplicationV2::APPLICATION_TYPE_NAMES[(int) $state] ?? (string) $state)
                        : __('All'))
                    ->placeholder(__('All')),

                TextColumn::make('branch_ids')
                    ->label(__('Branches'))
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return __('Global');
                        }

                        static $branchNames = null;
                        if ($branchNames === null) {
                            $branchNames = Branch::pluck('name', 'id')->toArray();
                        }

                        return collect($state)->map(fn ($id) => $branchNames[$id] ?? $id)->join(', ');
                    })
                    ->badge()
                    ->wrap()
                    ->placeholder(__('Global')),

                TextColumn::make('policy_steps_count')
                    ->label(__('Route Steps'))
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),

                ToggleColumn::make('active')
                    ->label(__('Active'))
                    ->sortable()
                    ->toggleable(),

                ToggleColumn::make('allow_skip_steps')
                    ->label(__('Allow Skipping Steps'))
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('approvable_type')
                    ->label(__('Subject'))
                    ->options(ApprovalSubjectRegistry::subjectTypeOptions()),

                SelectFilter::make('application_type_id')
                    ->label(__('Employee Request Type'))
                    ->options(EmployeeApplicationV2::APPLICATION_TYPE_NAMES),

                SelectFilter::make('branch_ids')
                    ->label(__('Branch'))
                    ->options(fn () => Branch::pluck('name', 'id')->toArray())
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->whereJsonContains('branch_ids', (int) $data['value']);
                    })
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('active')
                    ->label(__('Active')),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
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
