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
use Filament\Forms\Components\FileUpload; 
use Filament\Forms\Components\Select; 
use Filament\Notifications\Notification; 
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon; 
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table; 
use Illuminate\Support\Facades\Log; 
use Maatwebsite\Excel\Facades\Excel;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;


class EmployeeTable
{
    public static function configure(Table $table): Table

    {
        return $table->striped()->deferFilters(false)
            ->paginated([10, 25, 50, 100])

            ->defaultSort('id', 'desc')
            ->columns([
                SoftDeleteColumn::make(),
                TextColumn::make('id')->label('ID')->alignCenter()->toggleable(isToggledHiddenByDefault: true),
                // TextColumn::make('avatar_image')->copyable()->label('avatar_image')->alignCenter()->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('avatar_image')->label('')
                    ->circular(),
                TextColumn::make('avatar')->copyable()->label('avatar name')->toggleable(isToggledHiddenByDefault: true)->hidden(),
                TextColumn::make('employee_no')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Employee No.')->alignCenter()
                    ->sortable()->searchable()
                    ->searchable(isIndividual: false, isGlobal: true),

                TextColumn::make('name')
                    ->sortable()->searchable()
                    ->label('Full name')->wrap(false)
                    ->color('primary')->words(3)->limit(15)
                    ->weight(FontWeight::Bold)->tooltip(fn($state) => $state)
                    ->searchable(isIndividual: false, isGlobal: true)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('manager.name')
                    ->label('Manager')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('email')->icon('heroicon-m-envelope')->copyable()
                    ->sortable()->searchable()->limit(20)->default('@')->tooltip(fn($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(isIndividual: false, isGlobal: true)
                    ->copyable()
                    ->copyMessage('Email address copied')
                    ->copyMessageDuration(1500)
                    ->color('primary')
                    ->weight(FontWeight::Bold),
                TextColumn::make('phone_number')->label('Phone')->searchable()->icon('heroicon-m-phone')->searchable(isIndividual: false)->default('_')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->copyable()
                    ->copyMessage('Phone number copied')
                    ->copyMessageDuration(1500)
                    ->color('primary')
                    ->weight(FontWeight::Bold),
                TextColumn::make('join_date')->sortable()->label('Start date')
                    ->sortable()->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(isIndividual: false, isGlobal: false),
                TextColumn::make('salary')->sortable()->label('Salary')
                    ->sortable()->searchable()
                    // ->money(fn(): string => getDefaultCurrency())
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(isIndividual: false, isGlobal: false)->alignCenter(true),
                TextColumn::make('periodsCount')
                    ->default(0)

                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: true)

                    ->color('info') // لإظهار أن النص قابل للنقر
                // اختياري: أيقونة مشاهدة

                ,

                TextColumn::make('working_hours')->label('Working hours')->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(isIndividual: false, isGlobal: false)->alignCenter(true)
                    ->action(function ($record) {

                        $hoursCount = abs($record->hours_count);
                        $record->update([
                            'working_hours' => $hoursCount,
                        ]);
                    }),
                TextColumn::make('working_days')->label('Working days')->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(isIndividual: false, isGlobal: false)->alignCenter(true),
                TextColumn::make('position.title')->limit(20)
                    ->label('Position type')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('job_title')
                    ->label('Job title')
                    ->sortable()->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(isIndividual: false, isGlobal: false),

                TextColumn::make('department.name')
                    ->label('Department')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('unrequired_documents_count')->label('Unrequired docs')->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function ($state) {

                        return '(' . $state . ') docs of ' . EmployeeFileType::getCountByRequirement()['unrequired_count'];
                    }),
                TextColumn::make('required_documents_count')->label('Required docs')->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function ($state) {

                        return '(' . $state . ') docs of ' . EmployeeFileType::getCountByRequirement()['required_count'];
                    }),
                ToggleColumn::make('active')->toggleable(isToggledHiddenByDefault: true),
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
                    ->tooltip('Make Sure If Liked User Is Not Soft Deleted')
                    ->alignCenter(),
                TextColumn::make('rfid')
                    ->label('RFID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nationality')->sortable()->searchable()
                    ->label('Nationality')
                    ->toggleable(isToggledHiddenByDefault: true)->alignCenter(true),
                TextColumn::make('gender_title')->sortable()
                    ->label('Gender')
                    ->toggleable(isToggledHiddenByDefault: true)->alignCenter(true),
                IconColumn::make('is_citizen')
                    ->label('Is citizen')
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->toggleable(isToggledHiddenByDefault: true)->alignCenter(true),

            ])->deferFilters(false)
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
                    ->label(__('Nationality'))
                    ->options(getNationalities()),
                SelectFilter::make('active')

                    ->options([1 => 'Active', 0 => 'Inactive'])->default(1)
                    ->label('Active'),
                SelectFilter::make('employee_type')
                    ->label('Role type')
                    ->options(UserType::where('active', 1)->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->multiple(),
            ], FiltersLayout::AboveContent)
            ->headerActions([
                Action::make('export_employees')
                    ->label('Export to Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('warning')
                    ->action(function () {
                        $data = Employee::where('active', 1)->select('id', 'employee_no', 'name', 'branch_id', 'job_title')->get();
                        return Excel::download(new EmployeesExport($data), 'employees.xlsx');
                    }),
                Action::make('export_employees_pdf')
                    ->label('Print as PDF')
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
                    ->label('Import from Excel')
                    ->icon('heroicon-o-document-arrow-up')
                    ->visible(fn(): bool => isSystemManager() || isSuperAdmin())
                    ->schema([
                        FileUpload::make('file')
                            ->label('Select Excel file'),
                    ])->extraModalFooterActions([
                        Action::make('downloadexcel')->label(__('Download Example File'))
                            ->icon('heroicon-o-arrow-down-on-square-stack')
                            ->url(asset('storage/sample_file_imports/Sample import file.xlsx')) // URL to the existing file
                            ->openUrlInNewTab(),
                    ])
                    ->color('success')
                    ->action(function ($data) {

                        $file = 'public/' . $data['file'];
                        try {
                            // Create an instance of the import class
                            $import = new EmployeeImport;

                            // Import the file
                            Excel::import($import, $file);

                            // Check the result and show the appropriate notification
                            if ($import->getSuccessfulImportsCount() > 0) {
                                showSuccessNotifiMessage("Employees imported successfully. {$import->getSuccessfulImportsCount()} rows added.");
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

                Action::make('createUser')->button()
                    ->label('Create User')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->visible(fn($record) =>  !$record->has_user)
                    ->form(fn($record) => static::createUserForm($record))
                    ->action(function (array $data, $record) {
                        $user = $record->createLinkedUser($data);

                        if ($user) {
                            Notification::make()
                                ->title('User Created')
                                ->body("A new user has been created for {$record->name}.")
                                ->success()
                                ->send();
                        }
                    }),


                Action::make('index')
                    ->label('AWS Indexing')->button()
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    // ->visible(fn($record): bool => $record->avatar && Storage::disk('s3')->exists($record->avatar))
                    ->action(function ($record) {
                        $response = S3ImageService::indexEmployeeImage($record->id);

                        if (isset($response->original['success']) && $response->original['success']) {
                            Log::info('Employee image indexed successfully.', ['employee_id' => $record->id]);
                            Notification::make()
                                ->title('Success')
                                ->body($response->original['message'])
                                ->success()
                                ->send();
                        } else {
                            Log::error('Failed to index employee image.', ['employee_id' => $record->id]);
                            Notification::make()
                                ->title('Error')
                                ->body($response->original['message'] ?? 'An error occurred.')
                                ->danger()
                                ->send();
                        }
                    }),

                // Action::make('add_face_images')
                //     ->label('Add Face Images')
                //     ->icon('heroicon-o-photo')
                //     ->color('primary')
                //     ->form([
                //         FileUpload::make('images')
                //             ->label('Face Images')
                //             ->multiple()
                //             ->required()->disk('public')
                //             ->image()
                //             ->maxSize(10240) // 10MB
                //             ->directory('employee_faces')
                //             ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                //                 return Str::random(15) . "." . $file->getClientOriginalExtension();
                //             })
                //         ,
                //     ])
                //     ->action(fn(array $data, $record) => static::storeFaceImages($record, $data['images']))
                //     ->modalHeading('Upload Employee Face Images')
                //     ->modalSubmitActionLabel('Upload')
                //     ->modalCancelActionLabel('Cancel'),
                ActionGroup::make([

                    Action::make('quick_edit_avatar')
                        ->label('Edit Avatar')
                        ->icon('heroicon-o-camera')
                        ->color('secondary')
                        ->modalHeading('Edit Employee Avatar')
                        ->form([
                            EmployeeResource::avatarUploadField(),
                        ])
                        ->action(function (array $data, $record) {
                            $record->update([
                                'avatar' => $data['avatar'],
                            ]);
                            Notification::make()
                                ->title('Avatar updated')
                                ->body('Employee avatar updated successfully.')
                                ->success()
                                ->send();
                        }),
                    Action::make('checkInstallments')->label('Check Advanced installments')->button()->hidden()
                        ->color('info')
                        ->icon('heroicon-m-banknotes')
                        ->url(fn($record) => CheckInstallments::getUrl(['employeeId' => $record->id]))

                        ->openUrlInNewTab(),
                    // Action::make('view_shifts')
                    //     ->label('View Shifts')
                    //     ->icon('heroicon-o-clock')
                    //     ->color('info')
                    //     ->modalHeading('Work Periods')
                    //     ->modalSubmitAction(false) // No submit button
                    //     ->modalCancelActionLabel('Close')
                    //     ->action(fn() => null) // No backend action
                    //     ->modalContent(function ($record) {
                    //         $periods = $record->periods;

                    //         if ($periods->isEmpty()) {
                    //             return view('components.employee.no-periods');
                    //         }

                    //         return view('components.employee.periods-preview', [
                    //             'periods' => $periods,
                    //         ]);
                    //     }),
                    // Add the Change Branch action
                    Action::make('changeBranch')->icon('heroicon-o-arrow-path-rounded-square')
                        ->label('Change Branch') // Label for the action button
                        ->visible(isSystemManager() || isSuperAdmin())
                        // ->icon('heroicon-o-annotation') // Icon for the button
                        ->modalHeading('Change Employee Branch') // Modal heading
                        ->modalButton('Save')                    // Button inside the modal
                        ->schema([
                            Select::make('branch_id')
                                ->label('Select New Branch')
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
            ]);
    }
}
