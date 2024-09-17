<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\HRCluster;
use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeFileType;
use App\Models\Position;
use Closure;
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
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
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
                                        // ->useFullscreenPopup()
                                        // ->i18n([
                                        //     // Country names
                                        //     'YE' => "YEMEN",
                                        //     'MY' => "MALAYSIA",
                                        //     'KSA' => "SAUDIA",
                                        //     'UAE' => "EMARAT",
                                        // ])
                                            ->autoPlaceholder('aggressive')
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
                                    Grid::make()->columns(3)->schema([
                                        TextInput::make('employee_no')->default((Employee::get()->last()->id) + 1)->disabled()->columnSpan(1)->label('Employee number')->unique(ignoreRecord: true),
                                        TextInput::make('job_title')->columnSpan(1)->required(),
                                        Select::make('position_id')->columnSpan(1)->label('Position type')
                                            ->searchable()
                                            ->options(Position::where('active', 1)->select('id', 'title')->get()->pluck('title', 'id')),
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
                                                ->options(EmployeeFileType::select('id', 'name')->where('active', 1)->get()->pluck('name', 'id'))
                                                ->searchable(),
                                            FileUpload::make('attachment')->label('Attach your file')->downloadable()->previewable(),
                                        ]),
                                    ]),
                                ]),
                        ]),
                    Wizard\Step::make('Salary data')
                        ->schema([
                            Fieldset::make()->label('Set salary data and its config')->schema([
                                Grid::make()->label('')->columns(4)->schema([
                                    TextInput::make('salary')
                                        ->numeric()->columnSpan(1)
                                        ->columnSpan(2)
                                        ->inputMode('decimal')
                                        ->label('Salary')->nullable(),
                                    Toggle::make('discount_exception_if_absent')->columnSpan(1)
                                        ->label('No salary deduction for absences')->default(0)
                                    // ->isInline(false)
                                    ,
                                    Toggle::make('discount_exception_if_attendance_late')->columnSpan(1)
                                        ->label('Exempt from late attendance deduction')->default(0)
                                    // ->isInline(false)
                                    ,
                                ]),
                            ]),
                        ]),
                ])->columnSpanFull(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                ImageColumn::make('avatar_image')->label('')
                    ->circular(),
                TextColumn::make('name')

                    ->sortable()->searchable()
                    ->limit(12)
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
                    ->sortable()->searchable()->limit(20)
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
                TextColumn::make('employee_no')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('employee number')
                    ->sortable()->searchable()
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
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('active')),
                Tables\Filters\TrashedFilter::make(),
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
            //
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
}
