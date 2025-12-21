<?php

namespace App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource\Table;

use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource\Pages\ListEmployeeApplications;

use App\Models\ApplicationTransaction;
use App\Models\Branch;
use App\Models\EmployeeApplicationV2;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Exception;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Filters\TrashedFilter;
use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource;
use App\Models\Employee;
use Filament\Tables\Enums\FiltersLayout;

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
                ->limit(20)
                ->searchable(),

            TextColumn::make('createdBy.name')
                ->limit(20)
                ->sortable()
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
                ->icon('heroicon-m-check-badge')
                ->color(fn(string $state): string => match ($state) {
                    EmployeeApplicationV2::STATUS_PENDING  => 'warning',
                    EmployeeApplicationV2::STATUS_REJECTED => 'danger',
                    EmployeeApplicationV2::STATUS_APPROVED => 'success',
                }),
        ];

        // dd($activeTab,EmployeeApplicationV2::APPLICATION_TYPE_NAMES[1]);
        // أعمدة خاصة بإجازات (Leave request)
        if ($activeTab == EmployeeApplicationV2::APPLICATION_TYPE_NAMES[1]) {
            // dd(true);
            $columns[] = TextColumn::make('leave_type_name')
                ->label(__('lang.leave_type'));

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
                ->money('sar');

            $columns[] = TextColumn::make('detail_monthly_deduction_amount')
                ->label(__('lang.monthly_deduction'))
                ->money('sar');

            $columns[] = TextColumn::make('detail_deduction_starts_from')
                ->label(__('lang.deduction_starts'))
                ->date();

            $columns[] = TextColumn::make('detail_deduction_ends_at')
                ->label(__('lang.deduction_ends'))
                ->date();

            $columns[] = TextColumn::make('detail_number_of_months_of_deduction')
                ->label(__('lang.months'));
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
        }
        return $table->defaultSort('id', 'desc')
            ->paginated([10, 25, 50, 100])
            ->striped()
            ->columns($columns)
            // ->columns([
            //     TextColumn::make('id')
            //         ->sortable()
            //         ->searchable(),
            //     TextColumn::make('employee.name')
            //         ->sortable()->limit(20)
            //         ->searchable(),
            //     TextColumn::make('createdBy.name')->limit(20)
            //         ->sortable()->toggleable(isToggledHiddenByDefault: true)
            //         ->searchable(),
            //     TextColumn::make('application_date')->label('Request date')
            //         ->sortable(),
            //     // TextColumn::make('approvedBy.name')->label('Approved by')
            //     //     ->sortable(),
            //     // TextColumn::make('approved_at')->label('Approved at')
            //     //     ->sortable()
            //     // ,

            //     TextColumn::make('status')->label('Status')->alignCenter(true)
            //         ->badge()
            //         ->icon('heroicon-m-check-badge')
            //         ->color(fn(string $state): string    => match ($state) {
            //             EmployeeApplicationV2::STATUS_PENDING  => 'warning',
            //             EmployeeApplicationV2::STATUS_REJECTED => 'danger',
            //             EmployeeApplicationV2::STATUS_APPROVED => 'success',
            //         })
            //         ->toggleable(isToggledHiddenByDefault: false),
            //     TextColumn::make('application_type_id')
            //         ->label('Request Type')
            //         ->badge()
            //         ->formatStateUsing(function ($state) {
            //             return \App\Models\EmployeeApplicationV2::APPLICATION_TYPE_NAMES[$state] ?? 'Unknown';
            //         })
            //         ->sortable()
            //         ->toggleable(isToggledHiddenByDefault: false),
            // ])

            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')->options([
                    EmployeeApplicationV2::STATUS_PENDING  => EmployeeApplicationV2::STATUS_PENDING,
                    EmployeeApplicationV2::STATUS_REJECTED => EmployeeApplicationV2::STATUS_REJECTED,
                    EmployeeApplicationV2::STATUS_APPROVED => EmployeeApplicationV2::STATUS_APPROVED,
                ]),
                SelectFilter::make('employee_id')->label(__('lang.employee'))->searchable()
                    ->options(Employee::query()->forBranchManager()->select('name', 'id')->pluck('name', 'id')),
                SelectFilter::make('branch_id')
                    ->label(__('lang.branch'))
                    ->options(Branch::select('name', 'id')->selectable()->forBranchManager('id')->pluck('name', 'id')),
            ], FiltersLayout::Modal)
            ->recordActions([
                RestoreAction::make(),
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
                                    $fromDate     = Carbon::parse($details->start_date);
                                    $toDate       = Carbon::parse($details->end_date);
                                    $remaning     = $fromDate->diffInDays($toDate) + 1;
                                    $leaveBalance = LeaveBalance::where('leave_type_id', $details->leave_type)->where('employee_id', $record->employee_id)
                                        ->where('year', $details->year)
                                        ->where('month', $details->month)
                                        ->first();

                                    if (! is_null($leaveBalance)) {
                                        $leaveBalance->update([
                                            'balance' => $remaning + $leaveBalance?->balance,
                                        ]);
                                    }
                                    $record->delete();
                                    DB::commit();
                                    showSuccessNotifiMessage('done');
                                }
                            } catch (Exception $th) {
                                DB::rollBack();
                                throw $th;
                                return Notification::make()->title($th->getMessage())->warning()->send();
                            }
                            break;
                        case EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST:
                            $record->load([
                                'advanceRequest',
                            ]);
                            DB::beginTransaction();
                            try {
                                //code...
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
                                //code...
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
                                //code...
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

                        default:
                            # code...
                            break;
                    }
                }),
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
                        //throw $th;
                    }
                }),

                EmployeeApplicationResource::approveDepartureRequest()->hidden(function ($record) {
                    if (isstuff() || isFinanceManager()) {
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
                    if (isstuff() || isFinanceManager()) {
                        return true;
                    }
                    if (isset(Auth::user()->employee)) {
                        if ($record->employee_id == Auth::user()->employee->id) {
                            return true;
                        }
                    }
                    return false;
                }),

                EmployeeApplicationResource::approveAdvanceRequest()->hidden(function ($record) {
                    if (isstuff() || isFinanceManager()) {
                        return true;
                    }
                    if (isset(Auth::user()->employee)) {
                        if ($record->employee_id == Auth::user()->employee->id) {
                            return true;
                        }
                    }
                    return false;
                }),
                EmployeeApplicationResource::rejectAdvanceRequest()->hidden(function ($record) {
                    if (isstuff() || isFinanceManager()) {
                        return true;
                    }
                    if (isset(Auth::user()->employee)) {
                        if ($record->employee_id == Auth::user()->employee->id) {
                            return true;
                        }
                    }
                    return false;
                }),

                EmployeeApplicationResource::approveLeaveRequest()->hidden(function ($record) {
                    if (isstuff() || isFinanceManager()) {
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
                    if (isstuff() || isFinanceManager()) {
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
                    if (isstuff() || isFinanceManager()) {
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
                    if (isstuff() || isFinanceManager()) {
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
                    ->visible(fn($record): bool => ($record->application_type_id == EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST)),
                EmployeeApplicationResource::departureRequesttDetails()
                    ->visible(fn($record): bool => ($record->application_type_id == EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST)),

                EmployeeApplicationResource::advancedRequestDetails()
                    ->visible(fn($record): bool => ($record->application_type_id == EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST)),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
