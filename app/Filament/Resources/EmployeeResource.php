<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\HRCluster;
use App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers\PeriodHistoriesRelationManager;
use App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers\PeriodRelationManager;
use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Allowance;
use App\Models\Branch;
use App\Models\Deduction;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeFileType;
use App\Models\MonthlyIncentive;
use App\Models\Position;
use App\Models\UserType;
use Closure;
use Filament\Forms\Components\CheckboxList;
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
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

// use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = HRCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;
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
                                        // TextInput::make('phone_number')->unique(ignoreRecord: true)->columnSpan(1)->numeric()->maxLength(12)->minLength(8),

                                        PhoneInput::make('phone_number')
                                        // ->numeric()
                                            ->initialCountry('MY')
                                            ->onlyCountries([
                                                'MY',
                                                'US',
                                                'YE',
                                                'AE',
                                                'SA',
                                            ])
                                            ->displayNumberFormat(PhoneInputNumberType::E164)
                                            ->autoPlaceholder('aggressive')
                                            ->unique(ignoreRecord: true)
                                            ->validateFor(
                                                country: 'MY',
                                                lenient: true, // default: false
                                            ),

                                    ]),
                                    Fieldset::make()->label('Employee address')->schema([
                                        Textarea::make('address')->label('')->columnSpanFull(),
                                    ]),
                                    Fieldset::make()->label('Upload avatar image')
                                        ->columnSpanFull()
                                        ->schema([
                                            Grid::make()->columns(2)->schema([FileUpload::make('avatar')
                                                    ->image()
                                                    ->label('')
                                                // ->avatar()
                                                    ->imageEditor()
                                                    ->circleCropper()
                                                    ->disk('public')
                                                    ->directory('employees')
                                                    ->visibility('public')
                                                    ->imageEditorAspectRatios([
                                                        '16:9',
                                                        '4:3',
                                                        '1:1',
                                                    ])

                                                // ->imagePreviewHeight('250')
                                                    ->resize(5)

                                                // ->loadingIndicatorPosition('left')
                                                // ->panelLayout('integrated')
                                                // ->removeUploadedFileButtonPosition('right')
                                                // ->uploadButtonPosition('left')
                                                // ->uploadProgressIndicatorPosition('left')

                                                // ->openable()
                                                // ->downloadable()
                                                // ->default('https://dummyimage.com/900x700')
                                                // ->previewable(false)
                                                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                                        return (string) str($file->getClientOriginalName())->prepend('employee-');
                                                    })
                                                // ->formatStateUsing(function ($record,Get $get){
                                                //     dd($get);
                                                //     return url('/').'/storage/'. $record->avatar;
                                                // })
                                                    ->columnSpan(2)
                                                    ->reactive()
                                                ,
                                                // ViewField::make('avatar_view')
                                                //     ->columnSpan(1)

                                                //     ->view('filament.images.employee-avatar')
                                                //     ->formatStateUsing(function (Get $get, $record) { //adds the initial state on page load

                                                //       if(count($get('avatar'))> 0){
                                                //           return url('/') . '/storage/' . array_values($get('avatar'))[0];
                                                //       }
                                                //       return '';
                                                //     })
                                                // ,
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
                                        Select::make('employee_type')->columnSpan(1)->label('Employee type')
                                            ->searchable()
                                            ->options(UserType::where('active', 1)->select('id', 'name')->get()->pluck('name', 'id')),
                                        Select::make('department_id')->columnSpan(1)->label('Department')
                                            ->searchable()
                                            ->options(Department::where('active', 1)->select('id', 'name')->get()->pluck('name', 'id')),
                                        Select::make('branch_id')->columnSpan(1)->label('Branch')
                                            ->searchable()
                                            ->required()
                                            ->options(Branch::where('active', 1)->select('id', 'name')->get()->pluck('name', 'id')),
                                        DatePicker::make('join_date')->columnSpan(1)->label('Start date')->nullable(),

                                    ]),
                                ]),
                        ]),
                    Wizard\Step::make('Employee files')
                        ->schema([
                            Repeater::make('files')
                                ->relationship()
                                ->columns(2)
                            // ->minItems(0)
                                ->defaultItems(0)
                                ->schema([

                                    Fieldset::make()->schema([
                                        Grid::make()->columns(2)->schema([
                                            Select::make('file_type_id')
                                                ->label('File type')
                                                ->required()
                                                ->options(EmployeeFileType::select('id', 'name')->where('active', 1)->get()->pluck('name', 'id'))
                                                ->searchable(),
                                            FileUpload::make('attachment')->label('Attach your file')->downloadable()->previewable()->required(),
                                        ]),
                                    ]),
                                ]),
                        ]),
                    Wizard\Step::make('Finance  & Shift data')
                        ->schema([
                            Fieldset::make()->label('Set salary data and account number')->schema([
                                Grid::make()->label('')->columns(4)->schema([
                                    TextInput::make('salary')
                                        ->numeric()->columnSpan(1)
                                        ->columnSpan(2)
                                        ->inputMode('decimal'),
                                    TextInput::make('bank_account_number')
                                        ->columnSpan(2)
                                        ->label('Bank account number')->nullable(),
                                    Toggle::make('discount_exception_if_absent')->columnSpan(1)
                                        ->label('No salary deduction for absences')->default(0)
                                    // ->isInline(false)
                                    ,
                                    Toggle::make('discount_exception_if_attendance_late')->columnSpan(1)
                                        ->label('Exempt from late attendance deduction')->default(0)
                                    // ->isInline(false)
                                    ,
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
                                            ->unique(ignoreRecord: true)
                                        ,
                                    ]),
                                ]),
                                Fieldset::make()->columns(3)->label('Finance')->schema([
                                    Repeater::make('Monthly deductions')

                                        ->defaultItems(0)
                                        ->relationship('deductions')
                                        ->schema([

                                            Select::make('deduction_id')
                                                ->label('Deducation')
                                                ->options(Deduction::where('active', 1)->where('is_specific', 1)->get()->pluck('name', 'id'))
                                                ->required()

                                            ,
                                            Toggle::make('is_percentage')->live()->default(true)
                                            // ->helperText('Set allowance as a salary percentage or fixed amount')
                                            ,
                                            TextInput::make('amount')->visible(fn(Get $get): bool => !$get('is_percentage'))->numeric()
                                                ->suffixIcon('heroicon-o-calculator')
                                                ->suffixIconColor('success')
                                            ,
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
                                                ->required()

                                            ,
                                            Toggle::make('is_percentage')->live()->default(true)
                                            // ->helperText('Set allowance as a salary percentage or fixed amount')
                                            ,
                                            TextInput::make('amount')->visible(fn(Get $get): bool => !$get('is_percentage'))->numeric()
                                                ->suffixIcon('heroicon-o-calculator')
                                                ->suffixIconColor('success')
                                            ,
                                            TextInput::make('percentage')->visible(fn(Get $get): bool => $get('is_percentage'))->numeric()
                                                ->suffixIcon('heroicon-o-percent-badge')
                                                ->suffixIconColor('success'),

                                        ]),
                                    Repeater::make('Monthly incentives')
                                        ->defaultItems(0)
                                        ->relationship('monthlyIncentives')
                                        ->schema([

                                            Select::make('monthly_incentive_id')
                                                ->label('Monthly incentive')
                                                ->options(MonthlyIncentive::where('active', 1)->get()->pluck('name', 'id'))
                                                ->required()

                                            ,
                                            TextInput::make('amount')
                                                ->default(0)->minValue(0)
                                                ->numeric(),

                                        ]),

                                ])

                                ,
                            ]),
                        ]),
                ])->columnSpanFull()->skippable(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                ImageColumn::make('avatar_image')->label('')
                    ->circular(),
                TextColumn::make('id')->label('id')->copyable()->hidden(),
                TextColumn::make('avatar_image2')->copyable()->label('avatar name')->hidden()
                   ,
                TextColumn::make('employee_no')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Employee No.')
                    ->sortable()->searchable()
                    ->searchable(isIndividual: true, isGlobal: false),
                TextColumn::make('name')
                    ->sortable()->searchable()
                    ->limit(20)
                    ->searchable(isIndividual: true, isGlobal: false)
                    ->toggleable(isToggledHiddenByDefault: false)
                ,
                TextColumn::make('name')
                    ->sortable()->searchable()
                    ->limit(12)
                    ->label('Full name')
                    ->searchable(isIndividual: true, isGlobal: false)
                    ->toggleable(isToggledHiddenByDefault: false)
                ,
                TextColumn::make('email')->icon('heroicon-m-envelope')
                    ->sortable()->searchable()->limit(20)->default('@')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(isIndividual: true, isGlobal: false),
                TextColumn::make('join_date')->sortable()->label('Start date')
                    ->sortable()->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(isIndividual: true, isGlobal: false),
                TextColumn::make('salary')->sortable()->label('Salary')
                    ->sortable()->searchable()
                    ->numeric(decimalPlaces: 0)
                    ->money('MYR')
                    ->default(0)
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(isIndividual: true, isGlobal: false)->alignCenter(true),

                TextColumn::make('phone_number')->label('Phone')->searchable()->icon('heroicon-m-phone')->searchable(isIndividual: true)->default('_')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('position.title')->limit(20)
                    ->label('Position type')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('job_title')
                    ->label('Job title')
                    ->sortable()->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(isIndividual: true, isGlobal: false),

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
                    })
                ,
                IconColumn::make('has_user')->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('rfid')
                    ->label('RFID')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('active')),
                Tables\Filters\TrashedFilter::make(),
                SelectFilter::make('branch_id')
                    ->searchable()
                    ->multiple()
                    ->label(__('lang.branch'))->options([Branch::get()->pluck('name', 'id')->toArray()]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
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
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ListEmployees::class,
            Pages\CreateEmployee::class,
            Pages\EditEmployee::class,
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

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager()) {
            return true;
        }
        return false;
    }
}
