<?php

namespace App\Filament\Resources\EmployeeResource\Schemas;

use App\Enums\HR\Payroll\SalaryAllocationRule;
use App\Filament\Forms\Components\PhoneInput;
use App\Models\Allowance;
use App\Models\Branch;
use App\Models\Deduction;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeFileType;
use App\Models\EmployeeFileTypeField;
use App\Models\EmployeePaymentMethod;
use App\Models\MonthlyIncentive;
use App\Models\Position;
use App\Models\User;
use App\Models\UserType;
use App\Modules\HR\Employee\Services\PassportValidationService;
use Carbon\Carbon;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EmployeeForm
{
    public static function configure(Schema $schema, $branchId = null): Schema
    {
        return $schema
            ->components([

                Wizard::make([
                    Step::make(__('lang.personal_data'))
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Grid::make(4)
                                ->columnSpanFull()
                                ->schema([

                                    Grid::make(3)
                                        ->columnSpan(3)
                                        ->schema([
                                            static::name(),

                                            TextInput::make('known_name')
                                                ->label(__('lang.known_name'))
                                                ->hint(__('lang.known_name_hint'))
                                                ->placeholder(__('lang.known_name_example'))
                                                ->unique(ignoreRecord: true)
                                                ->nullable()
                                                ->columnSpan(1),
                                            Fieldset::make()
                                                ->columnSpanFull()
                                                ->visible(fn ($record) => $record && ! $record->active)
                                                ->schema([
                                                    DatePicker::make('termination_date')
                                                        ->columnSpanFull()
                                                        ->label(__('lang.termination_date'))

                                                        ->disabled(),
                                                    Textarea::make('termination_reason')
                                                        ->label(__('lang.termination_reason'))
                                                        ->columnSpanFull()
                                                        ->disabled(),
                                                    Textarea::make('notes')
                                                        ->label(__('lang.notes'))
                                                        ->columnSpanFull()
                                                        ->disabled(),
                                                ]),

                                            TextInput::make('email')
                                                ->label(__('lang.email'))
                                                ->required()
                                                ->rule('email')
                                                ->unique(column: 'email', ignoreRecord: true)
                                                ->rules([
                                                    fn ($record) => function (string $attribute, $value, Closure $fail) use ($record) {
                                                        $query = User::where('email', $value)->withTrashed();
                                                        if ($record && $record->user_id) {
                                                            $query->where('id', '!=', $record->user_id);
                                                        }
                                                        if ($query->exists()) {
                                                            $fail("The email {$value} is already used by another user.");
                                                        }
                                                    },
                                                ])
                                                ->columnSpan(2),

                                            Select::make('nationality')
                                                ->label(__('lang.nationality'))->live()
                                                ->required()
                                                ->options(getNationalities())
                                                ->preload()
                                                ->searchable()
                                                ->columnSpan(1),

                                            PhoneInput::make('phone_number')
                                                ->label(__('lang.phone_number'))
                                                ->unique(ignoreRecord: true)
                                                ->columnSpan(1)
                                                // ->required()
                                                ->defaultCountry('my') // اليمن كدولة افتراضية
                                                // ->onlyCountries(['sa', 'ye', 'ae', 'my']) // حصر القائمة في السعودية، اليمن، والإمارات
                                                ->countryValidations([
                                                    'sa' => [
                                                        // السعودية: يجب أن يبدأ بـ 5
                                                        'starts_with' => ['+9665'],
                                                        'length' => 13,
                                                    ],
                                                    'my' => [
                                                        // ماليزيا: أرقام الجوال تبدأ بـ 1
                                                        'starts_with' => ['+601'],
                                                        'length' => [12, 13],
                                                    ],
                                                    'ye' => [
                                                        // اليمن: تحديد دقيق للشركات (77، 73، 71، 70) ومنع أرقام الهاتف الثابت
                                                        'starts_with' => ['+96777', '+96773', '+96771', '+96770'],
                                                        'length' => 13,
                                                    ],
                                                    [],
                                                ])
                                            // ->maxLength(18)->minLength(8)
                                            ,

                                            Select::make('gender')
                                                ->label(__('lang.gender'))
                                                ->options([
                                                    1 => __('lang.male'),
                                                    0 => __('lang.female'),
                                                ])
                                                ->required()
                                                ->columnSpan(1),

                                            DatePicker::make('birthday')
                                                ->label(__('lang.birthday'))
                                                ->nullable()
                                                ->columnSpan(1),

                                            TextInput::make('mykad_number')->label(__('lang.mykad_number'))->numeric()
                                                ->columnSpanFull()
                                                ->visible(fn ($get): bool => ($get('nationality') != null && $get('nationality') == setting('default_nationality'))),

                                            Fieldset::make()
                                                ->columnSpanFull()
                                                ->visible(fn ($get): bool => ($get('nationality') != null && $get('nationality') != setting('default_nationality')))
                                                ->schema([
                                                    TextInput::make('passport_no')->label(__('lang.passport_no'))
                                                        // ->numeric()
                                                        ->rules([
                                                            fn (Get $get, $record) => app(PassportValidationService::class)->rule(
                                                                $record?->id,
                                                                $get('nationality')
                                                            ),
                                                        ])
                                                        ->columnSpan(2),
                                                    Toggle::make('has_employee_pass')->label(__('lang.has_employee_pass'))->inline(false)->live()
                                                        ->columnSpan(1),
                                                ])->columns(3),

                                        ]),

                                    static::avatar()
                                        ->columnSpan(1),
                                ]),
                                   Fieldset::make(__('lang.emergency_contact'))
                                   ->columnSpanFull()
                                   ->columns(3)
                                ->schema([ 
                                        TextInput::make('emergency_number.name')
                                            ->label(__('lang.name'))
                                            ->required(false),
                                            
                                        PhoneInput::make('emergency_number.phone')
                                            ->label(__('lang.phone_number'))
                                            ->required(false)
                                            ->defaultCountry('my')
                                            ->onlyCountries(['sa', 'ye', 'ae', 'my'])
                                            ->countryValidations([
                                                'sa' => [
                                                    'starts_with' => ['+9665'],
                                                    'length' => 13,
                                                ],
                                                'my' => [
                                                    'starts_with' => ['+601'],
                                                    'length' => [12, 13],
                                                ],
                                                'ye' => [
                                                    'starts_with' => ['+96777', '+96773', '+96771', '+96770'],
                                                    'length' => 13,
                                                ],
                                            ]),
                                            
                                        TextInput::make('emergency_number.relation')
                                            ->label(__('lang.kinship'))
                                            ->nullable(),

                                ]),

                            Textarea::make('address')->label('')->columnSpanFull(),

                         
                        ]),

                    Step::make(__('lang.employment'))
                        ->icon(Heroicon::Identification)
                        ->schema([
                            Fieldset::make('Employeement')->label(__('lang.employment'))->columnSpanFull()
                                ->schema([
                                    Grid::make()->columns(4)->columnSpanFull()->schema([
                                        TextInput::make('employee_no')->default((Employee::withTrashed()->latest()->first()?->id) + 1)->disabled(false)->columnSpan(1)->label(__('lang.employee_number'))->unique(ignoreRecord: true),
                                        TextInput::make('job_title')->label(__('lang.job_title'))->columnSpan(1)->required(),
                                        Select::make('position_id')->columnSpan(1)->label(__('lang.position_type'))
                                            ->searchable()
                                            ->options(Position::where('active', 1)->select('id', 'title')->get()->pluck('title', 'id')),
                                        Select::make('employee_type')->columnSpan(1)->label(__('lang.role_type'))
                                            ->searchable()
                                            ->live()
                                            ->options(function () {
                                                return [0 => __('lang.all')] + UserType::where('active', 1)
                                                    ->select('id', 'name')
                                                    ->get()
                                                    ->pluck('name', 'id')
                                                    ->toArray();
                                            })
                                            ->required()
                                            ->default(0),

                                        Select::make('branch_id')->columnSpan(1)->label(__('lang.branch'))
                                            ->searchable()
                                            ->required()
                                            // ->disabledOn('edit')
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                // عند تغيير الفرع -> أفرغ قيمة owner_id
                                                $set('manager_id', null);
                                            })
                                            ->disabledOn('edit')
                                            ->default($branchId)
                                            ->hidden($branchId !== null)
                                            ->options(
                                                Branch::query()
                                                    ->select('id', 'name')
                                                    ->whereIn('type', [
                                                        Branch::TYPE_BRANCH,
                                                        Branch::TYPE_HQ,
                                                        Branch::TYPE_CENTRAL_KITCHEN
                                                    ])
                                                    ->get()
                                                    ->pluck('name', 'id')
                                            ),
                                        Toggle::make('is_ceo')->label(__('lang.is_ceo'))
                                            ->live()
                                            ->visible(
                                                fn ($get, ?Employee $record): bool => in_array((int) $get('employee_type'), [0, 1])
                                                //  &&
                                                //     (
                                                //         ($record && $record->is_ceo) ||
                                                // !Employee::where('is_ceo', true)->exists()
                                                //     )
                                            )
                                            ->default(0)->inline(false),
                                        Select::make('manager_id')
                                            ->columnSpan(1)
                                            ->label(__('lang.manager'))
                                            ->searchable()
                                            ->hidden(fn ($get) => $get('is_ceo'))
                                            // ->requiredIf('is_ceo', false)
                                            // ->required(fn(Get $get) => in_array((int) $get('employee_type'), [2, 3, 4]))
                                            ->required()
                                            ->options(function (Get $get, ?Employee $record) {
                                                $branchId = $get('branch_id');
                                                $employeeType = (int) $get('employee_type');
                                                $currentEmployeeId = $record?->id; // سيكون null في List/Create، ومتوفر في Edit/View

                                                if (in_array($employeeType, [0, 1, 2, 3])) {
                                                    // إذا كان نوع الموظف 2، يمكن اختيار المدراء من أي فرع بشرط أن يكونوا من نوع 1 أو 2
                                                    return Employee::active()
                                                        ->whereIn('employee_type', [1, 2, 0])
                                                        ->when(
                                                            $currentEmployeeId,
                                                            fn ($query) => $query->where('id', '!=', $currentEmployeeId) // استبعاد الموظف الحالي إن كنا في وضع التعديل
                                                        )
                                                        ->whereHas('user.roles', function ($query) {
                                                            $query->whereIn('roles.id', [3, 4, 14, 16, 15, 7]);
                                                        })
                                                        ->pluck('name', 'id');
                                                }

                                                if ($branchId) {
                                                    // للموظفين الآخرين، تصفية حسب الفرع الحالي وأن يكون المدير من نوع 1، 2، أو 3
                                                    return Employee::active()
                                                        ->forBranch($branchId)
                                                        ->whereIn('employee_type', [1, 2, 3, 0])
                                                        ->whereHas('user.roles', function ($query) {
                                                            $query->whereIn('roles.id', [3, 4, 7, 14, 16, 15]);
                                                        })
                                                        ->when(
                                                            $currentEmployeeId,
                                                            fn ($query) => $query->where('id', '!=', $currentEmployeeId) // استبعاد الموظف الحالي إن كنا في وضع التعديل
                                                        )
                                                        ->pluck('name', 'id');
                                                }

                                                return [];
                                            }),

                                        Select::make('department_id')
                                            ->columnSpan(1)
                                            ->label(__('lang.department'))
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
                                            ->columnSpan(1)->label(__('lang.start_date'))->required()
                                        // ->maxDate(now()->toDateString())
                                        ,

                                        TextInput::make('working_hours')
                                            ->label(__('lang.working_hours'))
                                            ->helperText('To Calculate the Hour Late')
                                            ->numeric()->required()->default(12),

                                        TextInput::make('working_days')
                                            ->label(__('lang.working_days_per_month'))
                                            ->numeric()
                                            ->default(26)
                                            ->minValue(1)->required()
                                            ->maxValue(31)
                                            ->extraInputAttributes(['onkeypress' => 'return event.charCode >= 48 && event.charCode <= 57'])
                                        // ->visible(fn() => Setting::getSetting('working_policy_mode') === 'custom_per_employee')
                                        ,
                                        Toggle::make('can_add_branch_order')->columnSpan(1)

                                            ->label(__('lang.can_add_branch_order'))->default(0)->inline(false),

                                        Toggle::make('allow_attendance_from_any_branch')->columnSpan(1)
                                            ->disabled(fn(): bool => isBranchManager())
                                            ->label(__('lang.allow_attendance_from_any_branch'))->default(0)->inline(false),

                                    ]),
                                ]),
                        ]),
                    Step::make(__('lang.employee_files'))
                        ->icon('heroicon-o-document-plus')
                        ->schema([
                            Repeater::make('files')
                                ->relationship() // Define the relationship with the `files` table
                                ->columns(2)
                                ->defaultItems(0)
                                ->table([
                                    TableColumn::make(__('lang.file_type'))->width('16rem'),
                                    TableColumn::make(__('lang.attachment'))->alignCenter()->width('16rem'),
                                    TableColumn::make(__('lang.fields'))->alignCenter()->width('10rem'),
                                ])
                                ->schema([
                                    Fieldset::make('File Details')->label(__('lang.file_details'))->columnSpanFull()->schema([
                                        Grid::make()->columns(2)->columnSpanFull()->schema([
                                            Select::make('file_type_id')
                                                ->label(__('lang.file_type'))
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
                                                ->label(__('lang.attach_file'))
                                                ->downloadable()
                                                ->previewable()
                                                ->maxSize(20000)
                                                // ->required()
                                                ->imageEditor()
                                                ->circleCropper(),
                                        ]),
                                    ]),

                                    Fieldset::make('Additional Fields')->label(__('lang.additional_fields'))->columnSpanFull()
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
                                                    'text' => TextInput::make("dynamic_field_values.{$field->field_name}")
                                                        ->label(ucfirst(str_replace('_', ' ', $field->field_name)))
                                                        ->required(),
                                                    'number' => TextInput::make("dynamic_field_values.{$field->field_name}")
                                                        ->label(ucfirst(str_replace('_', ' ', $field->field_name)))
                                                        ->numeric()
                                                        ->required(),
                                                    'date' => DatePicker::make("dynamic_field_values.{$field->field_name}")
                                                        ->label(ucfirst(str_replace('_', ' ', $field->field_name)))
                                                        ->required(),
                                                    default => null,
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

                    Step::make(__('lang.finance'))
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Fieldset::make()->label(__('lang.set_salary_data'))
                                ->columnSpanFull()
                                ->schema([
                                    Grid::make()->columns(4)->columnSpanFull()->schema([

                                        Section::make()->columns(1)->columnSpan(2)->schema([
                                            TextInput::make('salary')
                                                ->label(__('lang.salary'))
                                                ->numeric()
                                                ->inputMode('decimal')
                                                ->disabled(
                                                    fn (): bool => isBranchManager() && ! (isSuperAdmin()
                                                        || isSystemManager())
                                                )
                                                ->hidden(fn (string $operation) => isHR() && $operation !== 'create'),

                                            Select::make('salary_allocation_rule')
                                                ->label(__('Salary Allocation Override (Branch Transfers)'))
                                                ->helperText(__('Overrides the default system rule for this specific employee when transferred between branches.'))
                                                ->options(SalaryAllocationRule::class)
                                                ->placeholder(__('Use System Default')) // Fallback to system general setting
                                                ->columnSpan(1),

                                            TextInput::make('tax_identification_number')
                                                ->label(__('lang.tax_identification_number'))->required()
                                                ->visible(fn ($get): bool => ($get('nationality') != null && ($get('nationality') == setting('default_nationality'))
                                                    || ($get('has_employee_pass') == 1)
                                                ))
                                                ->numeric(),

                                            TextInput::make('max_weekly_leave_days')
                                                ->label(__('lang.max_weekly_leave_days'))
                                                ->helperText(__('lang.max_weekly_leave_days_hint'))
                                                ->numeric()
                                                ->integer()
                                                ->minValue(1)
                                                ->maxValue(31)
                                                ->nullable()
                                                ->placeholder('4')
                                                ->columnSpan(1)
                                                ->visible(fn (Get $get): bool => (bool) $get('has_auto_weekly_leave')),

                                        ]),
                                        Section::make()->columns(1)
                                            ->columnSpan(2)
                                            ->schema([
                                                Select::make('payment_method_id')
                                                    ->columnSpanFull()
                                                    ->label(__('lang.payment_method'))
                                                    ->relationship('paymentMethod', 'name')
                                                    ->preload()
                                                    ->searchable()
                                                    ->nullable()
                                                    ->live(),
                                                Group::make([
                                                    TextInput::make('payment_details.account_name')
                                                        ->label(fn (Get $get) => EmployeePaymentMethod::find($get('payment_method_id'))?->getAccountNameLabel() ?? __('Account Name'))
                                                        ->required(),

                                                    TextInput::make('payment_details.account_number')
                                                        ->label(fn (Get $get) => EmployeePaymentMethod::find($get('payment_method_id'))?->getAccountNumberLabel() ?? __('Account Number'))
                                                        ->required()
                                                        ->live(onBlur: true)
                                                        ->afterStateUpdated(fn ($state, $set) => $set('bank_account_number', $state)),

                                                    TextInput::make('payment_details.full_name')
                                                        ->label(__('Full Name'))
                                                        ->nullable(),

                                                    TextInput::make('payment_details.note')
                                                        ->label(fn (Get $get) => EmployeePaymentMethod::find($get('payment_method_id'))?->getNoteLabel() ?? __('Remarks'))
                                                        ->columnSpanFull(),
                                                ])
                                                    ->columns(1)
                                                    ->visible(fn (Get $get) => EmployeePaymentMethod::find($get('payment_method_id'))?->requiresDetails() ?? false),
                                                TextInput::make('bank_account_number')
                                                    ->hidden(),
                                            ]),

                                        Toggle::make('discount_exception_if_absent')->columnSpan(1)

                                            ->label(__('lang.no_salary_deduction_for_absences'))->default(0)->inline(false)
                                        // ->isInline(false)
                                        ,
                                        Toggle::make('discount_exception_if_attendance_late')->columnSpan(1)

                                            ->label(__('lang.exempt_from_late_attendance_deduction'))->default(0)->inline(false)
                                        // ->isInline(false)
                                        ,

                                        Toggle::make('has_auto_weekly_leave')->columnSpan(1)
                                            ->label(__('lang.has_auto_weekly_leave'))->default(1)->inline(false)->live(),

                                    ]),

                                    Grid::make(3)->columnSpanFull()
                                        ->schema([
                                            Fieldset::make('Monthly allowances')
                                                ->label(__('lang.monthly_allowances'))
                                                ->columnSpan(1)
                                                ->schema([
                                                    Repeater::make('Monthly allowances')
                                                        ->hiddenLabel()
                                                        ->defaultItems(0)
                                                        ->columnSpanFull()
                                                        ->columns(['default' => 3])
                                                        ->table([
                                                            TableColumn::make(__('lang.allowance'))->width('10rem'),
                                                            TableColumn::make(__('lang.type'))->alignCenter()->width('6rem'),
                                                            TableColumn::make(__('lang.amount'))->alignCenter()->width('6rem'),
                                                        ])
                                                        ->relationship('allowances')
                                                        ->schema([
                                                            Select::make('allowance_id')
                                                                ->columnSpan(['default' => 1])
                                                                ->label(__('lang.allowance'))
                                                                ->options(Allowance::where('active', 1)->where('is_specific', 1)->get()->pluck('name', 'id'))
                                                                ->required(),
                                                            Toggle::make('is_percentage')->live()->default(true)->columnSpan(['default' => 1]),
                                                            TextInput::make('amount')->visible(fn (Get $get): bool => ! $get('is_percentage'))->numeric()
                                                                ->columnSpan(['default' => 1])
                                                                ->suffixIcon('heroicon-o-calculator')
                                                                ->suffixIconColor('success'),

                                                            Slider::make('percentage')->hintIcon(Heroicon::PercentBadge)
                                                                ->columnSpan(['default' => 1])
                                                                ->label(__('lang.percentage'))
                                                                ->tooltips(RawJs::make(<<<'JS'
                                                                    `%${$value.toFixed(0)}`
                                                                JS))
                                                                ->pips()
                                                                ->pipsFilter(RawJs::make(<<<'JS'
                                                                    ($value % 50) === 0
                                                                        ? 1
                                                                        : ($value % 10) === 0
                                                                            ? 2
                                                                            : ($value % 25) === 0
                                                                                ? 0
                                                                                : -1
                                                                JS))
                                                                ->fillTrack()
                                                                ->required()
                                                                ->visible(fn (Get $get): bool => $get('is_percentage'))
                                                                ->minValue(0)
                                                                ->step(1)
                                                                ->maxValue(100)
                                                                ->default(0),
                                                        ]),
                                                ]),

                                            Fieldset::make('Monthly bonus')
                                                ->label(__('lang.monthly_bonus'))
                                                ->columnSpan(1)
                                                ->schema([
                                                    Repeater::make('Monthly bonus')
                                                        ->hiddenLabel()
                                                        ->defaultItems(0)
                                                        ->columnSpanFull()
                                                        ->columns(['default' => 2])
                                                        ->table([
                                                            TableColumn::make(__('lang.monthly_bonus'))->width('10rem'),
                                                            TableColumn::make(__('lang.amount'))->alignCenter()->width('6rem'),
                                                        ])
                                                        ->relationship('monthlyIncentives')
                                                        ->schema([
                                                            Select::make('monthly_incentive_id')
                                                                ->columnSpan(['default' => 1])
                                                                ->label(__('lang.monthly_bonus'))
                                                                ->options(MonthlyIncentive::where('active', 1)->get()->pluck('name', 'id'))
                                                                ->required(),
                                                            TextInput::make('amount')
                                                                ->columnSpan(['default' => 1])
                                                                ->default(0)->minValue(0)
                                                                ->numeric(),
                                                        ]),
                                                ]),

                                            Fieldset::make('Custom deductions')
                                                ->label(__('lang.custom_deductions'))
                                                ->columnSpan(1)
                                                ->schema([
                                                    Repeater::make('Custom deductions')
                                                        ->hiddenLabel()
                                                        ->defaultItems(0)
                                                        ->columnSpanFull()
                                                        ->columns(['default' => 3])
                                                        ->table([
                                                            TableColumn::make(__('lang.deduction'))->width('10rem'),
                                                            TableColumn::make(__('lang.type'))->alignCenter()->width('6rem'),
                                                            TableColumn::make(__('lang.amount'))->alignCenter()->width('6rem'),
                                                        ])
                                                        ->relationship('deductions')
                                                        ->schema([
                                                            Select::make('deduction_id')
                                                                ->columnSpan(['default' => 1])
                                                                ->label(__('lang.deduction'))
                                                                ->searchable()
                                                                ->options(Deduction::where('active', 1)->where('is_specific', 1)->get()->pluck('name', 'id'))
                                                                ->required(),
                                                            Toggle::make('is_percentage')->live()->default(false)->columnSpan(['default' => 1]),
                                                            TextInput::make('amount')->visible(fn (Get $get): bool => ! $get('is_percentage'))->numeric()
                                                                ->columnSpan(['default' => 1])
                                                                ->suffixIcon('heroicon-o-calculator')
                                                                ->suffixIconColor('danger'),

                                                            Slider::make('percentage')->hintIcon(Heroicon::PercentBadge)
                                                                ->columnSpan(['default' => 1])
                                                                ->label(__('lang.percentage'))
                                                                ->tooltips(RawJs::make(<<<'JS'
                                                                    `%${$value.toFixed(0)}`
                                                                JS))
                                                                ->pips()
                                                                ->pipsFilter(RawJs::make(<<<'JS'
                                                                    ($value % 50) === 0
                                                                        ? 1
                                                                        : ($value % 10) === 0
                                                                            ? 2
                                                                            : ($value % 25) === 0
                                                                                ? 0
                                                                                : -1
                                                                JS))
                                                                ->fillTrack()
                                                                ->required()
                                                                ->visible(fn (Get $get): bool => $get('is_percentage'))
                                                                ->minValue(0)
                                                                ->step(1)
                                                                ->maxValue(100)
                                                                ->default(0),
                                                        ]),
                                                ]),
                                        ]),
                                ]),
                        ]),
                    Step::make('Last Updated')
                        ->icon(Heroicon::CalendarDateRange)
                        ->visibleOn(['edit', 'view'])
                        ->schema([
                            Grid::make(2)->columnSpanFull()->schema([
                                TextInput::make('updated_at')
                                    ->label(__('lang.updated_at'))
                                    ->disabled()
                                    ->formatStateUsing(function ($state) {
                                        return $state ? Carbon::parse($state)->format('Y-m-d H:i:s') : '-';
                                    }),
                                TextInput::make('updated_by')
                                    ->label('Updated By')
                                    ->disabled()
                                    ->formatStateUsing(function ($state) {
                                        if (! $state) {
                                            return '-';
                                        }
                                        $user = User::find($state);

                                        return $user?->name ?? '-';
                                    }),
                                TextInput::make('created_at')
                                    ->label(__('lang.created_at'))
                                    ->disabled()
                                    ->formatStateUsing(function ($state) {
                                        return $state ? Carbon::parse($state)->format('Y-m-d H:i:s') : '-';
                                    }),

                            ]),
                        ]),
                ])->columnSpanFull()->skippable(),

            ]);
    }

    public static function avatar(): FileUpload
    {
        return FileUpload::make('avatar')->columnSpanFull()
            ->image()
            ->label('')
            ->hiddenLabel()
            // ->avatar()
            ->imageEditor()

            ->circleCropper()
            ->disk('s3')
            // ->directory('employees')
            ->visibility('public')
            ->imageEditorAspectRatios([
                '16:9',
                '4:3',
                '1:1',
            ])
            // ->disk('s3') // Change disk to S3
            ->directory('employees')
            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): string {
                try {
                    $manager = new ImageManager(new Driver);
                    $img = $manager->read($file->get());
                    $img->scaleDown(width: 1200);
                    $encodedImage = $img->toJpeg(70);
                    $filename = 'employees/'.Str::random(15).'.jpeg';
                    Storage::disk('s3')->put($filename, (string) $encodedImage, 'public');

                    return $filename;
                } catch (\Exception $e) {
                    Log::error('Avatar Upload Error: '.$e->getMessage());
                    throw $e;
                }
            })
            // ->imagePreviewHeight('250')
            ->maxSize(20000)
            ->columnSpan(2)
            ->reactive();
    }

    public static function name(): TextInput
    {
        return TextInput::make('name')
            ->label(__('lang.full_name'))
            ->dehydrateStateUsing(fn ($state) => static::formatTitleCase($state))
            ->extraInputAttributes(function ($record) {
                $styles = ['text-transform: capitalize;'];

                if ($record && ! $record->active) {
                    $styles[] = 'color: #ef4444 !important; -webkit-text-fill-color: #ef4444 !important; font-weight: bold;';
                }

                return [
                    'style' => implode(' ', $styles),
                    'x-on:input' => '$el.value = $el.value.replace(/(^|\s)\p{L}/gu, (char) => char.toUpperCase())',
                ];
            })
            ->rules('string')
            ->unique(ignoreRecord: true)
            ->columnSpan(2)
            ->required();
    }

    public static function formatTitleCase(?string $state): string
    {
        return Str::title(preg_replace('/\s+/u', ' ', trim((string) $state)));
    }
}
