<?php

namespace App\Filament\Resources\EmployeeResource\Tables;


use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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

use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
use App\Models\AppLog;
use Maatwebsite\Excel\Facades\Excel;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;
use App\Rules\HR\Payroll\AdvanceWageLimitRule;
use App\Models\AdvanceWage;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use App\Services\HR\EmployeeBranchTransferService;

class EmployeeTable
{
    public static function configure(Table $table): Table

    {
        return $table->striped()
            ->paginated([10, 25, 50, 100])

            ->defaultSort('id', 'desc')
            ->recordUrl(fn(Employee $record): string => EmployeeResource::getUrl('view', ['record' => $record]))
            ->columns([
                SoftDeleteColumn::make(),
                TextColumn::make('id')
                    ->sortable()
                    ->label(__('lang.id'))->alignCenter()->toggleable(isToggledHiddenByDefault: true),
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
                    ->color(fn($record): string => $record->active ? 'primary' : 'warning')
                    // ->words(3)
                    ->limit(20)
                    ->weight(FontWeight::Medium)->tooltip(fn($state) => $state)
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
                TextColumn::make('branch_logs_count')
                    ->label(__('lang.branch_logs_count'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->counts('branchLogs')
                    ->alignCenter()
                    ->default(0),
                TextColumn::make('manager.name')
                    ->label(__('lang.manager'))
                    ->toggleable(isToggledHiddenByDefault: true)
                // ->searchable()
                ,

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
                TextColumn::make('serviceTermination.termination_date')
                    ->label(__('lang.termination_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('serviceTermination.termination_reason')
                    ->label(__('lang.termination_reason'))
                    ->limit(40)
                    ->tooltip(fn($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true),
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
                IconColumn::make('active')
                    ->label(__('lang.active'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
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
                TextColumn::make('created_at')
                    ->label(__('lang.created_at'))
                    ->dateTime()
                    ->sortable()
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

                    ->options([1 => __('lang.active'), 0 => __('lang.terminated')])->default(1)
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
                            try {
                                app(\App\Modules\HR\Employee\Services\EmployeeLifecycleService::class)->requestTermination($record, $data);

                                Notification::make()
                                    ->title(__('lang.termination_request_created'))
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title(__('lang.error_occurred'))
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
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
                        ->label(__('lang.approve_termination'))
                        ->color('success')
                        // ->requiresConfirmation()
                        ->action(function ($record) {
                            try {
                                app(\App\Modules\HR\Employee\Services\EmployeeLifecycleService::class)
                                    ->approveTermination($record->serviceTermination);

                                Notification::make()->title(__('lang.termination_approved_successfully'))->success()->send();
                            } catch (\Exception $e) {
                                Notification::make()->title(__('lang.error_occurred'))->body($e->getMessage())->danger()->send();
                            }
                        }),
                    Action::make('reject')
                        ->label(__('lang.reject_termination'))
                        ->color('danger')
                        ->schema([
                            Textarea::make('rejection_reason')->required()->label(__('lang.rejection_reason'))
                        ])
                        ->visible(fn(Employee $record) => $record->serviceTermination()->where('status', 'pending')->exists())

                        ->icon('heroicon-o-x-circle')
                        ->action(function (array $data, $record) {
                            try {
                                app(\App\Modules\HR\Employee\Services\EmployeeLifecycleService::class)
                                    ->rejectTermination($record->serviceTermination, $data);

                                Notification::make()->title(__('lang.termination_rejected_successfully'))->success()->send();
                            } catch (\Exception $e) {
                                Notification::make()->title(__('lang.error_occurred'))->body($e->getMessage())->danger()->send();
                            }
                        }),
                    Action::make('rehire')
                        ->label(__('lang.rehire'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->visible(fn(Employee $record) => !$record->active)
                        ->schema([
                            DatePicker::make('join_date')
                                ->label(__('lang.join_date'))
                                ->required()
                                ->default(now()),
                            Textarea::make('notes')
                                ->label(__('lang.notes')),
                        ])
                        ->action(function (Employee $record, array $data) {
                            try {
                                app(\App\Modules\HR\Employee\Services\EmployeeLifecycleService::class)->rehire($record, $data);

                                Notification::make()
                                    ->title(__('lang.employee_rehired_successfully'))
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title(__('lang.error_occurred'))
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

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
                    Action::make('advanceWage')
                        ->label(__('Advance Wage'))
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->visible(fn(Employee $record) => $record->active)
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('amount')
                                    ->label(__('Amount'))
                                    ->numeric()
                                    ->minValue(0.01)
                                    // ->maxValue(fn(Employee $record) => $record->salary ?? 99999)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->rules([
                                        fn(Get $get, Employee $record) => new AdvanceWageLimitRule(
                                            $record->id,
                                            $get('year'),
                                            $get('month')
                                        )
                                    ])
                                    ->columnSpan(1),

                                Select::make('year')
                                    ->label(__('Year'))
                                    ->options(collect(range(now()->year - 1, now()->year + 1))->mapWithKeys(fn($y) => [$y => $y]))
                                    ->default(now()->year)
                                    ->required()
                                    ->live()
                                    ->columnSpan(1),

                                Select::make('month')
                                    ->label(__('Month'))
                                    ->options(collect(range(1, 12))->mapWithKeys(fn($m) => [$m => now()->setMonth($m)->translatedFormat('F')]))
                                    ->default(now()->month)
                                    ->required()
                                    ->live()
                                    ->columnSpan(1),

                            ])->columnSpanFull(),

                            Grid::make(3)->schema([
                                Select::make('payment_method')
                                    ->label(__('lang.payment_method'))
                                    ->options(AdvanceWage::paymentMethods())
                                    ->default(AdvanceWage::PAYMENT_METHOD_CASH)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Employee $record) {
                                        if ($state === AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER) {
                                            $set('bank_account_number', $record->bank_account_number);
                                        }
                                    })
                                    ->columnSpan(1),

                                TextInput::make('bank_account_number')
                                    ->label(__('lang.bank_account_number'))
                                    ->visible(fn(Get $get) => $get('payment_method') === AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER)
                                    ->required(fn(Get $get) => $get('payment_method') === AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER)
                                    ->columnSpan(1),

                                TextInput::make('transaction_number')
                                    ->label(__('lang.transaction_number'))
                                    ->visible(fn(Get $get) => $get('payment_method') === AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER)
                                    ->required(fn(Get $get) => $get('payment_method') === AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER)
                                    ->columnSpan(1),
                            ])->columnSpanFull(),

                            TextInput::make('reason')
                                ->label(__('Reason'))->required()
                                ->maxLength(255)
                                ->columnSpanFull(),
                        ])
                        ->action(function (Employee $record, array $data) {
                            try {
                                $record->advanceWages()->create([
                                    'amount' => $data['amount'],
                                    'year' => $data['year'],
                                    'month' => $data['month'],
                                    'reason' => $data['reason'],
                                    'payment_method' => $data['payment_method'],
                                    'bank_account_number' => $data['bank_account_number'] ?? null,
                                    'transaction_number' => $data['transaction_number'] ?? null,
                                    'branch_id' => $record->branch_id,
                                ]);

                                Notification::make()
                                    ->title(__('Advance wage recorded successfully.'))
                                    ->success()
                                    ->send();
                            } catch (\Throwable $e) {
                                Notification::make()
                                    ->title(__('Error'))
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
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
                        ->fillForm(fn(Employee $record): array => $record->load('branchLogs')->toArray())
                        ->schema([
                            Tabs::make('Tabs')
                                ->columnSpanFull()
                                ->tabs([
                                    // Tab::make(__('lang.transfer_preview'))
                                    //     ->icon('heroicon-o-exclamation-triangle')
                                    //     ->schema([
                                    //         ViewField::make('_transfer_preview')
                                    //             ->view('components.employee.branch-transfer-preview')
                                    //             ->columnSpanFull()
                                    //     ]),
                                    Tab::make(__('lang.change_branch'))
                                        ->icon('heroicon-o-arrow-path')
                                        ->schema([
                                            Grid::make(3)
                                                ->schema([
                                                    Select::make('branch_id')
                                                        ->label(__('lang.select_new_branch'))
                                                        ->searchable()
                                                        ->options(Branch::query()
                                                            ->where('active', true)
                                                            ->pluck('name', 'id'))
                                                        ->required()
                                                        ->preload()
                                                        ->live()
                                                        ->rules([
                                                            fn(Get $get, Employee $record) => new \App\Rules\HR\Employee\BranchChangeRule(
                                                                $record->branch_id,
                                                                $record->id,
                                                                $get('start_at'),
                                                                $get('end_at')
                                                            )
                                                        ]),
                                                    DatePicker::make('start_at')
                                                        ->label(__('lang.start_date'))
                                                        ->default(now())
                                                        ->live()
                                                        ->required(),
                                                    DatePicker::make('end_at')
                                                        ->label(__('lang.end_date')),
                                                ])
                                        ]),
                                    Tab::make(__('lang.branch_logs_count'))
                                        ->icon('heroicon-o-list-bullet')
                                        ->schema([
                                            Repeater::make('branchLogs')
                                                ->relationship()
                                                ->table([
                                                    TableColumn::make(__('Branch'))->width('33%'),
                                                    TableColumn::make(__('Start Date'))->width('33%'),
                                                    TableColumn::make(__('End Date'))->width('33%'),
                                                    // TableColumn::make(__('Created By'))->width('30%'),
                                                ])
                                                ->schema([
                                                    Select::make('branch_id')
                                                        ->label(__('lang.branch'))
                                                        ->options(Branch::all()->pluck('name', 'id'))
                                                        ->disabled()
                                                        ->columnSpan(1),
                                                    DatePicker::make('start_at')
                                                        ->label(__('lang.start_date'))
                                                        ->disabled()
                                                        ->columnSpan(1),
                                                    DatePicker::make('end_at')
                                                        ->label(__('lang.end_date'))
                                                        ->disabled()
                                                        ->columnSpan(1),
                                                    TextInput::make('created_by')->hidden()
                                                        ->label(__('lang.created_by'))
                                                        ->formatStateUsing(fn($state, $record) => $record?->createdBy?->name ?? '-')
                                                        ->disabled()
                                                        ->columnSpan(1),
                                                ])
                                                ->columns(4)
                                                ->addable(false)
                                                ->deletable(false)
                                                ->reorderable(false)
                                                ->columnSpanFull()
                                        ]),
                                ])
                        ])
                        ->action(function (array $data, Employee $record) {
                            app(EmployeeBranchTransferService::class)->execute(
                                employee: $record,
                                newBranchId: (int) $data['branch_id'],
                                startAt: $data['start_at'],
                                endAt: $data['end_at'] ?? null,
                            );

                            Notification::make()
                                ->title(__('lang.success'))
                                ->body(__('lang.branch_changed_successfully') ?? 'Branch changed successfully')
                                ->success()
                                ->send();
                        }),
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
                        $activatedCount = 0;

                        foreach ($records as $record) {
                            try {
                                // Skip already active employees
                                if ($record->active == 1) {
                                    continue;
                                }

                                // activate employee
                                $record->update(['active' => 1]);
                                $activatedCount++;

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

                        showSuccessNotifiMessage("{$activatedCount} employees activated.");
                    }),
                ForceDeleteBulkAction::make()->visible(fn() => isSuperAdmin()),
            ]);
    }
}
