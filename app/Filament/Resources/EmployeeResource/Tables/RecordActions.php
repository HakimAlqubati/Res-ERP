<?php

namespace App\Filament\Resources\EmployeeResource\Tables;

use App\Filament\Clusters\HRCluster\Resources\EmployeeResource\Pages\CheckInstallments;
use App\Filament\Resources\EmployeeResource;
use App\Filament\Resources\EmployeeResource\EmployeeActions;
use App\Models\AdvanceWage;
use App\Models\Branch;
use App\Models\Employee;
use App\Modules\HR\Employee\Services\EmployeeLifecycleService;
use App\Rules\HR\Employee\NoFutureTerminationApprovalRule;
use App\Rules\HR\Payroll\AdvanceWageLimitRule;
use App\Services\S3ImageService;
use Carbon\Carbon;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Throwable;

class RecordActions
{
    public static function actionGroup(): ActionGroup
    {
        return ActionGroup::make([
            Action::make('terminateService')
                ->label(__('lang.terminate_service'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (Employee $record) => $record->active && ! $record->pendingTerminationRequest()->exists())
                ->schema(fn (Employee $record) => [
                    Fieldset::make()->columnSpanFull()->columns(2)->schema([
                        TextInput::make('name')
                            ->label(__('lang.full_name'))
                            ->default($record->name)
                            ->disabled(),
                        DatePicker::make('join_date')
                            ->label(__('lang.join_date'))
                            ->default($record->join_date)
                            ->disabled(),
                        DatePicker::make('termination_date')
                            ->label(__('lang.termination_date'))
                            ->required()
                            ->default(now())
                            ->live()
                            ->columnSpanFull()
                            ->rules([
                                fn (Employee $record) => function (string $attribute, $value, Closure $fail) use ($record) {
                                    $unpaidBalance = (float) $record->advancedInstallments()
                                        ->where('is_paid', false)
                                        ->sum('installment_amount');

                                    if ($unpaidBalance > 0) {
                                        $fail(__('lang.cannot_process_financial_clearance', [
                                            'amount' => number_format($unpaidBalance, 2),
                                        ]) ?: 'Cannot process financial clearance. The employee has outstanding advance installments amounting to: '.number_format($unpaidBalance, 2));
                                    }
                                },
                            ]),
                        Toggle::make('auto_approve')
                            ->label('Auto-approve on termination date (via cron job)')
                            ->default(false)
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('termination_date') && Carbon::parse($get('termination_date'))->isFuture()),
                        Textarea::make('termination_reason')
                            ->label(__('lang.termination_reason'))
                            ->columnSpanFull()
                            ->required(),
                        Textarea::make('notes')
                            ->label(__('lang.notes'))
                            ->columnSpanFull(),
                    ]),

                ])
                ->databaseTransaction()
                ->action(function (Employee $record, array $data) {
                    try {
                        app(EmployeeLifecycleService::class)->requestTermination($record, $data);

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
                ->visible(fn (Employee $record) => $record->pendingTerminationRequest()
                    ->exists())
                ->schema(fn (Employee $record) => [
                    DatePicker::make('termination_date')
                        ->label(__('lang.termination_date'))
                        ->default($record->serviceTermination->termination_date)
                        ->required()
                        ->live()
                        ->rules([new NoFutureTerminationApprovalRule($record->serviceTermination->termination_date)]),
                    Textarea::make('termination_reason')
                        ->label(__('lang.termination_reason'))
                        ->default($record->serviceTermination->termination_reason)
                        ->required(),
                    Textarea::make('notes')
                        ->label(__('lang.notes'))
                        ->default($record->serviceTermination->notes),
                    Toggle::make('auto_approve')
                        ->label('Auto-approve on termination date (via cron job)')
                        ->default($record->serviceTermination->auto_approve ?? false)
                        ->live()
                        ->afterStateUpdated(function (bool $state) use ($record) {
                            $record->serviceTermination->updateQuietly([
                                'auto_approve' => $state,
                                'scheduled_approver_id' => $state ? auth()->id() : null,
                            ]);
                        })
                        ->columnSpanFull()
                        ->visible(fn (Get $get) => $get('termination_date') && Carbon::parse($get('termination_date'))->isFuture()),
                ])
                ->label(__('lang.approve_termination'))
                ->color('success')
                // ->requiresConfirmation()
                ->action(function ($record, array $data) {
                    try {
                        $record->serviceTermination->update([
                            'termination_date' => $data['termination_date'],
                            'termination_reason' => $data['termination_reason'],
                            'notes' => $data['notes'] ?? null,
                            'auto_approve' => $data['auto_approve'] ?? false,
                            'scheduled_approver_id' => auth()->id(),
                        ]);

                        // If scheduled for future auto-approval, just save and let the cron job handle it.
                        if (($data['auto_approve'] ?? false) && Carbon::parse($data['termination_date'])->isFuture()) {
                            Notification::make()
                                ->title('Scheduled for auto-approval')
                                ->body('The termination will be approved automatically on the termination date.')
                                ->success()
                                ->send();

                            return;
                        }

                        app(EmployeeLifecycleService::class)
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
                    Textarea::make('rejection_reason')->required()->label(__('lang.rejection_reason')),
                ])
                ->visible(fn (Employee $record) => $record->serviceTermination()->where('status', 'pending')->exists())

                ->icon('heroicon-o-x-circle')
                ->action(function (array $data, $record) {
                    try {
                        app(EmployeeLifecycleService::class)
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
                ->visible(fn (Employee $record) => ! $record->active)
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
                        app(EmployeeLifecycleService::class)->rehire($record, $data);

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
                ->visible(fn ($record) => ! $record->has_user)
                ->schema(fn ($record) => EmployeeResource::createUserForm($record))
                ->action(function (array $data, $record) {
                    $user = $record->createLinkedUser($data);

                    if ($user) {
                        Notification::make()
                            ->title(__('lang.user_created'))
                            ->body(__('lang.user_created_for')." {$record->name}.")
                            ->success()
                            ->send();
                    }
                }),
            Action::make('index')
                ->label(__('lang.aws_indexing'))
                // ->button()
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->requiresConfirmation(fn (Employee $record) => (bool) $record->is_indexed_in_aws)
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

            Action::make('quickEdit')
                ->label('Quick Edit')
                ->icon('heroicon-o-pencil-square')
                ->color('info')
                ->fillForm(fn (Employee $record): array => [
                    'name' => $record->name,
                    'email' => $record->email,
                    'branch_id' => $record->branch_id,
                ])
                ->schema([
                    TextInput::make('name')
                        ->label(__('lang.full_name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label(__('lang.email'))
                        ->email()
                        ->required()
                        ->maxLength(255),
                    Select::make('branch_id')
                        ->label(__('lang.branch'))
                        ->options(Branch::active()->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->preload(),
                ])
                ->action(function (array $data, Employee $record): void {
                    $record->update($data);
                    Notification::make()
                        ->title(__('lang.updated_successfully'))
                        ->success()
                        ->send();
                })
                ->visible(fn () => isHakimOrAdel()),

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
                ->visible(fn (Employee $record) => $record->active)
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('amount')
                            ->label(__('Amount'))
                            ->numeric()
                            ->minValue(0.01)
                            ->required()
                            ->live(onBlur: true)
                            ->rules([
                                fn (Get $get, Employee $record) => new AdvanceWageLimitRule(
                                    $record->id,
                                    (int) now()->setDateFrom(Carbon::parse($get('date') ?: now()))->year,
                                    (int) now()->setDateFrom(Carbon::parse($get('date') ?: now()))->month,
                                ),
                            ])
                            ->columnSpan(1),

                        DatePicker::make('date')
                            ->label(__('Date'))
                            ->default(now()->toDateString())
                            ->required()
                            ->live()
                            ->native(false)
                            ->displayFormat('Y-m-d')
                            ->columnSpan(2),

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
                            ->visible(fn (Get $get) => $get('payment_method') === AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER)
                            ->required(fn (Get $get) => $get('payment_method') === AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER)
                            ->columnSpan(1),

                        TextInput::make('transaction_number')
                            ->label(__('lang.transaction_number'))
                            ->visible(fn (Get $get) => $get('payment_method') === AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER)
                            ->required(fn (Get $get) => $get('payment_method') === AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER)
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
                            'date' => $data['date'],
                            'reason' => $data['reason'],
                            'payment_method' => $data['payment_method'],
                            'bank_account_number' => $data['bank_account_number'] ?? null,
                            'transaction_number' => $data['transaction_number'] ?? null,
                            'branch_id' => $record->branch_id,
                            'created_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title(__('Advance wage recorded successfully.'))
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
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
                ->url(fn ($record) => CheckInstallments::getUrl(['employeeId' => $record->id]))

                ->openUrlInNewTab()->hidden(),
            Action::make('view_shifts')
                ->label('View Shifts')
                ->icon('heroicon-o-clock')
                ->color('info')
                ->modalHeading('Work Periods')
                ->modalSubmitAction(false) // No submit button
                ->modalCancelActionLabel('Close')
                ->action(fn () => null) // No backend action
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
            EmployeeActions::changeBranch(),
            EditAction::make(),
            ViewAction::make(),
            DeleteAction::make(),
            RestoreAction::make(),

        ]);
    }
}
