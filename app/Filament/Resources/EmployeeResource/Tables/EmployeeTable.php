<?php

namespace App\Filament\Resources\EmployeeResource\Tables;


use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Filters\TrashedFilter;
use App\Exports\EmployeesExport;
use App\Imports\EmployeeImport;
use Throwable;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Filament\Resources\EmployeeResource\Pages\ListEmployees;
use App\Filament\Resources\EmployeeResource\Pages\CreateEmployee;
use App\Filament\Resources\EmployeeResource\Pages\EditEmployee;
use App\Filament\Clusters\HRCluster;
use App\Filament\Clusters\HRCluster\Resources\EmployeeResource\Pages\CheckInstallments;
use App\Filament\Clusters\HRCluster\Resources\EmployeeResource\Pages\OrgChart;
use App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers\BranchLogRelationManager;
use App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers\EmployeeFaceDataRelationManager;
use App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers\PeriodHistoriesRelationManager;
use App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers\PeriodRelationManager;
use App\Filament\Resources\EmployeeResource;
use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\Schemas\EmployeeForm;
use App\Filament\Tables\Columns\SoftDeleteColumn;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeFileType;
use App\Models\UserType;
use App\Services\S3ImageService;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use App\Models\EmployeeServiceTermination;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;


class EmployeeTable
{
    public static function configure(Table $table): Table

    {
        return $table->striped()
            ->paginated([10, 25, 50, 100])

            ->defaultSort('id', 'desc')
            ->columns([
                SoftDeleteColumn::make(),
                TextColumn::make('id')->label(__('lang.id'))->alignCenter()->toggleable(isToggledHiddenByDefault: true),
                // TextColumn::make('avatar_image')->copyable()->label('avatar_image')->alignCenter()->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('avatar_image')->label('')
                    ->circular(),
                TextColumn::make('avatar')->copyable()->label('avatar name')->toggleable(isToggledHiddenByDefault: true)->hidden(),
                TextColumn::make('employee_no')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('lang.employee_no'))->alignCenter()
                    ->sortable()->searchable()
                    ->searchable(isIndividual: false, isGlobal: true),

                TextColumn::make('name')
                    ->sortable()->searchable()
                    ->label(__('lang.full_name'))->wrap(false)
                    ->color('primary')->words(3)->limit(15)
                    ->weight(FontWeight::Bold)->tooltip(fn($state) => $state)
                    ->searchable(isIndividual: false, isGlobal: true)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('known_name')
                    ->sortable()->searchable()
                    ->label(__('lang.known_name'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('branch.name')
                    ->label(__('lang.branch'))
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('manager.name')
                    ->label(__('lang.manager'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('email')
                    ->icon('heroicon-m-envelope')
                    // ->copyable()
                    ->sortable()->searchable()
                    // ->limit(20)

                    ->default('-')
                    // ->tooltip(fn($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(isIndividual: false, isGlobal: true)
                    // ->copyable()
                    ->copyMessage(__('lang.email_address_copied'))
                    ->copyMessageDuration(1500)
                    ->color('primary')
                    ->weight(FontWeight::Bold),
                TextColumn::make('phone_number')->label(__('lang.phone_number'))
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->searchable(isIndividual: false)
                    ->default('_')
                    ->toggleable(isToggledHiddenByDefault: false)
                    // ->copyable()
                    ->copyMessage(__('lang.phone_number_copied'))
                    ->copyMessageDuration(1500)
                    ->color('primary')
                    ->weight(FontWeight::Bold),
                TextColumn::make('join_date')->sortable()->label(__('lang.start_date'))
                    ->sortable()->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(isIndividual: false, isGlobal: false),
                TextColumn::make('salary')->sortable()->label(__('lang.salary'))
                    ->sortable()->searchable()
                    // ->money(fn(): string => getDefaultCurrency())
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(isIndividual: false, isGlobal: false)->alignCenter(true),
                TextColumn::make('periodsCount')
                    ->default(0)

                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: true)

                    ->color('info') // لإظهار أن النص قابل للنقر
                // اختياري: أيقونة مشاهدة

                ,

                TextColumn::make('working_hours')->label(__('lang.working_hours'))->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(isIndividual: false, isGlobal: false)->alignCenter(true)
                    ->action(function ($record) {

                        $hoursCount = abs($record->hours_count);
                        $record->update([
                            'working_hours' => $hoursCount,
                        ]);
                    }),
                TextColumn::make('working_days')->label(__('lang.working_days'))->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(isIndividual: false, isGlobal: false)->alignCenter(true),
                TextColumn::make('position.title')->limit(20)
                    ->label(__('lang.position_type'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('job_title')
                    ->label(__('lang.job_title'))
                    ->sortable()->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(isIndividual: false, isGlobal: false),
                TextColumn::make('employeeType.name')
                    ->label(__('lang.role_type'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('department.name')
                    ->label(__('lang.department'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('unrequired_documents_count')->label(__('lang.unrequired_docs'))->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function ($state) {

                        return '(' . $state . ') docs of ' . EmployeeFileType::getCountByRequirement()['unrequired_count'];
                    }),
                TextColumn::make('required_documents_count')->label(__('lang.required_docs'))->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function ($state) {

                        return '(' . $state . ') docs of ' . EmployeeFileType::getCountByRequirement()['required_count'];
                    }),
                ToggleColumn::make('active')
                    ->label(__('lang.active'))
                    // ->boolean()
                    // ->getStateUsing(fn($record) => $record->active ?? true)
                    // ✅ يعامل null كـ true

                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('has_user')->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon(
                        Heroicon::XMark
                    )
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->url(function ($record) {
                        if ($record->user) {
                            return url('admin/users/' . $record?->user_id . '/edit');
                        }
                    })->openUrlInNewTab()
                    ->tooltip(__('lang.make_sure_user_not_soft_deleted'))
                    ->alignCenter(),
                TextColumn::make('rfid')
                    ->label('RFID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nationality')->sortable()->searchable()
                    ->label(__('lang.nationality'))
                    ->toggleable(isToggledHiddenByDefault: true)->alignCenter(true),
                TextColumn::make('gender_title')->sortable()
                    ->label(__('lang.gender'))
                    ->toggleable(isToggledHiddenByDefault: true)->alignCenter(true),
                IconColumn::make('is_citizen')
                    ->label(__('lang.is_citizen'))
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->toggleable(isToggledHiddenByDefault: true)->alignCenter(true),
                IconColumn::make('is_indexed_in_aws')
                    ->label(__('lang.is_indexed_in_aws'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: true),

            ])->deferFilters(true)
            ->filters([

                TrashedFilter::make()
                    ->visible(fn(): bool => (isSystemManager() || isSuperAdmin() || isBranchManager())),
                SelectFilter::make('branch_id')
                    ->searchable()
                    ->multiple()
                    ->label(__('lang.branch'))->options(Branch::active()->forBranchManager('id')->get()->pluck('name', 'id')->toArray()),
                SelectFilter::make('nationality')
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->label(__('lang.nationality'))
                    ->options(getNationalities()),
                SelectFilter::make('active')

                    ->options([1 => __('lang.active'), 0 => __('lang.inactive')])->default(1)
                    ->label(__('lang.active')),
                SelectFilter::make('employee_type')
                    ->label(__('lang.role_type'))
                    ->options(UserType::where('active', 1)->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->multiple(),
                SelectFilter::make('manager_id')
                    ->label(__('lang.manager'))
                    ->options(Employee::whereIn('employee_type', [1, 2])->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->multiple(),
                Filter::make('me')
                    ->label(__('lang.me'))
                    ->toggle()
                    ->query(fn($query) => $query->where('id', auth()->user()?->employee?->id)),
                Filter::make('my_employees')
                    ->label(__('lang.my_employees'))
                    ->toggle()
                    ->query(fn($query) => $query->where('manager_id', auth()->user()?->employee?->id)),
            ], FiltersLayout::Modal)
            ->filtersFormColumns(4)
            ->headerActions([
                Action::make('export_employees')
                    ->label(__('lang.export_to_excel'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('warning')
                    ->action(function () {
                        $data = Employee::where('active', 1)->select('id', 'employee_no', 'name', 'branch_id', 'job_title')->get();
                        return Excel::download(new EmployeesExport($data), 'employees.xlsx');
                    }),
                Action::make('export_employees_pdf')
                    ->label(__('lang.print_as_pdf'))
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->action(function () {
                        $data = Employee::where('active', 1)->select('id', 'employee_no', 'name', 'branch_id', 'job_title')->get();
                        $pdf  = PDF::loadView('export.reports.hr.employees.export-employees-as-pdf', ['data' => $data]);
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, 'employees.pdf');
                    }),

                Action::make('import_employees')
                    ->label(__('lang.import_from_excel'))
                    ->icon('heroicon-o-document-arrow-up')
                    ->visible(fn(): bool => isSystemManager() || isSuperAdmin())
                    ->schema([
                        FileUpload::make('file')
                            ->label(__('lang.select_excel_file')),
                    ])
                    // ->extraModalFooterActions([
                    //     Action::make('downloadexcel')->label(__('Download Example File'))
                    //         ->icon('heroicon-o-arrow-down-on-square-stack')
                    //         ->url(asset('data/sample_file_imports/Sample import file.xlsx')) // URL to the existing file
                    //         ->openUrlInNewTab(),
                    // ])
                    ->color('success')
                    // ->iconButton(Heroicon::AcademicCap)
                    ->action(function ($data) {

                        $file = 'public/' . $data['file'];
                        try {
                            // Create an instance of the import class
                            $import = new EmployeeImport;

                            // Import the file
                            Excel::import($import, $file);

                            // Check the result and show the appropriate notification
                            if ($import->getSuccessfulImportsCount() > 0) {
                                showSuccessNotifiMessage("Employees imported successfully {$import->getSuccessfulImportsCount()} rows added.");
                            } else {
                                showWarningNotifiMessage('No employees were added. Please check your file.');
                            }
                        } catch (Throwable $th) {
                            throw $th;
                            showWarningNotifiMessage('Error importing employees');
                        }
                    }),

            ])
            ->recordActions([







                ActionGroup::make([
                    Action::make('terminateService')
                        ->label(__('lang.terminate_service'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn(Employee $record) => $record->active && !$record->serviceTermination()->where('status', 'pending')->exists())
                        ->schema([
                            DatePicker::make('termination_date')
                                ->label(__('lang.termination_date'))
                                ->required()
                                ->default(now()),
                            Textarea::make('termination_reason')
                                ->label(__('lang.termination_reason'))
                                ->required(),
                            Textarea::make('notes')
                                ->label(__('lang.notes')),
                        ])
                        ->databaseTransaction()
                        ->action(function (Employee $record, array $data) {
                            $record->serviceTermination()->create([
                                'termination_date'   => $data['termination_date'],
                                'termination_reason' => $data['termination_reason'],
                                'notes'              => $data['notes'],
                                'status'             => \App\Models\EmployeeServiceTermination::STATUS_PENDING,
                            ]);

                            Notification::make()
                                ->title(__('lang.termination_request_created'))
                                ->success()
                                ->send();
                        }),

                    Action::make('manageTermination')
                        ->label(__('lang.manage_termination'))
                        ->icon('heroicon-o-clipboard-document-check')
                        ->color('warning')
                        ->visible(fn(Employee $record) => $record->serviceTermination()->where('status', 'pending')->exists())
                        ->schema(fn(Employee $record) => [
                            DatePicker::make('termination_date')
                                ->label(__('lang.termination_date'))
                                ->default($record->serviceTermination->termination_date)
                                ->disabled(),
                            Textarea::make('termination_reason')
                                ->label(__('lang.termination_reason'))
                                ->default($record->serviceTermination->termination_reason)
                                ->disabled(),
                            Textarea::make('notes')
                                ->label(__('lang.notes'))
                                ->default($record->serviceTermination->notes)
                                ->disabled(),
                        ])
                        ->modalSubmitAction(false)
                        ->modalFooterActions(fn(Employee $record, Action $action) => [
                            Action::make('approve')
                                ->label(__('lang.approve_termination'))
                                ->color('success')
                                ->requiresConfirmation()
                                ->action(function () use ($record, $action) {
                                    $record->serviceTermination->update([
                                        'status'      => \App\Models\EmployeeServiceTermination::STATUS_APPROVED,
                                        'approved_at' => now(),
                                        'approved_by' => auth()->id(),
                                    ]);
                                    Notification::make()->title(__('lang.termination_approved_successfully'))->success()->send();
                                    $action->close();
                                }),
                            Action::make('reject')
                                ->label(__('lang.reject_termination'))
                                ->color('danger')
                                ->schema([
                                    Textarea::make('rejection_reason')->required()->label(__('lang.rejection_reason'))
                                ])
                                ->action(function (array $data) use ($record, $action) {
                                    $record->serviceTermination->update([
                                        'status'           => \App\Models\EmployeeServiceTermination::STATUS_REJECTED,
                                        'rejected_at'      => now(),
                                        'rejected_by'      => auth()->id(),
                                        'rejection_reason' => $data['rejection_reason'] ?? null
                                    ]);
                                    Notification::make()->title(__('lang.termination_rejected_successfully'))->success()->send();
                                    $action->close();
                                }),
                                // $action->cancel(),
                        ]),
                    Action::make('createUser')
                        ->label(__('lang.create_user'))
                        ->icon('heroicon-o-user-plus')
                        ->color('success')
                        ->visible(fn($record) =>  !$record->has_user)
                        ->schema(fn($record) => EmployeeResource::createUserForm($record))
                        ->action(function (array $data, $record) {
                            $user = $record->createLinkedUser($data);

                            if ($user) {
                                Notification::make()
                                    ->title(__('lang.user_created'))
                                    ->body(__('lang.user_created_for') . " {$record->name}.")
                                    ->success()
                                    ->send();
                            }
                        }),
                    Action::make('index')
                        ->label(__('lang.aws_indexing'))
                        // ->button()
                        ->icon('heroicon-o-user-plus')
                        ->color('success')
                        ->requiresConfirmation(fn(Employee $record) => (bool) $record->is_indexed_in_aws)
                        ->modalHeading(__('lang.warning'))
                        ->modalDescription(__('lang.employee_already_indexed_warning'))
                        ->modalSubmitActionLabel(__('lang.yes'))
                        // ->visible(fn($record): bool => $record->avatar && Storage::disk('s3')->exists($record->avatar))
                        ->action(function ($record) {
                            $response = S3ImageService::indexEmployeeImage($record->id);

                            if (isset($response->original['success']) && $response->original['success']) {
                                Notification::make()
                                    ->title('Success')
                                    ->body($response->original['message'])
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Error')
                                    ->body($response->original['message'] ?? 'An error occurred.')
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Action::make('quick_edit_avatar')
                        ->label(__('lang.edit_avatar'))
                        ->icon('heroicon-o-camera')
                        ->color('secondary')
                        ->modalHeading(__('lang.edit_employee_avatar'))
                        ->schema([
                            EmployeeResource::avatarUploadField(),
                        ])
                        ->action(function (array $data, $record) {
                            $record->update([
                                'avatar' => $data['avatar'],
                            ]);
                            Notification::make()
                                ->title(__('lang.avatar_updated'))
                                ->body(__('lang.avatar_updated_successfully'))
                                ->success()
                                ->send();
                        }),
                    Action::make('checkInstallments')->label(__('lang.check_advanced_installments'))->button()
                        // ->hidden()
                        ->color('info')
                        ->icon('heroicon-m-banknotes')
                        ->url(fn($record) => CheckInstallments::getUrl(['employeeId' => $record->id]))

                        ->openUrlInNewTab()->hidden(),
                    Action::make('view_shifts')
                        ->label('View Shifts')
                        ->icon('heroicon-o-clock')
                        ->color('info')
                        ->modalHeading('Work Periods')
                        ->modalSubmitAction(false) // No submit button
                        ->modalCancelActionLabel('Close')
                        ->action(fn() => null) // No backend action
                        ->modalContent(function ($record) {
                            $periods = $record->periods;

                            if ($periods->isEmpty()) {
                                return view('components.employee.no-periods');
                            }

                            return view('components.employee.periods-preview', [
                                'periods' => $periods,
                            ]);
                        })->hidden(),
                    // Add the Change Branch action
                    Action::make('changeBranch')->icon('heroicon-o-arrow-path-rounded-square')
                        ->label(__('lang.change_branch')) // Label for the action button
                        ->visible(isSystemManager() || isSuperAdmin())
                        // ->icon('heroicon-o-annotation') // Icon for the button
                        ->modalHeading(__('lang.change_employee_branch')) // Modal heading
                        ->modalButton('Save')                    // Button inside the modal
                        ->schema([
                            Select::make('branch_id')
                                ->label(__('lang.select_new_branch'))
                                ->options(Branch::all()->pluck('name', 'id')) // Assuming you have a `Branch` model with `id` and `name`
                                ->required(),
                        ])
                        ->action(function (array $data, $record) {
                            // This is where you handle the logic to update the employee's branch and log the change

                            $newBranchId = $data['branch_id'];
                            $employee    = $record; // The current employee record

                            // Create the employee branch log
                            $employee->branchLogs()->create([
                                'employee_id' => $employee->id,
                                'branch_id'   => $newBranchId,
                                'start_at'    => now(),
                                'created_by'  => auth()->user()->id,
                            ]);

                            // Update the employee's branch
                            $employee->update([
                                'branch_id' => $newBranchId,
                            ]);
                        })->hidden(),
                    EditAction::make(),
                    ViewAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),
                ]),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                // ExportBulkAction::make(),
                RestoreBulkAction::make(),
                BulkAction::make('activate')
                    ->label(__('lang.activate'))
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (\Illuminate\Support\Collection $records) {
                        foreach ($records as $record) {
                            try {
                                // activate employee
                                $record->update(['active' => 1]);

                                // if employee has linked user, restore (if trashed) and activate user
                                if ($record->user_id) {
                                    $user = \App\Models\User::withTrashed()->find($record->user_id);
                                    if ($user) {
                                        if (method_exists($user, 'trashed') && $user->trashed()) {
                                            $user->restore();
                                        }
                                        $user->update(['active' => 1]);
                                    }
                                }
                            } catch (\Throwable $e) {
                                report($e);
                            }
                        }

                        showSuccessNotifiMessage('Selected employees activated.');
                    }),
                ForceDeleteBulkAction::make()->visible(fn() => isSuperAdmin()),
            ]);
    }
}
