<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\CheckboxList;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\HRAttenanceCluster\Resources\LeaveTypeResource\Pages\ListLeaveTypes;
use App\Filament\Clusters\HRAttenanceCluster\Resources\LeaveTypeResource\Pages\CreateLeaveType;
use App\Filament\Clusters\HRAttenanceCluster\Resources\LeaveTypeResource\Pages\EditLeaveType;
use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\LeaveTypeResource\Pages;
use App\Filament\Clusters\HRAttenanceCluster\Resources\LeaveTypeResource\RelationManagers;
use App\Filament\Clusters\HRLeaveManagementCluster;
use App\Filament\Tables\Columns\SoftDeleteColumn;
use App\Models\LeaveType;
use Filament\Actions\Action;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeaveTypeResource extends Resource
{
    protected static ?string $model = LeaveType::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $cluster = HRLeaveManagementCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make()->columnSpanFull()->schema([
                    Step::make('')->columnSpanFull()->schema([
                        Fieldset::make()->columnSpanFull()->schema([
                            Grid::make()->columnSpanFull()->columns(4)->schema([
                                TextInput::make('name')
                                    ->label('Leave type name')
                                    ->unique(ignoreRecord: true)->columnSpan(1)
                                    ->required(),

                                TextInput::make('count_days')
                                    ->label('Number of days')
                                    ->numeric()
                                    ->rules(function (\Filament\Forms\Components\TextInput $component) {
                                        /** @var LeaveType|null $record */
                                        $record = $component->getRecord();

                                        // Creating a new leave type — no restriction
                                        if (!$record?->exists) {
                                            return [];
                                        }

                                        return [
                                            function (string $attribute, $value, \Closure $fail) use ($record) {
                                                if ((float) $value !== (float) $record->getOriginal('count_days')) {
                                                    $hasBalances = \App\Models\LeaveBalance::where('leave_type_id', $record->id)->exists();
                                                    if ($hasBalances) {
                                                        $fail('Cannot change "Days Count": this leave type has employee balances linked to it and cannot be modified.');
                                                    }
                                                }
                                            },
                                        ];
                                    })
                                    ->minValue(1)
                                    ->required(),

                                Select::make('type')->label('Type')->options(LeaveType::getTypes())
                                    ->required(),
                                Select::make('applicable_to')
                                    ->label('Applicable to')
                                    ->options(LeaveType::getApplicabilityOptions())
                                    ->default(LeaveType::APPLICABLE_ALL)
                                    ->required(),
                            ]),
                            Fieldset::make('Accural rules')->columnSpanFull()->columns(3)->schema([
                                Toggle::make('prorate_on_hire')
                                    ->label('Prorate on hire')->inline(false)
                                    ->default(true)
                                    ->helperText('If enabled, the leave balance will be prorated based on the employee\'s joining date.'),

                                Toggle::make('carry_forward_allowed')
                                    ->label('Carry forward allowed')->inline(false)
                                    ->default(false)
                                    ->helperText('If enabled, the employee will be able to carry forward the unused leave balance to the next year.')
                                    ->live(),

                                TextInput::make('max_carry_forward')
                                    ->label('Max carry forward')
                                    ->numeric()
                                    ->default(0)
                                    ->visible(fn($get) => $get('carry_forward_allowed')),

                                TextInput::make('max_days_per_month')
                                    ->label('Max days per month')
                                    ->numeric()
                                    ->nullable(),
                            ]),

                            Fieldset::make('')->columnSpanFull()->columns(4)->schema([

                                Toggle::make('active')
                                    ->label('Active')->inline(false)
                                    ->default(true),

                                Toggle::make('is_paid')
                                    ->label('Is paid')->inline(false)
                                    ->default(true),

                                Toggle::make('requires_attachment')
                                    ->label('Requires attachment')->inline(false)
                                    ->default(false)
                                    ->helperText('If enabled, the employee will be required to upload an attachment when applying for this leave type.'),

                                Toggle::make('all_branches')
                                    ->label('All branches')
                                    ->default(true)
                                    ->inline(false)
                                    ->live(),
                            ]),

                            Fieldset::make('Allowed Branches')
                                ->columnSpanFull()
                                ->schema([
                                    CheckboxList::make('branches')
                                        ->relationship('branches', 'name')
                                        ->searchable()
                                        ->columns(4)
                                        ->columnSpanFull()

                                        ->bulkToggleable()
                                        ->required(),
                                ])
                                ->visible(fn($get) => ! $get('all_branches')),


                        ]),
                    ]),
                    Step::make('Description')->columnSpanFull()->schema([
                        Textarea::make('description')->columnSpanFull()
                            ->label('Description')
                            ->nullable(),
                    ])

                ])
                    ->skippable(true),




            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->defaultSort('id', 'desc')
            ->columns([
                SoftDeleteColumn::make(),
                TextColumn::make('id')->label('ID')
                    ->toggleable(),
                TextColumn::make('name')->label('Leave Type')
                    ->toggleable(),
                TextColumn::make('count_days')->label('Number of Days')->alignCenter(true)->toggleable(),
                TextColumn::make('max_days_per_month')->label('Max/Month')->alignCenter(true)->toggleable(),

                TextColumn::make('type_label')->label('Type')->alignCenter(true)->toggleable(),
                // TextColumn::make('balance_period_label')->label('Accural cycle')->alignCenter(true),
                TextColumn::make('created_at')->label('Created At')->toggleable(isToggledHiddenByDefault: true)->dateTime(),
                BooleanColumn::make('active')->toggleable()
                    ->label('Active')->alignCenter(true)
                    ->boolean(),
                BooleanColumn::make('is_paid')
                    ->label('Is paid')->alignCenter(true)->toggleable()
                    ->boolean(),

            ])
            ->filters([
                TrashedFilter::make()
                    ->visible(fn(): bool => (isSystemManager() || isSuperAdmin() || isBranchManager())),
                SelectFilter::make('type')->options(LeaveType::getTypes()),
                // SelectFilter::make('balance_period')->options(LeaveType::getBalancePeriods())->label('Accural cycle'),
            ], FiltersLayout::Modal)
            ->filtersFormColumns(4)
            ->recordActions([
                self::getAddSpecialBalanceAction(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    RestoreBulkAction::make(),
                    DeleteBulkAction::make(),
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
            'index' => ListLeaveTypes::route('/'),
            'create' => CreateLeaveType::route('/create'),
            'edit' => EditLeaveType::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()
            ->whereNull('deleted_at')->count();
    }

    public static function canViewAny(): bool
    {
        if (isSystemManager() || isSuperAdmin() || isHR()) {
            return true;
        }
        return false;
    }

    public static function getAddSpecialBalanceAction(): Action
    {
        return Action::make('addSpecialBalance')
            ->label('Assign to Staff')
            ->icon('heroicon-o-plus-circle')
            ->color('success')
            ->visible(fn(LeaveType $record): bool => $record->type === LeaveType::TYPE_SPECIAL)
            ->schema([
                Fieldset::make()->columns(4)->columnSpanFull()->schema([
                    \Filament\Forms\Components\Select::make('employee_id')
                        ->label('Employee')
                        ->options(\App\Models\Employee::active()
                            ->forBranchManager()
                            // ->forEmployee()
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('balance')
                        ->label('Balance (Days)')
                        ->numeric()
                        ->default(fn(LeaveType $record) => $record->count_days)
                        ->required(),
                    \Filament\Forms\Components\Select::make('year')
                        ->label('Year')
                        ->options(function () {
                            $currentYear = (int) date('Y');
                            return array_combine(
                                range($currentYear - 1, $currentYear + 2),
                                range($currentYear - 1, $currentYear + 2)
                            );
                        })
                        ->default(date('Y'))
                        ->required(),
                    \Filament\Forms\Components\Select::make('month')
                        ->label('Month')
                        ->options(array_combine(range(1, 12), range(1, 12)))
                        ->nullable(),
                ])
            ])
            ->action(function (array $data, LeaveType $record) {
                try {
                    \Illuminate\Support\Facades\DB::beginTransaction();

                    $employee = \App\Models\Employee::find($data['employee_id']);

                    \App\Models\LeaveBalance::create([
                        'employee_id'   => $employee->id,
                        'branch_id'     => $employee->branch_id,
                        'leave_type_id' => $record->id,
                        'year'          => (int) $data['year'],
                        'month'         => !empty($data['month']) ? (int) $data['month'] : null,
                        'entitled_days' => (float) $data['balance'],
                        'supposed_days' => (float) $record->count_days,
                        'used_days'     => 0,
                        'pending_days'  => 0,
                        'balance'       => (float) $data['balance'],
                        'created_by'    => auth()->id(),
                    ]);

                    \Illuminate\Support\Facades\DB::commit();

                    \Filament\Notifications\Notification::make()
                        ->title('Balance added successfully')
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\DB::rollBack();
                    \Illuminate\Support\Facades\Log::error('Failed to add special leave balance: ' . $e->getMessage());

                    \Filament\Notifications\Notification::make()
                        ->title('Failed to add balance')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('type', [
                LeaveType::TYPE_YEARLY,
                LeaveType::TYPE_MONTHLY,
                LeaveType::TYPE_SPECIAL
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
