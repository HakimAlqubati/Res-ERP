<?php

namespace App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource\Table;

use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource;
use App\Models\ApplicationTransaction;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeApplicationV2;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeApplicationTable
{
    public static function configure($table, ?string $activeTab = null)
    {

        $activeTab ??= EmployeeApplicationV2::APPLICATION_TYPE_NAMES[EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST];

        // الأعمدة المشتركة بين جميع الطلبات:
        $columns = [
            TextColumn::make('id')
                ->sortable()
                ->searchable(),

            TextColumn::make('employee.name')
                ->label(__('lang.employee'))
                ->sortable()
                ->url(fn ($record) => \App\Filament\Resources\EmployeeResource::getUrl('view', ['record' => $record->employee_id]),true)
                ->limit(20)
                ->tooltip(fn ($state) => $state)
                ->searchable(),

            TextColumn::make('createdBy.name')
                ->limit(20)
                ->sortable()
                  ->tooltip(fn ($state) => $state)
                ->toggleable(isToggledHiddenByDefault: true)
                ->searchable(),

            TextColumn::make('application_date')->toggleable(isToggledHiddenByDefault: true)
                ->label(__('lang.request_date'))
                ->sortable(),

            // TextColumn::make('application_type_name')
            //     ->label('Request Type')
            //     ->badge()
            //     ->sortable(),

            TextColumn::make('status')
                ->label(__('lang.status'))
                ->alignCenter(true)
                ->badge()
                ->sortable()
                ->icon('heroicon-m-check-badge')
                ->formatStateUsing(fn ($state) => EmployeeApplicationV2::getStatusLabel($state))
                ->color(fn (string $state): string => match ($state) {
                    EmployeeApplicationV2::STATUS_PENDING => 'warning',
                    EmployeeApplicationV2::STATUS_REJECTED => 'danger',
                    EmployeeApplicationV2::STATUS_APPROVED => 'success',
                }),
        ];

        // أعمدة خاصة بسلف الموظف (Advance request)
        if ($activeTab == EmployeeApplicationV2::APPLICATION_TYPE_NAMES[3]) {
            $columns[] = TextColumn::make('advanceRequest.finance_approved_at')
                ->label('Finance Approval')
                ->badge()
                ->alignCenter()
                ->formatStateUsing(fn ($state) => $state ? __('lang.approved') : __('lang.pending'))
                ->color(fn ($state) => $state ? 'success' : 'warning')
                ->icon(fn ($state) => $state ? 'heroicon-m-check-badge' : 'heroicon-m-clock')
                ;
        }
        // dd($activeTab,EmployeeApplicationV2::APPLICATION_TYPE_NAMES[1]);
        // أعمدة خاصة بإجازات (Leave request)
        if ($activeTab == EmployeeApplicationV2::APPLICATION_TYPE_NAMES[1]) {
            // dd(true);
            $columns[] = TextColumn::make('deleted_leave_type_name.name')
                ->label(__('lang.leave_type'))
                ->color(fn ($record) => ($record->deleted_leave_type_name['is_deleted'] ?? false) ? 'danger' : null)
                ->icon(fn ($record) => ($record->deleted_leave_type_name['is_deleted'] ?? false) ? 'heroicon-o-trash' : null);

            $columns[] = TextColumn::make('detail_from_date')
                ->label(__('lang.from'))
                ->date();

            $columns[] = TextColumn::make('detail_to_date')
                ->label(__('lang.to'))
                ->date();

            $columns[] = TextColumn::make('detail_days_count')
                ->label(__('lang.days'))->alignCenter()
                ->numeric();
        }

        // أعمدة خاصة بسلف الموظف (Advance request)
        if ($activeTab == EmployeeApplicationV2::APPLICATION_TYPE_NAMES[3]) {
            $columns[] = TextColumn::make('detail_advance_amount')
                ->label(__('lang.advance_amount'))
                ->formatStateUsing(fn ($state) => formatMoneyWithCurrency($state));

            $columns[] = TextColumn::make('detail_monthly_deduction_amount')
                ->label(__('lang.monthly_deduction'))
                ->toggleable(isToggledHiddenByDefault: true)
                ->formatStateUsing(fn ($state) => formatMoneyWithCurrency($state));

            $columns[] = TextColumn::make('detail_deduction_starts_from')
                ->label(__('lang.deduction_starts'))
                ->toggleable(isToggledHiddenByDefault: true)
                ->date('M Y');

            $columns[] = TextColumn::make('detail_deduction_ends_at')
                ->label(__('lang.deduction_ends'))
                ->toggleable(isToggledHiddenByDefault: true)
                ->date('M Y');

            $columns[] = TextColumn::make('detail_number_of_months_of_deduction')
                ->label(__('lang.months'))->alignCenter();
        }

        // أعمدة خاصة بطلب بصمة الحضور (Missed check-in)
        if ($activeTab == EmployeeApplicationV2::APPLICATION_TYPE_NAMES[2]) {
            $columns[] = TextColumn::make('detail_date')
                ->label(__('lang.date'));

            $columns[] = TextColumn::make('detail_time')

                ->label(__('lang.time'));
        }

        // أعمدة خاصة بطلب بصمة الانصراف (Missed check-out)
        if ($activeTab == EmployeeApplicationV2::APPLICATION_TYPE_NAMES[4]) {
            $columns[] = TextColumn::make('detail_date')
                ->label(__('lang.date'));

            $columns[] = TextColumn::make('detail_time')
                ->label(__('lang.time'));
            $columns[] = IconColumn::make('is_auto_generated')
                ->label(__('lang.auto'))
                ->alignCenter()
                ->toggleable(isToggledHiddenByDefault: true)
                ->sortable()
                ->boolean();

        }

        // أعمدة خاصة بطلب وجبات (Employee Meals Request)
        if ($activeTab == EmployeeApplicationV2::APPLICATION_TYPE_NAMES[5]) {
            $columns[] = TextColumn::make('application_date')->hidden();
            $columns[] = TextColumn::make('mealRequest.date')
                ->label(__('lang.date'))
                ->date();
            $columns[] = TextColumn::make('mealRequest.meal_details')
                ->label(__('lang.meal_details'))
                ->limit(50);
            $columns[] = TextColumn::make('mealRequest.branch.name')
                ->label(__('lang.branch'));

            $columns[] = TextColumn::make('mealRequest.cost')
                ->label(__('lang.cost'))
                ->formatStateUsing(fn ($state) => formatMoneyWithCurrency($state));
        }
        $columns[] = TextColumn::make('approvedBy.name')
            ->label(__('lang.approved_by'))
          ->tooltip(fn ($state) => $state)
          ->limit(20)
          ->toggleable(isToggledHiddenByDefault:false)
        ;
        
        $columns[] = TextColumn::make('rejectedBy.name')
            ->label(__('lang.rejected_by'))
          ->tooltip(fn ($state) => $state)
          ->limit(20)
          ->toggleable(isToggledHiddenByDefault:true)
        ;
        $columns[] = SpatieMediaLibraryImageColumn::make('images')
            ->label(__('lang.images'))
            ->collection('images')
            ->conversion('optimized')
            ->size(40)
            ->circular()
            ->toggleable(isToggledHiddenByDefault: true)
            ->alignCenter(true)
            ->limit(3);

        $columns[] = SpatieMediaLibraryImageColumn::make('files')
            ->label(__('lang.files'))
            ->collection('files')
            ->size(40)
            ->toggleable(isToggledHiddenByDefault: true)
            ->alignCenter(true)
            ->limit(3);
        $columns[] = TextColumn::make('branch.name')
            ->label(__('lang.branch'))
            ->searchable()
            ->default('-')
            ->toggleable(isToggledHiddenByDefault: true)
            ->alignCenter(false)
           ;

        return $table->defaultSort('id', 'desc')
            ->paginated([10, 25, 50, 100])
            ->striped()
            ->columns($columns)
            ->headerActions([
                Action::make('export_excel')
                    ->label(__('lang.export_to_excel') ?? 'Export Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function ($livewire) {
                        $records = $livewire->getFilteredTableQuery()->get();
                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new \App\Exports\AdvanceRequestsExport($records),
                            'advance_requests.xlsx'
                        );
                    })
                    ->visible($activeTab == EmployeeApplicationV2::APPLICATION_TYPE_NAMES[3])
            ])
            ->filters([
                TrashedFilter::make(),
                Filter::make('application_date')
                    ->form([
                        DatePicker::make('date_from')
                            ->label(__('lang.from'))
                        // ->default(today())
                        ,
                        DatePicker::make('date_to')
                            ->label(__('lang.to'))
                        // ->default(today())
                        ,
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('application_date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('application_date', '<=', $date),
                            );
                    }),
                SelectFilter::make('status')->options([
                    EmployeeApplicationV2::STATUS_PENDING => EmployeeApplicationV2::STATUS_PENDING,
                    EmployeeApplicationV2::STATUS_REJECTED => EmployeeApplicationV2::STATUS_REJECTED,
                    EmployeeApplicationV2::STATUS_APPROVED => EmployeeApplicationV2::STATUS_APPROVED,
                ]),
                SelectFilter::make('branch_id')
                    ->label(__('lang.branch'))
                    ->options(Branch::select('name', 'id')->selectable()
                        ->forBranchManager('id')->pluck('name', 'id')),
                SelectFilter::make('employee_id')->label(__('lang.employee'))->searchable()
                    ->options(Employee::query()->forBranchManager()->select('name', 'id')->pluck('name', 'id')),

            ], FiltersLayout::Modal)
            ->filtersFormColumns(4)
            ->recordActions([
                ActionGroup::make([
                    EmployeeApplicationResource::attachmentsAction(),

                    EmployeeApplicationResource::approveLeaveRequest()->hidden(function ($record) {
                        if (isstuff() || isHR()) {
                            return true;
                        }
                        if (isset(Auth::user()->employee)) {
                            if ($record->employee_id == Auth::user()->employee->id) {
                                return true;
                            }
                        }

                        return false;
                    }),
                    EmployeeApplicationResource::undoApproveLeaveRequest()->hidden(function ($record) {
                        if (isstuff() ||  isHR()) {
                            return true;
                        }
                        if (isset(Auth::user()->employee)) {
                            if ($record->employee_id == Auth::user()->employee->id) {
                                return true;
                            }
                        }

                        return false;
                    }),
                    EmployeeApplicationResource::rejectLeaveRequest()->hidden(function ($record) {
                        if (isstuff() ||  isHR()) {
                            return true;
                        }
                        if (isset(Auth::user()->employee)) {
                            if ($record->employee_id == Auth::user()->employee->id) {
                                return true;
                            }
                        }

                        return false;
                    }),
                    EmployeeApplicationResource::LeaveRequesttDetails()
                        ->visible(fn ($record): bool => ($record->application_type_id == EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST)),
                    EmployeeApplicationResource::departureRequesttDetails()
                        ->visible(fn ($record): bool => ($record->application_type_id == EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST)),
                    EmployeeApplicationResource::attendanceRequestDetails()
                        ->visible(fn ($record): bool => ($record->application_type_id == EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST)),

                    EmployeeApplicationResource::advancedRequestDetails()
                        ->visible(fn ($record): bool => ($record->application_type_id == EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST)),

                    EmployeeApplicationResource::exportAdvanceRequestPdf(),

                    EmployeeApplicationResource::advanceInstallmentsAction(),
                    EmployeeApplicationResource::approveAdvanceRequest()->hidden(function ($record) {
                        return false;
                    }),
                    EmployeeApplicationResource::rejectAdvanceRequest()->hidden(function ($record) {
                        return false;
                    }),
                    EmployeeApplicationResource::financeApproveAdvanceRequest()
                        ->hidden(function ($record) {
                            if (isFinanceManager() || isHR() || isSuperAdmin()) {
                                return false;
                            }

                            return true;
                        }),
                    EmployeeApplicationResource::financeRejectAdvanceRequest()->hidden(function ($record) {
                        if (isFinanceManager() || isHR() || isSuperAdmin()) {
                            return false;
                        }

                        return true;
                    }),
                    DeleteAction::make()->using(function ($record) {

                        $details = null;
                        switch ($record->application_type_id) {

                            case EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST:
                                $record->load([
                                    'leaveRequest',
                                ]);
                                DB::beginTransaction();
                                try {
                                    $details = $record->leaveRequest;
                                    // dd($details);
                                    if (! is_null($details)) {

                                        $record->delete();
                                        DB::commit();
                                        showSuccessNotifiMessage('done');
                                    }
                                } catch (Exception $th) {
                                    DB::rollBack();
                                    // throw $th;

                                    return Notification::make()->title($th->getMessage())->warning()->send();
                                }
                                break;
                            case EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST:
                                $record->load([
                                    'advanceRequest',
                                ]);
                                DB::beginTransaction();
                                try {
                                    // code...
                                    $record->delete();
                                    $record->advanceInstallments()->delete();
                                    $record->advanceRequest()->delete();
                                    showSuccessNotifiMessage('Done');
                                    DB::commit();
                                } catch (Exception $th) {
                                    showWarningNotifiMessage($th->getMessage());
                                    throw $th;
                                    DB::rollBack();
                                }
                                break;
                            case EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST:
                                $record->load([
                                    'missedCheckinRequest',
                                ]);
                                DB::beginTransaction();
                                try {
                                    // code...
                                    $record->delete();
                                    $record->missedCheckinRequest()->delete();
                                    showSuccessNotifiMessage('Done');
                                    DB::commit();
                                } catch (Exception $th) {
                                    showWarningNotifiMessage($th->getMessage());
                                    throw $th;
                                    DB::rollBack();
                                }
                                break;
                            case EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST:
                                $record->load([
                                    'missedCheckoutRequest',
                                ]);
                                // dd('sd', $record);
                                DB::beginTransaction();
                                try {
                                    // code...
                                    $record->delete();
                                    $record->missedCheckoutRequest()->delete();
                                    showSuccessNotifiMessage('Done');
                                    DB::commit();
                                } catch (Exception $th) {
                                    showWarningNotifiMessage($th->getMessage());
                                    throw $th;
                                    DB::rollBack();
                                }

                                break;
                            case EmployeeApplicationV2::APPLICATION_TYPE_MEAL_REQUEST:
                                $record->load(['mealRequest']);
                                DB::beginTransaction();
                                try {
                                    $record->delete();
                                    $record->mealRequest()->delete();
                                    showSuccessNotifiMessage('Done');
                                    DB::commit();
                                } catch (Exception $th) {
                                    showWarningNotifiMessage($th->getMessage());
                                    throw $th;
                                    DB::rollBack();
                                }
                                break;

                            default:
                                // code...
                                break;
                        }
                    }),
                ]),
                RestoreAction::make(),

                ForceDeleteAction::make()->using(function ($record) {
                    DB::beginTransaction();
                    try {
                        $transaction = ApplicationTransaction::where('application_id', $record->id)->whereIn('transaction_type_id', [1, 2, 3, 4])->first();
                        $record->forceDelete();
                        if ($transaction) {
                            $transaction->forceDelete();
                        }
                        DB::commit();
                    } catch (Exception $th) {
                        DB::rollBack();

                        return Notification::make()->title($th->getMessage())->warning()->send();
                        // throw $th;
                    }
                }),

                EmployeeApplicationResource::approveDepartureRequest()->hidden(function ($record) {
                    if (isstuff() ||  isHR()) {
                        return true;
                    }
                    if (isset(Auth::user()->employee)) {
                        if ($record->employee_id == Auth::user()->employee->id) {
                            return true;
                        }
                    }

                    return false;
                }),
                EmployeeApplicationResource::rejectDepartureRequest()->hidden(function ($record) {
                    if (isstuff() ||  isHR()) {
                        return true;
                    }
                    if (isset(Auth::user()->employee)) {
                        if ($record->employee_id == Auth::user()->employee->id) {
                            return true;
                        }
                    }

                    return false;
                }),
                EmployeeApplicationResource::undoApproveDepartureRequest()->hidden(function ($record) {
                    if (isstuff() ||  isHR()) {
                        return true;
                    }
                    if (isset(Auth::user()->employee)) {
                        if ($record->employee_id == Auth::user()->employee->id) {
                            return true;
                        }
                    }

                    return false;
                }),

                EmployeeApplicationResource::approveAttendanceRequest()->hidden(function ($record) {
                    // return false;
                    if (isstuff() || isHR()) {
                        return true;
                    }
                    if (isset(Auth::user()->employee)) {
                        if ($record->employee_id == Auth::user()->employee->id) {
                            return true;
                        }
                    }

                    return false;
                }),

                EmployeeApplicationResource::rejectAttendanceRequest()->hidden(function ($record) {
                    if (isstuff() ||  isHR()) {
                        return true;
                    }
                    if (isset(Auth::user()->employee)) {
                        if ($record->employee_id == Auth::user()->employee->id) {
                            return true;
                        }
                    }

                    return false;
                }),
                EmployeeApplicationResource::undoApproveAttendanceRequest()->hidden(function ($record) {
                    if (isstuff() ||  isHR()) {
                        return true;
                    }
                    if (isset(Auth::user()->employee)) {
                        if ($record->employee_id == Auth::user()->employee->id) {
                            return true;
                        }
                    }

                    return false;
                }),

                EmployeeApplicationResource::approveMealRequest()
                ->hidden(function ($record) {
                    if (isstuff() ||  isHR()) {
                        return true;
                    }
                    if (isset(Auth::user()->employee)) {
                        if ($record->employee_id == Auth::user()->employee->id) {
                            return true;
                        }
                    }

                    return false;
                }),
                EmployeeApplicationResource::rejectMealRequest()->hidden(function ($record) {
                    if (isstuff() ||  isHR()) {
                        return true;
                    }
                    if (isset(Auth::user()->employee)) {
                        if ($record->employee_id == Auth::user()->employee->id) {
                            return true;
                        }
                    }

                    return false;
                }),
                EmployeeApplicationResource::mealRequestDetails()
                    ->visible(fn ($record): bool => ($record->application_type_id == EmployeeApplicationV2::APPLICATION_TYPE_MEAL_REQUEST)),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
