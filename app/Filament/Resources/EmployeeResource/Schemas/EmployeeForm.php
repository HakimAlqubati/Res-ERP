<?php

namespace App\Filament\Resources\EmployeeResource\Schemas;


use Filament\Schemas\Schema;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\Allowance;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeFileType;
use App\Models\EmployeeFileTypeField;
use App\Models\MonthlyIncentive;
use App\Models\Position;
use App\Models\UserType;
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
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EmployeeForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Wizard::make([
                    Step::make(__('lang.personal_data'))
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Tabs::make('')->columnSpanFull()
                                ->tabs([
                                    Tab::make(__('lang.personal_data'))
                                        ->icon(Heroicon::UserCircle)
                                        ->schema([Grid::make()->columns(3)
                                            ->columnSpanFull()
                                            ->schema([
                                                TextInput::make('name')->label(__('lang.full_name'))
                                                    ->dehydrateStateUsing(fn($state) => preg_replace('/\s+/u', ' ', trim((string) $state)))
                                                    ->rules(['string'])
                                                    ->rule(fn() => function (string $attribute, $value, \Closure $fail) {
                                                        $value = preg_replace('/\s+/u', ' ', trim((string) $value));
                                                        $parts = array_values(array_filter(explode(' ', $value)));

                                                        // 1) At least two words
                                                        if (count($parts) < 2) {
                                                            return $fail(__('lang.name_must_contain_two_words'));
                                                        }

                                                        // 2) Letters only (any language) + spaces/hyphen/apostrophe
                                                        if (!preg_match("/^[\\p{L}\\p{M}\\s'\\-]+$/u", $value)) {
                                                            return $fail(__('lang.name_letters_spaces_only'));
                                                        }

                                                        // Helper lists
                                                        $blacklistExact     = ['test', 'tester', 'unknown', 'na', 'n/a', 'none', 'xxx', 'aaaa', 'dd', 'dk', 'as'];
                                                        $whitelistShortLatin = ['al', 'ib', 'bin', 'ibn'];   // common transliterations
                                                        $arabicParticles    = ['بن', 'ابن', 'آل', 'ال'];   // allowed connectors (don’t count as full name)
                                                        $latinVowels        = '/[aeiouy]/i';

                                                        // 3) Reject testy/unrealistic tokens
                                                        $lower = mb_strtolower(str_replace(['-', "'"], ' ', $value));
                                                        foreach ($blacklistExact as $bad) {
                                                            if ($lower === $bad || preg_match('/\\b' . preg_quote($bad, '/') . '\\b/u', $lower)) {
                                                                return $fail(__('lang.name_placeholder_unrealistic'));
                                                            }
                                                        }

                                                        $hasLongCore    = false; // at least one core word length ≥ 3
                                                        $twoLetterCount = 0;

                                                        foreach ($parts as $w) {
                                                            $wTrim = $w;

                                                            // Each part ≥ 2 chars
                                                            if (mb_strlen($wTrim) < 2) {
                                                                return $fail(__('lang.each_part_min_2_chars'));
                                                            }

                                                            // No single-letter repetition like "dd", "aaa"
                                                            if (preg_match('/^(.)\\1{1,}$/u', $wTrim)) {
                                                                return $fail(__('lang.name_unrealistic_repeated_letters'));
                                                            }

                                                            if (mb_strlen($wTrim) === 2) {
                                                                $twoLetterCount++;
                                                            }

                                                            $isArabicParticle = in_array($wTrim, $arabicParticles, true);
                                                            $isShortLatinOk   = in_array(mb_strtolower($wTrim), $whitelistShortLatin, true);

                                                            if (mb_strlen($wTrim) >= 3 && !$isArabicParticle) {
                                                                $hasLongCore = true;
                                                            }

                                                            // For Latin segments: must include a vowel
                                                            if (preg_match('/^[A-Za-z]+$/', $wTrim)) {
                                                                if (!preg_match($latinVowels, $wTrim) && !$isShortLatinOk) {
                                                                    return $fail(__('lang.latin_parts_must_include_vowel'));
                                                                }
                                                                // Avoid long consonant clusters like "dkrv"
                                                                if (preg_match('/[bcdfghjklmnpqrstvwxz]{4,}/i', $wTrim)) {
                                                                    return $fail(__('lang.name_unlikely_consonant_clusters'));
                                                                }
                                                            }
                                                        }

                                                        // 7) Avoid names made mostly of 2-letter words unless there is a long core
                                                        if ($twoLetterCount >= (int) ceil(count($parts) * 0.5)) {
                                                            if (!$hasLongCore) {
                                                                return $fail(__('lang.name_too_short_unrealistic'));
                                                            }
                                                        }

                                                        // 8) Reasonable total length
                                                        if (mb_strlen($value) < 5) {
                                                            return $fail(__('lang.name_too_short'));
                                                        }
                                                    })
                                                    ->columnSpan(1)->required(),
                                                TextInput::make('known_name')
                                                    ->label(__('lang.known_name'))
                                                    // ->helperText(__('lang.known_name_description'))
                                                    ->hint(__('lang.known_name_hint'))
                                                    ->placeholder(__('lang.known_name_example'))
                                                    ->unique(ignoreRecord: true)
                                                    ->nullable()
                                                    ->columnSpan(1),
                                                TextInput::make('email')
                                                    ->label(__('lang.email'))
                                                    ->email()
                                                    ->required()
                                                    // ->unique(table: 'users', column: 'email', ignoreRecord: true)
                                                    ->unique(column: 'email', ignoreRecord: true),

                                                TextInput::make('phone_number')
                                                    ->label(__('lang.phone_number'))
                                                    ->unique(ignoreRecord: true)
                                                    ->columnSpan(1)

                                                    // ->numeric()
                                                    ->maxLength(14)->minLength(8),
                                                Select::make('gender')
                                                    ->label(__('lang.gender'))
                                                    ->options([
                                                        1 => __('lang.male'),
                                                        0 => __('lang.female'),
                                                    ])
                                                    ->required(),


                                                Select::make('nationality')
                                                    ->label(__('lang.nationality'))->live()
                                                    // ->required()
                                                    ->options(getNationalities())
                                                    ->preload()
                                                    ->searchable(),

                                                TextInput::make('mykad_number')->label(__('lang.mykad_number'))->numeric()
                                                    ->visible(fn($get): bool => ($get('nationality') != null && $get('nationality') == setting('default_nationality'))),

                                                Fieldset::make()->columnSpanFull()->label('')
                                                    ->visible(fn($get): bool => ($get('nationality') != null && $get('nationality') != setting('default_nationality')))
                                                    ->schema([
                                                        TextInput::make('passport_no')->label(__('lang.passport_no'))->numeric(),
                                                        Toggle::make('has_employee_pass')->label(__('lang.has_employee_pass'))->inline(false)->live(),

                                                    ]),

                                            ]),]),
                                    Tab::make(__('lang.address'))
                                        ->icon(Heroicon::MapPin)
                                        ->schema([
                                            Fieldset::make()->label(__('lang.employee_address'))->columnSpanFull()->schema([
                                                Textarea::make('address')->label('')->columnSpanFull(),
                                            ]),
                                        ]),
                                    Tab::make(__('lang.avatar'))
                                        ->icon(Heroicon::UserCircle)
                                        ->schema([

                                            FileUpload::make('avatar')->columnSpanFull()
                                                ->image()
                                                ->label('')
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
                                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                                    return Str::random(15) . "." . $file->getClientOriginalExtension();
                                                })
                                                // ->imagePreviewHeight('250')
                                                // ->resize(5)
                                                ->maxSize(1000)
                                                ->columnSpan(2)
                                                ->reactive(),

                                        ])
                                ]),

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
                                            ->options(UserType::where('active', 1)->select('id', 'name')->get()->pluck('name', 'id'))->required(),

                                        Select::make('branch_id')->columnSpan(1)->label(__('lang.branch'))
                                            ->searchable()
                                            ->required()
                                            // ->disabledOn('edit')
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                // عند تغيير الفرع -> أفرغ قيمة owner_id
                                                $set('manager_id', null);
                                            })
                                            ->options(
                                                Branch::query()
                                                    ->select('id', 'name')
                                                    ->whereIn('type', [
                                                        Branch::TYPE_BRANCH,
                                                        Branch::TYPE_HQ
                                                    ])
                                                    ->get()
                                                    ->pluck('name', 'id')
                                            ),
                                        Toggle::make('is_ceo')->label(__('lang.is_ceo'))
                                            ->live()
                                            ->visible(fn($get): bool => $get('employee_type') == 1)
                                            ->default(0)->inline(false),
                                        Select::make('manager_id')
                                            ->columnSpan(1)
                                            ->label(__('lang.manager'))
                                            ->searchable()
                                            // ->requiredIf('is_ceo', false)
                                            ->required(fn(Get $get) => in_array((int) $get('employee_type'), [3, 4]))

                                            ->options(function (Get $get, ?Employee $record) {
                                                $branchId = $get('branch_id');
                                                $currentEmployeeId = $record?->id; // سيكون null في List/Create، ومتوفر في Edit/View

                                                if ($branchId) {
                                                    return Employee::active()
                                                        ->forBranch($branchId)
                                                        // ->employeeTypesManagers()
                                                        ->whereIn('employee_type', [1, 2, 3])
                                                        ->when(
                                                            $currentEmployeeId,
                                                            fn($query) =>
                                                            $query->where('id', '!=', $currentEmployeeId) // استبعاد الموظف الحالي إن كنا في وضع التعديل
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
                                            ->maxDate(now()->toDateString()),
                                        TextInput::make('working_hours')->label(__('lang.working_hours'))->numeric()->required()->default(6),

                                        TextInput::make('working_days')
                                            ->label(__('lang.working_days_per_month'))
                                            ->numeric()
                                            ->minValue(1)->required()
                                            ->maxValue(31)
                                            ->extraInputAttributes(['onkeypress' => 'return event.charCode >= 48 && event.charCode <= 57'])
                                        // ->visible(fn() => Setting::getSetting('working_policy_mode') === 'custom_per_employee')
                                        ,

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

                    Step::make(__('lang.finance'))
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Fieldset::make()->label(__('lang.set_salary_data'))
                                ->columnSpanFull()
                                ->schema([
                                    Grid::make()->columns(4)->columnSpanFull()->schema([
                                        TextInput::make('salary')
                                            ->label(__('lang.salary'))
                                            ->numeric()
                                            ->inputMode('decimal')->disabled(fn(): bool => isBranchManager()),
                                        TextInput::make('tax_identification_number')
                                            ->label(__('lang.tax_identification_number'))->required()
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
                                            ->label(__('lang.no_salary_deduction_for_absences'))->default(0)->inline(false)
                                        // ->isInline(false)
                                        ,
                                        Toggle::make('discount_exception_if_attendance_late')->columnSpan(1)
                                            ->disabled(fn(): bool => isBranchManager())
                                            ->label(__('lang.exempt_from_late_attendance_deduction'))->default(0)->inline(false)
                                        // ->isInline(false)
                                        ,

                                        Repeater::make('bank_information')
                                            ->disabled(fn(): bool => isBranchManager())
                                            ->label(__('lang.bank_information'))
                                            ->columns(2)

                                            ->schema([
                                                TextInput::make('bank')
                                                    ->label(__('lang.bank_name'))
                                                    ->required()
                                                    ->placeholder(__('lang.enter_bank_name')),
                                                TextInput::make('number')
                                                    ->label(__('lang.bank_account_number'))
                                                    ->required()
                                                    ->placeholder(__('lang.enter_bank_account_number')),
                                            ])
                                            ->table([
                                                TableColumn::make(__('lang.bank'))->width('16rem'),
                                                TableColumn::make(__('lang.account_no'))->alignCenter()->width('16rem'),
                                            ])

                                            ->collapsed()
                                            ->minItems(0)         // Set the minimum number of items
                                            // Optional: set the maximum number of items
                                            ->defaultItems(0)     // Default number of items when the form loads
                                            ->columnSpan('full'), // Adjust the span as necessary
                                    ]),
                                    Fieldset::make()->columnSpanFull()->label(__('lang.shift_rfid'))->columnSpanFull()->schema([
                                        Grid::make()->columns(2)->columnSpanFull()->schema([
                                            // CheckboxList::make('periods')
                                            //     ->label('Work Periods')
                                            //     ->relationship('periods', 'name')

                                            //     ->columns(2)
                                            //     ->helperText('Select the employee\'s work periods.')

                                            // ,

                                            TextInput::make('rfid')->label(__('lang.employee_rfid'))
                                                ->unique(ignoreRecord: true),
                                        ]),
                                    ]),
                                    Fieldset::make()->columns(2)->label(__('lang.finance'))->columnSpanFull()
                                        ->disabled(fn(): bool => isBranchManager())
                                        ->schema([
                                            Repeater::make('Monthly allowances')
                                                ->label(__('lang.monthly_allowances'))
                                                ->defaultItems(0)
                                                ->table([
                                                    TableColumn::make(__('lang.allowance'))->width('20rem'),
                                                    TableColumn::make(__('lang.type'))->alignCenter()->width('10rem'),
                                                    TableColumn::make(__('lang.amount'))->alignCenter()->width('12rem'),
                                                ])

                                                ->relationship('allowances')
                                                ->schema([

                                                    Select::make('allowance_id')
                                                        ->label(__('lang.allowance'))
                                                        ->options(Allowance::where('active', 1)->where('is_specific', 1)->get()->pluck('name', 'id'))
                                                        ->required(),
                                                    Toggle::make('is_percentage')->live()->default(true)
                                                    // ->helperText('Set allowance as a salary percentage or fixed amount')
                                                    ,
                                                    TextInput::make('amount')->visible(fn(Get $get): bool => ! $get('is_percentage'))->numeric()
                                                        ->suffixIcon('heroicon-o-calculator')
                                                        ->suffixIconColor('success'),

                                                    Slider::make('percentage')->hintIcon(Heroicon::PercentBadge)
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
                                                        ->visible(fn(Get $get): bool => $get('is_percentage'))
                                                        ->minValue(0)
                                                        ->step(1)
                                                        ->maxValue(100)
                                                        ->default(0)
                                                        ->rtl(),
                                                    // TextInput::make('percentage')
                                                    //     ->visible(fn(Get $get): bool => $get('is_percentage'))
                                                    //     ->numeric()
                                                    //     ->suffixIcon('heroicon-o-percent-badge')
                                                    //     ->suffixIconColor('success'),

                                                ]),
                                            Repeater::make('Monthly bonus')
                                                ->defaultItems(0)
                                                ->table([
                                                    TableColumn::make(__('lang.monthly_bonus'))->width('20rem'),
                                                    TableColumn::make(__('lang.amount'))->alignCenter()->width('12rem'),
                                                ])

                                                ->label(__('lang.monthly_bonus'))
                                                ->relationship('monthlyIncentives')
                                                ->schema([

                                                    Select::make('monthly_incentive_id')
                                                        ->label(__('lang.monthly_bonus'))
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
}
