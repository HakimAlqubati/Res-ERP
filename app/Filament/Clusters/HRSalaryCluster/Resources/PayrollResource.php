<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources;

use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\Pages;
use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\RelationManagers;
use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\RelationManagers\PayrollsRelationManager;
use App\Filament\Pages\RunPayroll;
use App\Filament\Tables\Columns\SoftDeleteColumn;
use App\Enums\HR\Payroll\SalaryTransactionType;
use App\Models\AdvanceRequest;
use App\Models\Branch;
use App\Models\EmployeeAdvanceInstallment;
use App\Models\Payroll;
use App\Models\PayrollRun;
use App\Models\SalaryTransaction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PayrollResource extends Resource
{
    protected static ?string $model = PayrollRun::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::Banknotes;

    protected static ?string $cluster = HRSalaryCluster::class;
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::forBranchManager()->count();
    }
    public static function getNavigationLabel(): string
    {
        return 'Payroll';
    }
    public static function getPluralLabel(): ?string
    {
        return 'Payroll';
    }

    public static function getLabel(): ?string
    {
        return 'Payroll';
    }

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Fieldset::make()->columnSpanFull()->label('Set Branch, Month and payment date')->columns(3)->schema([
                    TextInput::make('note_that')->label('Note that!')->columnSpan(3)->hiddenOn('view')
                        ->disabled()
                        // ->extraAttributes(['class' => 'text-red-600'])
                        ->suffixIcon('heroicon-o-exclamation-triangle')
                        ->suffixIconColor('warning')
                        // ->color(Color::Red)
                        ->default('Employees who have not had their work periods added, will not appear on the payroll.'),
                    Select::make('branch_id')->label('Choose branch')
                        ->disabledOn('view')->searchable()
                        ->options(Branch::selectable()
                            ->forBranchManager('id')
                            ->select('id', 'name')
                            ->get()
                            ->pluck('name', 'id'))
                        ->required()

                        ->helperText('Please, choose a branch'),
                    Select::make('name')->label('Month')->hiddenOn('view')
                        ->required()
                        ->options(fn() => getMonthOptionsBasedOnSettings()) // Use the helper function

                        // ->searchable()
                        ->default(now()->format('F'))
                        ->rule(function (callable $get) {
                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                // استخراج branch_id من الفورم
                                $branchId = $get('branch_id');
                                if (! $branchId) {
                                    return;
                                }

                                // "August 2025" → [$monthName, $year]
                                [$monthName, $year] = explode(' ', $value);
                                $monthNumber = \Carbon\Carbon::parse($monthName)->month;

                                // تحقق من وجود سجل مكرر
                                $exists = \App\Models\PayrollRun::query()
                                    ->where('branch_id', $branchId)
                                    ->where('year', (int) $year)
                                    ->where('month', (int) $monthNumber)
                                    ->withTrashed()
                                    ->first();
                                // dd($exists);
                                if ($exists) {
                                    if ($exists->trashed()) {
                                        $fail(__('Payroll for this branch and month already exists in the recycle bin. Please restore or permanently delete it before creating a new one.'));
                                    } else {
                                        $fail(__('Payroll for this branch and month already exists. You cannot create a duplicate.'));
                                    }
                                }
                            };
                        }),
                    TextInput::make('name')->label('Title')->hiddenOn('create')->disabled(),
                    DatePicker::make('pay_date')->required()
                        ->default(date('Y-m-d')),
                ]),
                Textarea::make('notes')->label('Notes')->columnSpanFull(),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SoftDeleteColumn::make(),
                TextColumn::make('name')
                    ->label('Name')->searchable()->sortable(),
                TextColumn::make('branch.name')
                    ->label('Branch')->sortable(),
                TextColumn::make('year')

                    ->sortable(),
                TextColumn::make('month')
                    ->formatStateUsing(function ($record) {
                        $months = getMonthArrayWithKeys();
                        $key = str_pad($record->month, 2, '0', STR_PAD_LEFT); // يحول 1 → 01 ، 9 → 09 ، 10 تبقى 10
                        return $months[$key] ?? '';
                    })
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('Status'))
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn($state) => PayrollRun::statuses()[$state] ?? $state)
                    ->colors([
                        'warning' => PayrollRun::STATUS_PENDING,   // أصفر
                        'info'    => PayrollRun::STATUS_COMPLETED, // أزرق
                        'success' => PayrollRun::STATUS_APPROVED,  // أخضر
                    ]),


            ])
            ->filters([
                SelectFilter::make('branch_id')->label('Branch')
                    ->options(Branch::selectable()->forBranchManager('id')->pluck('name', 'id')),
                TrashedFilter::make(),


            ])
            ->recordActions([
                ViewAction::make(),
                self::earlyInstallmentPaymentAction(),
                self::approveAction(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
        ;
    }

    /**
     * Approve Action for PayrollRun table.
     * 
     * Shows a confirmation dialog and updates status to approved.
     * Only visible when status is pending or completed.
     */
    public static function approveAction(): Action
    {
        return Action::make('approve')->button()
            ->label(__('Approve'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(__('Approve Payroll'))
            ->modalDescription(__('Are you sure you want to approve this payroll? This will sync it with the financial system.'))
            ->modalSubmitActionLabel(__('Yes, Approve'))
            ->visible(
                fn(PayrollRun $record): bool =>
                in_array($record->status, [PayrollRun::STATUS_PENDING, PayrollRun::STATUS_COMPLETED])
            )
            ->action(function (PayrollRun $record): void {
                // IMPORTANT: Update child Payrolls FIRST before updating PayrollRun
                // Because the Observer on PayrollRun will trigger financial sync
                // which needs the Payrolls to be approved
                $record->payrolls()
                    ->where('status', Payroll::STATUS_PENDING)
                    ->update([
                        'status' => Payroll::STATUS_APPROVED,
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);

                // Now update the PayrollRun status (this triggers the Observer)
                // Note: Installments are marked as paid in PayrollRunObserver
                $record->update([
                    'status' => PayrollRun::STATUS_APPROVED,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);

                \Filament\Notifications\Notification::make()
                    ->title(__('Payroll Approved'))
                    ->body(__('Payroll has been approved and synced with financial system.'))
                    ->success()
                    ->send();
            });
    }

    /**
     * Early Installment Payment Action for PayrollRun table.
     * 
     * Allows paying future advance installments while payroll is pending.
     * Creates SalaryTransaction records that will be processed when payroll is approved.
     */
    public static function earlyInstallmentPaymentAction(): Action
    {
        return Action::make('earlyPayment')
            ->button()
            ->label(__('lang.early_installment_payment'))
            ->icon('heroicon-o-banknotes')
            ->color('warning')
            ->visible(fn(PayrollRun $record): bool => $record->status === PayrollRun::STATUS_PENDING)
            ->schema(function (PayrollRun $record) {
                // Get all employee IDs in this payroll run
                $employeeIds = $record->payrolls()->pluck('employee_id')->toArray();

                if (empty($employeeIds)) {
                    return [
                        \Filament\Forms\Components\Placeholder::make('no_employees')
                            ->label('')
                            ->content(__('lang.no_employees_in_payroll')),
                    ];
                }

                // Get the current period end date
                $periodEnd = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $record->year, $record->month)));

                // Find unpaid future installments (after current period)
                $installments = EmployeeAdvanceInstallment::query()
                    ->whereIn('employee_id', $employeeIds)
                    ->where('is_paid', false)
                    ->where('status', EmployeeAdvanceInstallment::STATUS_SCHEDULED)
                    ->where('due_date', '>', $periodEnd)
                    ->with(['employee:id,name,employee_no', 'advanceRequest:id,code,advance_amount'])
                    ->orderBy('employee_id')
                    ->orderBy('due_date')
                    ->get();

                if ($installments->isEmpty()) {
                    return [
                        \Filament\Forms\Components\Placeholder::make('no_installments')
                            ->label('')
                            ->content(__('lang.no_future_installments')),
                    ];
                }

                // Build options for checkbox list
                $options = $installments->mapWithKeys(function ($inst) {
                    $label = sprintf(
                        '%s (%s) - %s - %s - %s',
                        $inst->employee->name ?? 'N/A',
                        $inst->employee->employee_no ?? 'N/A',
                        $inst->advanceRequest->code ?? 'N/A',
                        formatMoneyWithCurrency($inst->installment_amount),
                        $inst->due_date?->format('Y-m-d') ?? 'N/A'
                    );
                    return [$inst->id => $label];
                })->toArray();

                return [
                    \Filament\Forms\Components\Placeholder::make('info')
                        ->label('')
                        ->content(__('lang.select_installments_to_pay')),
                    CheckboxList::make('installment_ids')
                        ->label(__('lang.future_installments'))
                        ->options($options)
                        ->required()
                        ->columns(1)
                        ->bulkToggleable()
                        ->descriptions(
                            $installments->mapWithKeys(function ($inst) {
                                return [$inst->id => __('lang.sequence') . ': ' . $inst->sequence];
                            })->toArray()
                        ),
                ];
            })
            ->modalHeading(__('lang.early_installment_payment'))
            ->modalDescription(__('lang.early_installment_payment_description'))
            ->modalSubmitActionLabel(__('lang.add_to_payroll'))
            ->action(function (PayrollRun $record, array $data): void {
                $installmentIds = $data['installment_ids'] ?? [];

                if (empty($installmentIds)) {
                    \Filament\Notifications\Notification::make()
                        ->title(__('lang.error'))
                        ->body(__('lang.no_installments_selected'))
                        ->danger()
                        ->send();
                    return;
                }

                $installments = EmployeeAdvanceInstallment::whereIn('id', $installmentIds)
                    ->with(['advanceRequest:id,code'])
                    ->get();

                $periodEnd = \Carbon\Carbon::create($record->year, $record->month, 1)->endOfMonth();
                $createdCount = 0;

                foreach ($installments as $installment) {
                    // Find the payroll for this employee
                    $payroll = $record->payrolls()
                        ->where('employee_id', $installment->employee_id)
                        ->first();

                    if (!$payroll) {
                        continue;
                    }

                    // Check if SalaryTransaction already exists for this installment
                    $exists = SalaryTransaction::where('reference_type', EmployeeAdvanceInstallment::class)
                        ->where('reference_id', $installment->id)
                        ->where('payroll_run_id', $record->id)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    // Build description
                    $desc = __('lang.early_installment_payment') . ' (' .
                        ($installment->advanceRequest->code ?? 'N/A') . ', ' .
                        __('lang.sequence') . ' ' . $installment->sequence . ', ' .
                        __('lang.due_date') . ' ' . $installment->due_date?->format('Y-m-d') . ')';

                    // Create SalaryTransaction
                    SalaryTransaction::create([
                        'employee_id' => $installment->employee_id,
                        'payroll_id' => $payroll->id,
                        'payroll_run_id' => $record->id,
                        'date' => $periodEnd->toDateString(),
                        'amount' => $installment->installment_amount,
                        'currency' => SalaryTransaction::defaultCurrency(),
                        'type' => SalaryTransactionType::TYPE_DEDUCTION->value,
                        'sub_type' => 'early_advance_installment',
                        'reference_type' => EmployeeAdvanceInstallment::class,
                        'reference_id' => $installment->id,
                        'description' => $desc,
                        'operation' => SalaryTransaction::OPERATION_SUB,
                        'year' => $record->year,
                        'month' => $record->month,
                        'created_by' => auth()->id(),
                        'status' => SalaryTransaction::STATUS_PENDING,
                    ]);

                    // Update payroll total_deductions
                    $payroll->increment('total_deductions', $installment->installment_amount);
                    $payroll->decrement('net_salary', $installment->installment_amount);

                    $createdCount++;
                }

                // Update PayrollRun totals
                $record->refresh();
                $record->total_deductions = $record->payrolls()->sum('total_deductions');
                $record->total_net = $record->payrolls()->sum('net_salary');
                $record->save();

                \Filament\Notifications\Notification::make()
                    ->title(__('lang.success'))
                    ->body(__('lang.early_installments_added', ['count' => $createdCount]))
                    ->success()
                    ->send();
            });
    }

    public static function getRelations(): array
    {
        return [
            PayrollsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrolls::route('/'),
            'create' => Pages\CreatePayroll::route('/create'),
            'view' => Pages\ViewPayroll::route('/{record}'),
            'edit' => Pages\EditPayroll::route('/{record}/edit'),
            // 'runPayroll'    => RunPayroll::route('/run-payroll')
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ListPayrolls::class,
            Pages\CreatePayroll::class,
            Pages\ViewPayroll::class,
            Pages\EditPayroll::class,
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forBranchManager()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
