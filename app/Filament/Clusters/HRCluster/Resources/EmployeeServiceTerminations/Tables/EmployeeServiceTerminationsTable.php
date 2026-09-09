<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeServiceTerminations\Tables;

use App\Filament\Tables\Actions\RefreshAction;
use App\Models\EmployeeServiceTermination;
use App\Modules\HR\Employee\Services\EmployeeLifecycleService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EmployeeServiceTerminationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
        ->defaultSort('id','desc')
        ->striped()
        ->deferLoading()
            ->columns([
                TextColumn::make('employee.name')
                    ->label(__('lang.employee'))
                    ->url(fn ($record) => \App\Filament\Resources\EmployeeResource::getUrl('view', ['record' => $record->employee_id]))
                    ->openUrlInNewTab()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('termination_date')
                    ->label(__('lang.termination_date'))
                    ->date()
                    ->sortable(),
             
                TextColumn::make('termination_reason')
                    ->label(__('lang.termination_reason'))
                    ->limit(50)
                    ->tooltip(fn($state)=>$state)
                    ->sortable(),
                       TextColumn::make('notes')
                    ->label(__('lang.notes'))
                    ->limit(50)
                    ->tooltip(fn($state)=>$state)
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('lang.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        EmployeeServiceTermination::STATUS_PENDING => 'warning',
                        EmployeeServiceTermination::STATUS_APPROVED => 'success',
                        EmployeeServiceTermination::STATUS_REJECTED => 'danger',
                        EmployeeServiceTermination::STATUS_CANCEL => 'secondary',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label(__('lang.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('lang.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()
                ->visible(fn (EmployeeServiceTermination $record) => $record->status === EmployeeServiceTermination::STATUS_PENDING)
                ,
                Action::make('manageTermination')
                    ->label(__('lang.approve'))
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('success')
                    ->visible(fn (EmployeeServiceTermination $record) => $record->status === EmployeeServiceTermination::STATUS_PENDING)
                    ->form(fn (EmployeeServiceTermination $record) => [
                        DatePicker::make('termination_date')
                            ->label(__('lang.termination_date'))
                            ->default($record->termination_date)
                            ->required()
                            ->live(),
                        Textarea::make('termination_reason')
                            ->label(__('lang.termination_reason'))
                            ->default($record->termination_reason)
                            ->required(),
                        Textarea::make('notes')
                            ->label(__('lang.notes'))
                            ->default($record->notes),
                    ])
                    ->action(function (EmployeeServiceTermination $record, array $data) {
                        try {
                            $record->update([
                                'termination_date' => $data['termination_date'],
                                'termination_reason' => $data['termination_reason'],
                                'notes' => $data['notes'] ?? null,
                            ]);

                            app(EmployeeLifecycleService::class)->approveTermination($record);

                            if (Carbon::parse($data['termination_date'])->isFuture()) {
                                Notification::make()
                                    ->title(__('lang.scheduled_for_auto_approval', [], 'Scheduled for auto-approval'))
                                    ->body(__('lang.auto_approval_body', [], 'The termination will be approved automatically on the termination date.'))
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()->title(__('lang.termination_approved_successfully'))->success()->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()->title(__('lang.error_occurred'))->body($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('reject')
                    ->label(__('lang.reject'))
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (EmployeeServiceTermination $record) => $record->status === EmployeeServiceTermination::STATUS_PENDING)
                    ->form([
                        Textarea::make('rejection_reason')->required()->label(__('lang.rejection_reason')),
                    ])
                    ->action(function (array $data, EmployeeServiceTermination $record) {
                        try {
                            app(EmployeeLifecycleService::class)->rejectTermination($record, $data);

                            Notification::make()->title(__('lang.termination_rejected_successfully'))->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title(__('lang.error_occurred'))->body($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->headerActions([RefreshAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
