<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\SettingsCluster;
use App\Filament\Resources\SettingResource\Pages;
use App\Models\Attendance;
use App\Models\Setting;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $modelLabel = 'System Settings';
    protected static ?string $pluralLabel = 'System Settings';
    // protected static ?string $cluster = SettingsCluster::class;
    // protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    // protected static ?int $navigationSort = 1;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('settings')->columnSpanFull()
                    ->tabs([
                        Tab::make('Company Info')->hidden(function () {
                            if (isFinanceManager()) {
                                return true;
                            }
                            return false;
                        })
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Fieldset::make()->columns(3)->label('Company Info')->schema([
                                    TextInput::make("company_name")
                                        ->label('Name'),
                                    TextInput::make("company_phone")
                                        ->label('Phone Number')

                                        ->required(),

                                    Select::make('default_nationality')
                                        ->label('Locale')
                                        ->searchable()
                                        ->options(getNationalitiesAsCountries()),
                                    FileUpload::make('company_logo')
                                        ->label('Logo')
                                        ->required(false)
                                        ->directory('company_logo')
                                        ->image()->disk('public')
                                        ->visibility('public')
                                        ->columnSpan(3)
                                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                            return  Str::random(15) . "." . $file->getClientOriginalExtension();
                                        }),
                                    Fieldset::make()->columnSpanFull()->label('Address')->schema([
                                        Textarea::make('address')->label('')->columnSpanFull()->required(),
                                    ]),
                                ]),
                            ]),

                        Tab::make('HR Settings')
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                Fieldset::make()->label('Work Shifts')->columns(4)->schema([
                                    TextInput::make("hours_count_after_period_before")
                                        ->label('Allowed hours pre-period')
                                        ->numeric()
                                        ->required(),
                                    TextInput::make("hours_count_after_period_after")
                                        ->label('Allowed hours post-period')
                                        ->numeric()
                                        ->required(),

                                    TextInput::make("early_attendance_minutes")
                                        ->label('Early arrival minutes')
                                        // ->helperText('The number of minutes before the scheduled start time that is considered early attendance.')
                                        ->numeric()
                                        ->required(),
                                    TextInput::make("pre_end_hours_for_check_in_out")
                                        ->label('Pre-period action hours')
                                        // ->helperText('Number of hours remaining before period end to trigger an action if check-in or check-out is not recorded')
                                        ->numeric()
                                        ->required(),
                                    TextInput::make("early_depature_deduction_minutes")
                                        ->label('Early depature deduction minutes')
                                        ->numeric()
                                        ->required(),
                                    Fieldset::make()->columns(2)->columnSpanFull()->schema([
                                        Select::make("period_allowed_to_calculate_overtime")
                                            ->label('Overtime calculation period')
                                            ->options([
                                                Attendance::PERIOD_ALLOWED_OVERTIME_QUARTER_HOUR => Attendance::PERIOD_ALLOWED_OVERTIME_QUARTER_HOUR_LABEL,
                                                Attendance::PERIOD_ALLOWED_OVERTIME_HALF_HOUR => Attendance::PERIOD_ALLOWED_OVERTIME_HALF_HOUR_LABEL,
                                                Attendance::PERIOD_ALLOWED_OVERTIME_HOUR => Attendance::PERIOD_ALLOWED_OVERTIME_HOUR_LABEL,
                                            ])
                                            ->live()
                                            ->required(),
                                        Toggle::make('calculating_overtime_with_half_hour_after_hour')
                                            ->inline(false)
                                            ->visible(fn(Get $get): bool => $get('period_allowed_to_calculate_overtime') == Attendance::PERIOD_ALLOWED_OVERTIME_HOUR),

                                    ]),
                                ]),
                                Fieldset::make()->label('Salary')->columns(4)->schema([
                                    TextInput::make('days_in_month')->label('Days in Month')->helperText('Days of month to calculate daily salary')->required(),

                                    TextInput::make('hours_no_in_day')->label('Hours No in Day')->helperText('Hours number in day to calculate hourly salary')->required(),
                                    TextInput::make('overtime_hour_multiplier')
                                        ->label('Overtime Hour Multiplier')
                                        ->helperText('Enter the overtime multiplier, e.g., 2 for double, 3 for triple')
                                        ->numeric()
                                        ->minValue(1)
                                        ->placeholder('Enter multiplier (e.g., 2, 3, 4)')
                                        ->required(),
                                    Toggle::make('create_auto_leave_when_create_payroll')
                                        ->label('Create Auto Leave When Create Payroll')
                                        ->helperText('Create auto leave when create payroll')
                                        ->default(false)
                                        ->visible(fn(): bool => isSuperAdmin())
                                        ->hidden(),
                                    Toggle::make('flix_hours')
                                        ->label('Flix Hours')
                                        ->helperText('No deductions will be applied if the total hours worked equal or exceed the required daily hours')
                                        ->default(false),
                                    Fieldset::make()->label('End of Month Day')->columnSpanFull()->schema([
                                        Toggle::make('use_standard_end_of_month')
                                            ->label('Use Standard End of Month')
                                            ->inline(false)
                                            ->live()
                                            ->helperText('Enable this to use the normal end of month (1st - 30th)')
                                            ->default(true),

                                        Select::make('end_of_month_day')
                                            ->label('Custom End of Month Day')
                                            ->helperText('Select a custom day for the end of the month')
                                            ->options(array_combine(range(1, 28), range(1, 28))) // Creates options from 1 to 28
                                            ->visible(fn(Get $get) => !$get('use_standard_end_of_month')) // Only visible if 'use_standard_end_of_month' is false
                                            ->required(),
                                    ])

                                ]),
                                Fieldset::make()->label('Face rekognation settings')
                                    ->hidden(fn(): bool => isFinanceManager())
                                    ->columns(4)->schema([
                                        Select::make('timeout_webcam_value')
                                            ->label('Camera Auto-Off Timer (minutes)')
                                            ->options([
                                                '30000' => 'Half Minute',
                                                '60000' => 'One Minute',
                                                '120000' => 'Two Minutes',
                                                '180000' => 'Three Minutes',
                                                '300000' => 'Five Minutes',
                                                '600000' => 'Ten Minutes',
                                            ])
                                            ->default('30000')
                                            ->native(false)
                                            ->required()
                                            ->helperText('Select the camera timeout duration.'),
                                        Select::make('webcam_capture_time')
                                            ->label('Image Capture Delay (Seconds)')
                                            ->options([
                                                '500' => 'Half a Second',
                                                '1000' => 'One Second',
                                                '2000' => 'Two Seconds',
                                                '3000' => 'Three Seconds',
                                                '5000' => 'Five Seconds',
                                                '7000' => 'Seven Seconds',
                                                '8000' => 'Eight Seconds',
                                                '10000' => 'Ten Seconds',
                                            ])
                                            ->default('1000') // Default to 1 second
                                            ->helperText('Choose the delay before capturing an image.')
                                            ->native(false)
                                            ->required(),
                                    ]),

                            ])
                            ->hidden(function () {
                                return hideHrForTenant();
                            }),
                        Tab::make('Tax Settings')->hidden()
                            ->icon('heroicon-o-calculator')
                            ->schema([
                                Fieldset::make('MTD/PCB Tax Brackets')->columns(4)->schema([
                                    TextInput::make('tax_bracket_0_to_5000')
                                        ->label('0 - 5,000')
                                        ->default(0) // 0% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->disabled(),

                                    TextInput::make('tax_bracket_5001_to_20000')
                                        ->label('5,001 - 20,000')
                                        ->default(1) // 1% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('helperText', "Tax Rate: {$state}%")),

                                    TextInput::make('tax_bracket_20001_to_35000')
                                        ->label('20,001 - 35,000')
                                        ->default(3) // 3% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('helperText', "Tax Rate: {$state}%")),

                                    TextInput::make('tax_bracket_35001_to_50000')
                                        ->label('35,001 - 50,000')
                                        ->default(8) // 8% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('helperText', "Tax Rate: {$state}%")),

                                    TextInput::make('tax_bracket_50001_to_70000')
                                        ->label('50,001 - 70,000')
                                        ->default(13) // 13% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('helperText', "Tax Rate: {$state}%")),

                                    TextInput::make('tax_bracket_70001_to_100000')
                                        ->label('70,001 - 100,000')
                                        ->default(21) // 21% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('helperText', "Tax Rate: {$state}%")),

                                    TextInput::make('tax_bracket_100001_to_250000')
                                        ->label('100,001 - 250,000')
                                        ->default(24) // 24% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('helperText', "Tax Rate: {$state}%")),

                                    TextInput::make('tax_bracket_250001_to_400000')
                                        ->label('250,001 - 400,000')
                                        ->default(25) // 25% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('helperText', "Tax Rate: {$state}%")),

                                    TextInput::make('tax_bracket_400001_to_600000')
                                        ->label('400,001 - 600,000')
                                        ->default(26) // 26% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('helperText', "Tax Rate: {$state}%")),

                                    TextInput::make('tax_bracket_600001_to_1000000')
                                        ->label('600,001 - 1,000,000')
                                        ->default(28) // 28% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('helperText', "Tax Rate: {$state}%")),

                                    TextInput::make('tax_bracket_1000001_to_2000000')
                                        ->label('1,000,001 - 2,000,000')
                                        ->default(30) // 30% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('helperText', "Tax Rate: {$state}%")),

                                    TextInput::make('tax_bracket_above_2000000')
                                        ->label('Above 2,000,000')
                                        ->default(32) // 32% tax rate
                                        ->helperText(fn($state) => "Tax Rate: {$state}%")
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) => $set('helperText', "Tax Rate: {$state}%")),
                                ]),
                            ]),
                        Tab::make('Task Settings')->hidden(fn(): bool => isFinanceManager())
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                Fieldset::make('')->columns(4)->schema([
                                    TextInput::make('task_rejection_times_red_card')
                                        ->label('Rejections times lead to red')
                                        ->default(2)
                                        ->prefixIconColor('danger')
                                        ->prefixIcon('heroicon-o-credit-card') // Replace with a red card icon class
                                        ->helperText('Red card indicates task rejection limit'), // Optional helper text

                                    TextInput::make('task_rejection_times_yello_card')
                                        ->label('Rejections times lead to yellow')
                                        ->prefixIconColor('warning')
                                        ->prefixIcon('heroicon-o-credit-card')
                                        ->helperText('Yellow card indicates task rejection limit')
                                        ->default(1),
                                    Select::make('task_red_card_penalty_type')->required()
                                        // ->text('-select a panality-')
                                        ->native(false)
                                        ->reactive()
                                        ->label('Penalty Type for Red Card')
                                        ->options([
                                            'deduction_half_day' => 'Deduction Half Day',
                                            'deduction_full_day' => 'Deduction Full Day',
                                            'custom_amount' => 'Custom amount',
                                            'no_penalty' => 'No Penalty',
                                        ])
                                        ->default('no_penalty')
                                        ->helperText('Select the penalty applied when a red card is issued'),
                                    TextInput::make('task_penality_custom_amount_red_card')
                                        ->label('Custom amount')
                                        ->visible(fn($get): bool => $get('task_red_card_penalty_type') == 'custom_amount')
                                        ->prefixIconColor('warning')
                                        ->prefixIconColor('danger')
                                        ->prefixIcon('heroicon-o-document-currency-dollar')
                                        ->helperText('Specify the deduction amount for employees who receive a red card')
                                        ->default(1),
                                    Checkbox::make('show_warning_message')
                                        ->inline(true)
                                        ->label('Show warning message before second rejection')
                                        ->default(false)
                                        ->helperText('Enable to show a warning message before issuing a second rejection'),

                                ]),
                                // Fieldset::make('')->columns(4)->schema([
                                //     TextInput::make('tasks_count_for_hero_title')
                                //         ->label('Tasks required for Hero title')
                                //         ->default(20) // Set a default value, if applicable
                                //         ->prefixIcon('heroicon-o-bars-arrow-up')
                                //         ->helperText('Specify the number of tasks an employee must complete to earn the "Hero of the Month" title.'),

                                // ]),
                            ])->hidden(function () {
                                return hideHrForTenant();
                            }),
                        Tab::make('Stock Settings')->hidden(fn(): bool => isFinanceManager())
                            ->icon('heroicon-o-shopping-cart')
                            ->schema([
                                Fieldset::make('')->label('Purchase Settings')->columns(3)->schema([
                                    Toggle::make('purchase_invoice_no_required_and_disabled_on_edit')
                                        ->inline(false)
                                        ->label('Purchase Invoice Number Required and Disabled on Edit')
                                        ->offIcon('heroicon-s-user')
                                        ->onColor('success')
                                        ->offColor('danger')
                                        ->helperText('Enable this to require and disable editing of the purchase invoice number.')
                                        ->default(false),
                                ]),
                                Fieldset::make('GRN Workflow Settings')->columns(2)->schema([
                                    Toggle::make('purchase_invoice_from_grn_only')
                                        ->inline(false)->columnSpanFull()
                                        ->label('Use GRN to Create Purchase Invoices Only')
                                        ->helperText('If enabled, purchase invoices can only be created through GRN.')
                                        ->default(false),
                                    Select::make('grn_entry_role_id')->multiple()
                                        ->label('Role Allowed to Create GRN')
                                        ->options(\Spatie\Permission\Models\Role::pluck('name', 'id')->toArray())
                                        ->searchable()
                                        ->required(),
                                    Select::make('grn_approver_role_id')->multiple()
                                        ->label('Role Allowed to Approve GRN')
                                        ->options(\Spatie\Permission\Models\Role::pluck('name', 'id')->toArray())
                                        ->searchable()
                                        ->required(),
                                    Toggle::make('grn_affects_inventory')->inline(false)
                                        ->label('Affect Inventory Upon GRN Creation')
                                        ->default(false)
                                        ->helperText('If enabled, GRN will directly impact stock levels.'),
                                    Toggle::make('purchase_invoice_affects_inventory')
                                        ->label('Affect Inventory Upon Purchase Invoice Creation')
                                        ->default(true)
                                        ->inline(false)
                                        ->helperText('If disabled, purchase invoice details will not be added to inventory.'),
                                ]),

                                Fieldset::make('')->label('Orders Settings')->columns(3)->schema([
                                    // Select::make('calculating_orders_price_method')
                                    //     ->label(__('system_settings.calculating_orders_price_method'))
                                    //     ->options([
                                    //         'from_unit_prices' => __('system_settings.from_unit_prices'),
                                    //         'fifo' => __('system_settings.fifo'),
                                    //     ])
                                    //     ->default('from_unit_prices') // Default to 1 second
                                    //     ->helperText('Choose method calculating orders.')
                                    //     ->native(false)
                                    //     ->required(),
                                    TextInput::make('currency_symbol')->label(__('system_settings.currency_symbol')),
                                    TextInput::make('limit_days_orders')->numeric()->label(__('system_settings.limit_days_orders')),
                                    Grid::make()->columns(2)->schema([
                                        // Toggle::make('completed_order_if_not_qty')->inline(false)
                                        //     ->label(__('system_settings.completed_order_if_not_qty'))
                                        //     // ->onIcon('heroicon-s-lightning-bolt')
                                        //     ->offIcon('heroicon-s-user')
                                        //     ->onColor('success')
                                        //     ->offColor('danger')
                                        //     ->helperText(__('system_settings.note_if_order_completed_if_not_qty')),
                                        Toggle::make('enable_user_orders_to_store')->inline(false)
                                            ->label(__('system_settings.enable_user_orders_to_store'))
                                            // ->onIcon('heroicon-s-lightning-bolt')
                                            ->offIcon('heroicon-s-user')
                                            ->onColor('success')
                                            ->offColor('danger')
                                            ->helperText(__('system_settings.enable_user_orders_to_store')),
                                        Toggle::make('create_auto_order_when_stock_empty')
                                            ->inline(false)
                                            ->label('Auto-create order if stock is unavailable')
                                            ->helperText('Automatically create a new order  if inventory is empty and update original quantity to zero.')
                                            ->default(false),
                                    ])
                                ]),
                            ]),
                        Tabs\Tab::make('Users Settings')
                            ->label(__('lang.users_settings'))
                            ->icon('heroicon-o-users')
                            ->schema([
                                Grid::make()->schema([
                                    Grid::make()->columns(3)->schema([
                                        TextInput::make("password_min_length")
                                            ->label(__('lang.password_min_length'))->numeric()
                                            ->columnSpan(1)->required()->default(6),
                                        Select::make('password_contains_for')
                                            ->label(__('lang.password_strong_type'))
                                            ->options([
                                                'easy_password' => __('lang.easy_password'),
                                                'strong_password' => __('lang.strong_password'),
                                            ])
                                            ->required() // You can adjust validation as needed
                                            ->default('only_letters') // Set default value if required
                                        ,
                                        Select::make('disallow_multi_session')
                                            ->label(__('lang.disallow_multi_session'))
                                            ->options([
                                                1 => __('lang.yes'),
                                                0 => __('lang.no'),
                                            ])
                                            ->required() // You can adjust validation as needed
                                            ->default(0) // Set default value if required
                                        ,
                                    ]),
                                    Fieldset::make()->label(__('lang.setting_to_block_users_with_failed_attempts'))
                                        ->columns(3)
                                        ->schema([
                                            TextInput::make('threshold')
                                                ->label(__('lang.threshold'))
                                                ->columnSpan(1)
                                                ->numeric()
                                                ->required()
                                                ->default(3),
                                            Select::make('type_reactive_blocked_users')
                                                ->label(__('lang.type_reactive_blocked_users'))
                                                ->options([
                                                    'manual' => __('lang.manual'),
                                                    'based_on_specific_time' => __('lang.based_on_specific_time'),
                                                ])
                                                ->required()
                                                ->default('based_on_specific_time')
                                                ->reactive()
                                                ->columnSpan(1),

                                            TextInput::make('hours_to_allow_login_again')
                                                ->label(__('lang.hours_to_allow_login_again'))
                                                ->columnSpan(1)
                                                ->visible(fn($get) => $get('type_reactive_blocked_users') == 'based_on_specific_time')

                                                ->default(1)
                                                ->numeric(),
                                        ]),
                                    Fieldset::make()->label('Setup Mobile Application Login Methos')
                                        ->columns(2)
                                        ->schema([
                                            Select::make('login_method')->options(['phone_number' => 'Phone', 'email' => 'email'])->label('Login Methos'),
                                            Select::make('login_auth_type')
                                                ->label('Login Authentication Method')
                                                ->options([
                                                    'password' => 'Email/Phone with Password',
                                                    'otp' => 'OTP via Email',
                                                ])
                                                ->default('password')
                                                ->required()
                                                ->helperText('Choose how users should authenticate when logging in.'),
                                        ])
                                ]),
                            ]),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            // 'index' => Pages\ListSettings::route('/'),
            // 'create' => Pages\CreateSetting::route('/create'),
            'index' => Pages\CreateSetting::route('/'),
            // 'edit' => Pages\EditSetting::route('/'),
        ];
    }
    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('update_setting');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_setting');
    }

    public static function canAccess(): bool
    {
        return static::canViewAny();
    }
}
