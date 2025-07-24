<?php
namespace App\Filament\Resources;

use App\Filament\Clusters\HRCluster;
use App\Filament\Clusters\HRCluster\Resources\EmployeeResource\Pages\CheckInstallments;
use App\Filament\Clusters\HRCluster\Resources\EmployeeResource\Pages\OrgChart;
use App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers\BranchLogRelationManager;
use App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers\PeriodHistoriesRelationManager;
use App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers\PeriodRelationManager;
use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Allowance;
use App\Models\Branch;
use App\Models\Deduction;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeFileType;
use App\Models\EmployeeFileTypeField;
use App\Models\MonthlyIncentive;
use App\Models\Position;
use App\Models\UserType;
use App\Services\S3ImageService;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
// use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\Action as ActionsAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

// use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class EmployeeResource extends Resource
{
    protected static ?string $model                               = Employee::class;
    protected static ?string $navigationIcon                      = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster                             = HRCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort                         = 1;
    public static function getNavigationLabel(): string
    {
        return __('lang.employees');
    }
    public static function getPluralLabel(): ?string
    {
        return __('lang.employees');
    }

    public static function getLabel(): ?string
    {
        return __('lang.employees');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Wizard::make([
                    Wizard\Step::make('Personal & Employeement data')
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Fieldset::make('personal_data')->label('Personal data')

                                ->schema([
                                    Grid::make()->columns(3)->schema([
                                        TextInput::make('name')->label('Full name')
                                            ->rules([
                                                fn(): Closure => function (string $attribute, $value, Closure $fail) {
                                                    // dd('dd',$value);
                                                    if (count(explode(" ", $value)) < 2) {
                                                        $fail('The :attribute must be two words at least.');
                                                    }
                                                },
                                            ])
                                            ->columnSpan(1)->required(),
                                        TextInput::make('email')->columnSpan(1)->email()->unique(ignoreRecord: true),

                                        TextInput::make('phone_number')
                                            ->unique(ignoreRecord: true)
                                            ->columnSpan(1)

                                        // ->numeric()
                                            ->maxLength(14)->minLength(8),

                                        // PhoneInput::make('phone_number')
                                        //     // ->numeric()
                                        //     ->hidden()
                                        //     ->initialCountry('MY')
                                        //     ->onlyCountries([
                                        //         'MY',
                                        //         'US',
                                        //         'YE',
                                        //         'AE',
                                        //         'SA',
                                        //         'PK',
                                        //     ])
                                        //     ->displayNumberFormat(PhoneInputNumberType::E164)
                                        //     ->autoPlaceholder('aggressive')
                                        //     ->unique(ignoreRecord: true)
                                        //     ->validateFor(
                                        //         country: 'MY',
                                        //         lenient: true, // default: false
                                        //     ),
                                        Select::make('gender')
                                            ->label('Gender')
                                            ->options([
                                                1 => 'Male',
                                                0 => 'Female',
                                            ])
                                            ->required(),
                                        // TextInput::make('nationality')
                                        // ->label('Nationality')
                                        // ->nullable(),
                                        TextInput::make('working_hours')->label('Working hours')->numeric()->required()->default(6),

                                        Select::make('nationality')
                                            ->label('Nationality')->live()
                                            // ->required()
                                            ->options(getNationalities())
                                            ->searchable(),

                                        TextInput::make('mykad_number')->label('MyKad no.')->numeric()
                                            ->visible(fn($get): bool => ($get('nationality') != null && $get('nationality') == setting('default_nationality'))),

                                        Fieldset::make()->label('')
                                            ->visible(fn($get): bool => ($get('nationality') != null && $get('nationality') != setting('default_nationality')))
                                            ->schema([
                                                TextInput::make('passport_no')->label('Passport no.')->numeric(),
                                                Toggle::make('has_employee_pass')->label('Has employement pass')->inline(false)->live(),

                                            ]),

                                    ]),
                                    Fieldset::make()->label('Employee address')->schema([
                                        Textarea::make('address')->label('')->columnSpanFull(),
                                    ]),
                                    Fieldset::make()->label('Upload avatar image')
                                        ->columnSpanFull()
                                        ->schema([
                                            Grid::make()->columns(2)->schema([
                                                self::avatarUploadField(),
                                            ]),
                                        ]),
                                ]),
                            Fieldset::make('Employeement')->label('Employeement')
                                ->schema([
                                    Grid::make()->columns(4)->schema([
                                        TextInput::make('employee_no')->default((Employee::withTrashed()->latest()->first()?->id) + 1)->disabled()->columnSpan(1)->label('Employee number')->unique(ignoreRecord: true),
                                        TextInput::make('job_title')->columnSpan(1)->required(),
                                        Select::make('position_id')->columnSpan(1)->label('Position type')
                                            ->searchable()
                                            ->options(Position::where('active', 1)->select('id', 'title')->get()->pluck('title', 'id')),
                                        Select::make('employee_type')->columnSpan(1)->label('Role type')
                                            ->searchable()
                                            ->live()
                                            ->options(UserType::where('active', 1)->select('id', 'name')->get()->pluck('name', 'id')),

                                        Select::make('branch_id')->columnSpan(1)->label('Branch')
                                            ->searchable()
                                            ->required()
                                        // ->disabledOn('edit')
                                            ->live()
                                            ->options(Branch::where('active', 1)->select('id', 'name')->get()->pluck('name', 'id')),
                                        Toggle::make('is_ceo')->label('is_ceo')
                                            ->live()
                                            ->visible(fn($get): bool => $get('employee_type') == 1)
                                            ->default(0)->inline(false),
                                        Select::make('manager_id')
                                            ->columnSpan(1)
                                            ->label('Manager')
                                            ->searchable()
                                            ->requiredIf('is_ceo', false)
                                            ->options(function ($get) {
                                                $branchId = $get('branch_id');
                                                // if ($branchId) {
                                                return Employee::active()
                                                // ->forBranch($branchId)
                                                    ->pluck('name', 'id');
                                                // }
                                                return [];
                                            }),

                                        Select::make('department_id')
                                            ->columnSpan(1)
                                            ->label('Department')
                                            ->searchable()
                                            ->options(function ($get) {
                                                $branchId = $get('branch_id');
                                                // if ($branchId) {
                                                return Department::where('active', 1)
                                                // ->forBranch($branchId)
                                                    ->select('id', 'name')->get()->pluck('name', 'id');
                                                // }
                                                return Department::where('active', 1)
                                                    ->select('id', 'name')->get()->pluck('name', 'id');
                                            })->hidden(),
                                        DatePicker::make('join_date')
                                            ->default(now())
                                            ->columnSpan(1)->label('Start date')->required()
                                            ->maxDate(now()->toDateString()),

                                    ]),
                                ]),
                        ]),

                    Wizard\Step::make('Employee files')
                        ->icon('heroicon-o-document-plus')
                        ->schema([
                            Repeater::make('files')
                                ->relationship() // Define the relationship with the `files` table
                                ->columns(2)
                                ->defaultItems(0)
                                ->schema([
                                    Fieldset::make('File Details')->schema([
                                        Grid::make()->columns(2)->schema([
                                            Select::make('file_type_id')
                                                ->label('File Type')
                                                ->required()
                                                ->options(
                                                    EmployeeFileType::select('id', 'name')
                                                        ->where('active', 1)
                                                        ->get()
                                                        ->pluck('name', 'id')
                                                )
                                                ->searchable()
                                                ->reactive() // Makes the field reactive to changes
                                                ->afterStateUpdated(function ($state, $get, $set) {
                                                    if (is_numeric($state)) {
                                                        $dynamicFields = EmployeeFileTypeField::where('file_type_id', $state)->get();
                                                        $set('dynamic_fields', $dynamicFields->toArray());
                                                    } else {
                                                        $set('dynamic_fields', []);
                                                    }
                                                }),

                                            FileUpload::make('attachment')
                                                ->label('Attach File')
                                                ->downloadable()
                                                ->previewable()
                                            // ->required()
                                                ->imageEditor()
                                                ->circleCropper(),
                                        ]),
                                    ]),

                                    Fieldset::make('Additional Fields')
                                        ->schema(function (Get $get) {
                                            // Fetch the dynamic fields for the current file_type_id
                                            $fileTypeId = $get('file_type_id');
                                            if (! $fileTypeId) {
                                                return [];
                                            }

                                            $dynamicFields = EmployeeFileTypeField::where('file_type_id', $fileTypeId)->get();

                                            // Map the fields dynamically
                                            return $dynamicFields->map(function ($field) {
                                                return match ($field->field_type) {
                                                    'text'   => TextInput::make("dynamic_field_values.{$field->field_name}")
                                                        ->label(ucfirst(str_replace('_', ' ', $field->field_name)))
                                                        ->required(),
                                                    'number' => TextInput::make("dynamic_field_values.{$field->field_name}")
                                                        ->label(ucfirst(str_replace('_', ' ', $field->field_name)))
                                                        ->numeric()
                                                        ->required(),
                                                    'date'   => DatePicker::make("dynamic_field_values.{$field->field_name}")
                                                        ->label(ucfirst(str_replace('_', ' ', $field->field_name)))
                                                        ->required(),
                                                    default  => null,
                                                };
                                            })->filter()->toArray();
                                        }),
                                ])
                                ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {

                                    // dd($data['dynamic_field_values']);
                                    foreach ($data as &$file) {
                                        if (isset($file['dynamic_field_values'])) {
                                            $file['dynamic_field_values'] = json_encode($file['dynamic_field_values']);
                                        }
                                    }
                                    // dd($file);
                                    $data['dynamic_field_values'] = $file;
                                    //  dd($data);
                                    return $data;
                                }),

                        ]),

                    Wizard\Step::make('Finance')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Fieldset::make()->label('Set salary data and account number')->schema([
                                Grid::make()->label('')->columns(4)->schema([
                                    TextInput::make('salary')
                                        ->numeric()
                                        ->inputMode('decimal')->disabled(fn(): bool => isBranchManager()),
                                    TextInput::make('tax_identification_number')
                                        ->label('Tax Identification Number(TIN)')->required()
                                        ->visible(fn($get): bool => ($get('nationality') != null && ($get('nationality') == setting('default_nationality'))
                                            || ($get('has_employee_pass') == 1)
                                        ))
                                        ->numeric()
                                        ->disabled(fn(): bool => isBranchManager()),
                                    // TextInput::make('bank_account_number')
                                    //     ->columnSpan(2)
                                    //     ->label('Bank account number')->nullable(),
                                    Toggle::make('discount_exception_if_absent')->columnSpan(1)
                                        ->disabled(fn(): bool => isBranchManager())
                                        ->label('No salary deduction for absences')->default(0)->inline(false)
                                    // ->isInline(false)
                                    ,
                                    Toggle::make('discount_exception_if_attendance_late')->columnSpan(1)
                                        ->disabled(fn(): bool => isBranchManager())
                                        ->label('Exempt from late attendance deduction')->default(0)->inline(false)
                                    // ->isInline(false)
                                    ,

                                    Repeater::make('bank_information')
                                        ->disabled(fn(): bool => isBranchManager())
                                        ->label('Bank Information')
                                        ->columns(2)

                                        ->schema([
                                            TextInput::make('bank')
                                                ->label('Bank Name')
                                                ->required()
                                                ->placeholder('Enter bank name'),
                                            TextInput::make('number')
                                                ->label('Bank Account Number')
                                                ->required()
                                                ->placeholder('Enter bank account number'),
                                        ])
                                        ->collapsed()
                                        ->minItems(0)         // Set the minimum number of items
                                                          // Optional: set the maximum number of items
                                        ->defaultItems(1)     // Default number of items when the form loads
                                        ->columnSpan('full'), // Adjust the span as necessary
                                ]),
                                Fieldset::make()->label('Shift - RFID')->schema([
                                    Grid::make()->columns(2)->schema([
                                        // CheckboxList::make('periods')
                                        //     ->label('Work Periods')
                                        //     ->relationship('periods', 'name')

                                        //     ->columns(2)
                                        //     ->helperText('Select the employee\'s work periods.')

                                        // ,

                                        TextInput::make('rfid')->label('Employee RFID')
                                            ->unique(ignoreRecord: true),
                                    ]),
                                ]),
                                Fieldset::make()->columns(3)->label('Finance')
                                    ->disabled(fn(): bool => isBranchManager())
                                    ->schema([
                                        Repeater::make('Monthly deductions')

                                            ->defaultItems(0)
                                            ->relationship('deductions')
                                            ->schema([

                                                Select::make('deduction_id')
                                                    ->label('Deducation')
                                                    ->options(
                                                        Deduction::where('active', 1)
                                                            ->where('is_penalty', 0)
                                                            ->where('is_specific', 1)
                                                            ->get()->pluck('name', 'id')
                                                    )
                                                    ->required(),
                                                Toggle::make('is_percentage')->live()->default(true)
                                                // ->helperText('Set allowance as a salary percentage or fixed amount')
                                                ,
                                                TextInput::make('amount')->visible(fn(Get $get): bool => ! $get('is_percentage'))->numeric()
                                                    ->suffixIcon('heroicon-o-calculator')
                                                    ->suffixIconColor('success'),
                                                TextInput::make('percentage')->visible(fn(Get $get): bool => $get('is_percentage'))->numeric()
                                                    ->suffixIcon('heroicon-o-percent-badge')
                                                    ->suffixIconColor('success'),

                                            ]),
                                        Repeater::make('Monthly allowances')
                                            ->defaultItems(0)
                                            ->relationship('allowances')
                                            ->schema([

                                                Select::make('allowance_id')
                                                    ->label('Allowance')
                                                    ->options(Allowance::where('active', 1)->where('is_specific', 1)->get()->pluck('name', 'id'))
                                                    ->required(),
                                                Toggle::make('is_percentage')->live()->default(true)
                                                // ->helperText('Set allowance as a salary percentage or fixed amount')
                                                ,
                                                TextInput::make('amount')->visible(fn(Get $get): bool => ! $get('is_percentage'))->numeric()
                                                    ->suffixIcon('heroicon-o-calculator')
                                                    ->suffixIconColor('success'),
                                                TextInput::make('percentage')->visible(fn(Get $get): bool => $get('is_percentage'))->numeric()
                                                    ->suffixIcon('heroicon-o-percent-badge')
                                                    ->suffixIconColor('success'),

                                            ]),
                                        Repeater::make('Monthly bonus')
                                            ->defaultItems(0)
                                            ->label('Monthly bonus')
                                            ->relationship('monthlyIncentives')
                                            ->schema([

                                                Select::make('monthly_incentive_id')
                                                    ->label('Monthly bonus')
                                                    ->options(MonthlyIncentive::where('active', 1)->get()->pluck('name', 'id'))
                                                    ->required(),
                                                TextInput::make('amount')
                                                    ->default(0)->minValue(0)
                                                    ->numeric(),

                                            ]),

                                    ]),
                            ]),
                        ]),
                ])->columnSpanFull()->skippable(),

            ]);
    }

    public static function table(Table $table): Table
    {
        // $employee = Employee::with('periodDays.workPeriod')->find(1);
        // $res=[];
        // foreach ($employee->periodDays as $periodDay) {
        //     $period = $periodDay->workPeriod;
        //     $day    = $periodDay->day_of_week;

        //     $res[$day][] = "يعمل في الفترة: {$period->name} في يوم {$day}";
        // }
        // dd($res);

        // $sessionLifetime = config('session.lifetime');
        // dd($sessionLifetime);
        return $table->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'asc')
            ->columns([
                ImageColumn::make('avatar_image')->label('')
                    ->circular(),
                TextColumn::make('id')->label('id')->copyable()->hidden(),
                TextColumn::make('avatar')->copyable()->label('avatar name')->toggleable(isToggledHiddenByDefault: true)->hidden(),
                TextColumn::make('employee_no')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Employee No.')
                    ->sortable()->searchable()
                    ->searchable(isIndividual: false, isGlobal: false),

                TextColumn::make('name')
                    ->sortable()->searchable()
                    ->label('Full name')->wrap()
                    ->color('primary')
                    ->weight(FontWeight::Bold)
                    ->searchable(isIndividual: false, isGlobal: true)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: false),
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
                    ->falseIcon('heroicon-o-x-mark')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->url(function ($record) {
                        if ($record->user) {
                            return url('admin/users/' . $record?->user_id . '/edit');
                        }
                    })->openUrlInNewTab(),
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

            ])
            ->filters([

                Tables\Filters\TrashedFilter::make()
                    ->visible(fn(): bool => (isSystemManager() || isSuperAdmin())),
                SelectFilter::make('branch_id')
                    ->searchable()
                    ->multiple()
                    ->label(__('lang.branch'))->options(Branch::where('active', 1)->get()->pluck('name', 'id')->toArray()),
                SelectFilter::make('nationality')
                    ->searchable()
                    ->multiple()
                    ->label(__('Nationality'))
                    ->options(getNationalities()),
                SelectFilter::make('active')

                    ->options([1 => 'Active', 0 => 'Inactive'])
                    ->label('Active'),
            ], FiltersLayout::AboveContent)
            ->headerActions([
                ActionsAction::make('export_employees')
                    ->label('Export to Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('warning')
                    ->action(function () {
                        $data = Employee::where('active', 1)->select('id', 'employee_no', 'name', 'branch_id', 'job_title')->get();
                        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\EmployeesExport($data), 'employees.xlsx');
                    }),
                ActionsAction::make('export_employees_pdf')
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

                ActionsAction::make('import_employees')
                    ->label('Import from Excel')
                    ->icon('heroicon-o-document-arrow-up')
                    ->visible(fn(): bool => isSystemManager() || isSuperAdmin())
                    ->form([
                        FileUpload::make('file')
                            ->label('Select Excel file'),
                    ])->extraModalFooterActions([
                    ActionsAction::make('downloadexcel')->label(__('Download Example File'))
                        ->icon('heroicon-o-arrow-down-on-square-stack')
                        ->url(asset('storage/sample_file_imports/Sample import file.xlsx')) // URL to the existing file
                        ->openUrlInNewTab(),
                ])
                    ->color('success')
                    ->action(function ($data) {

                        $file = 'public/' . $data['file'];
                        try {
                            // Create an instance of the import class
                            $import = new \App\Imports\EmployeeImport;

                            // Import the file
                            Excel::import($import, $file);

                            // Check the result and show the appropriate notification
                            if ($import->getSuccessfulImportsCount() > 0) {
                                showSuccessNotifiMessage("Employees imported successfully. {$import->getSuccessfulImportsCount()} rows added.");
                            } else {
                                showWarningNotifiMessage('No employees were added. Please check your file.');
                            }
                        } catch (\Throwable $th) {
                            throw $th;
                            showWarningNotifiMessage('Error importing employees');
                        }
                    }),

            ])
            ->actions([

                ActionsAction::make('index')
                    ->label('AWS Indexing')->button()
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->visible(fn($record): bool => $record->avatar && Storage::disk('s3')->exists($record->avatar))
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

                ActionGroup::make([
                    
                ActionsAction::make('quick_edit_avatar')
                    ->label('Edit Avatar')
                    ->icon('heroicon-o-camera')
                    ->color('secondary')
                    ->modalHeading('Edit Employee Avatar')
                    ->form([
                        static::avatarUploadField(),
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
                    ActionsAction::make('checkInstallments')->label('Check Advanced installments')->button()->hidden()
                        ->color('info')
                        ->icon('heroicon-m-banknotes')
                        ->url(fn($record) => CheckInstallments::getUrl(['employeeId' => $record->id]))

                        ->openUrlInNewTab(),
                    ActionsAction::make('view_shifts')
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
                        }),
                    // Add the Change Branch action
                    \Filament\Tables\Actions\Action::make('changeBranch')->icon('heroicon-o-arrow-path-rounded-square')
                        ->label('Change Branch') // Label for the action button
                        ->visible(isSystemManager() || isSuperAdmin())
                                                             // ->icon('heroicon-o-annotation') // Icon for the button
                        ->modalHeading('Change Employee Branch') // Modal heading
                        ->modalButton('Save')                    // Button inside the modal
                        ->form([
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
                        }),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                // ExportBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PeriodRelationManager::class,
            PeriodHistoriesRelationManager::class,
            BranchLogRelationManager::class,
            // EmployeePeriodDaysRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'             => Pages\ListEmployees::route('/'),
            'create'            => Pages\CreateEmployee::route('/create'),
            'edit'              => Pages\EditEmployee::route('/{record}/edit'),
            'org_chart'         => OrgChart::route('/org_chart'),
                                                                                                 // 'view' => Pages\ViewEmployee::route('/{record}'),
            'checkInstallments' => CheckInstallments::route('/{employeeId}/check-installments'), // Pass employee ID here

        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ListEmployees::class,
            Pages\CreateEmployee::class,
            Pages\EditEmployee::class,
            // Pages\ViewEmployee::class,
        ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
        // ->where('role_id',8)
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    // public function canCreate(){
    //     return false;
    // }

    public static function canCreate(): bool
    {

        if (isSystemManager() || isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (isSystemManager() || isBranchManager() || isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function canDeleteAny(): bool
    {
        if (isSystemManager() || isBranchManager() || isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (isSuperAdmin() || isBranchManager() || isSystemManager() || isStuff() || isFinanceManager()) {
            return true;
        }
        return false;
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager()) {
            return true;
        }
        return false;
    }

    public static function avatarUploadField(): \Filament\Forms\Components\FileUpload
    {
        return FileUpload::make('avatar')
            ->image()
            ->label('')
        // ->avatar()
            ->imageEditor()

            ->circleCropper()
        // ->disk('public')
        // ->directory('employees')
            ->visibility('public')
            ->imageEditorAspectRatios([
                '16:9',
                '4:3',
                '1:1',
            ])
            ->disk('s3') // Change disk to S3
            ->directory('employees')
            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                return Str::random(15) . "." . $file->getClientOriginalExtension();
            })
        // ->imagePreviewHeight('250')
            ->resize(5)
            ->maxSize(333)
            ->columnSpan(2)
            ->reactive();
    }
}