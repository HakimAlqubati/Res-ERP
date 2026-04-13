<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers;

use App\Models\AdvanceWage;
use App\Rules\HR\Payroll\AdvanceWageLimitRule;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use App\Services\HR\Payroll\PayrollLockGuard;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AdvanceWagesRelationManager extends RelationManager
{
    protected static string $relationship = 'advanceWages';

    protected static ?string $title = 'Advance Wages';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->advanceWages()
            // ->where('status', AdvanceWage::STATUS_PENDING)
            ->count();
        return $count;
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return 'warning';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([

            Grid::make(3)->schema([
                TextInput::make('amount')
                    ->label(__('Amount'))
                    ->numeric()
                    ->minValue(0.01)
                    // ->maxValue(fn() => $this->getOwnerRecord()->salary ?? 99999)
                    ->required()
                    ->live(onBlur: true)
                    ->rules([
                        fn(Get $get) => new AdvanceWageLimitRule(
                            $this->getOwnerRecord()->id,
                            $get('year'),
                            $get('month')
                        )
                    ])
                    ->columnSpan(1),

                Select::make('year')
                    ->label(__('Year'))
                    ->options(collect(range(now()->year - 1, now()->year + 1))->mapWithKeys(fn($y) => [$y => $y]))
                    ->default(now()->year)
                    ->required()
                    ->live()
                    ->columnSpan(1),

                Select::make('month')
                    ->label(__('Month'))
                    ->options(collect(range(1, 12))->mapWithKeys(fn($m) => [$m => now()->setMonth($m)->translatedFormat('F')]))
                    ->default(now()->month)
                    ->required()
                    ->live()
                    ->columnSpan(1),

            ])->columnSpanFull(),

            Grid::make(3)->schema([
                Select::make('payment_method')
                    ->label(__('lang.payment_method'))
                    ->options(AdvanceWage::paymentMethods())
                    ->default(AdvanceWage::PAYMENT_METHOD_CASH)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        if ($state === AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER) {
                            $set('bank_account_number', $this->getOwnerRecord()->bank_account_number);
                        }
                    })
                    ->columnSpan(1),

                TextInput::make('bank_account_number')
                    ->label(__('lang.bank_account_number'))
                    ->visible(fn(Get $get) => $get('payment_method') === AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER)
                    ->required(fn(Get $get) => $get('payment_method') === AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER)
                    ->columnSpan(1),

                TextInput::make('transaction_number')
                    ->label(__('lang.transaction_number'))
                    ->visible(fn(Get $get) => $get('payment_method') === AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER)
                    ->required(fn(Get $get) => $get('payment_method') === AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER)
                    ->columnSpan(1),
            ])->columnSpanFull(),

            TextInput::make('reason')
                ->label(__('Reason'))
                ->maxLength(255)->required()
                ->columnSpanFull(),



        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount')
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('year')
                    ->label(__('Year'))
                    ->sortable(),

                TextColumn::make('month')
                    ->label(__('Month'))
                    ->formatStateUsing(fn($state) => now()->setMonth($state)->translatedFormat('F'))
                    ->sortable(),

                TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->money(fn() => $this->getOwnerRecord()->branch?->currency ?? 'MYR')
                    ->sortable(),

                TextColumn::make('reason')
                    ->label(__('Reason'))
                    ->limit(40)
                    ->tooltip(fn($record) => $record->reason)
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        AdvanceWage::STATUS_PENDING   => 'warning',
                        AdvanceWage::STATUS_SETTLED   => 'success',
                        AdvanceWage::STATUS_CANCELLED => 'danger',
                        default                       => 'gray',
                    })
                    ->formatStateUsing(fn($state) => AdvanceWage::statuses()[$state] ?? $state),

                TextColumn::make('settledPayroll.name')
                    ->label(__('Settled In'))
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('settled_at')
                    ->label(__('Settled At'))
                    ->dateTime('Y-m-d')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('creator.name')
                    ->label(__('Created By'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(AdvanceWage::statuses()),

                SelectFilter::make('year')
                    ->label(__('Year'))
                    ->options(collect(range(now()->year - 2, now()->year))->mapWithKeys(fn($y) => [$y => $y])),

                SelectFilter::make('month')
                    ->label(__('Month'))
                    ->options(collect(range(1, 12))->mapWithKeys(fn($m) => [$m => now()->setMonth($m)->translatedFormat('F')])),
            ])
            ->headerActions([
                CreateAction::make()
                    ->action(function (array $data, CreateAction $action): void {
                        try {
                            $this->getOwnerRecord()->advanceWages()->create([
                                'amount' => $data['amount'],
                                'year' => $data['year'],
                                'month' => $data['month'],
                                'reason' => $data['reason'] ?? null,
                                'payment_method' => $data['payment_method'],
                                'bank_account_number' => $data['bank_account_number'] ?? null,
                                'transaction_number' => $data['transaction_number'] ?? null,
                                'branch_id' => $this->getOwnerRecord()->branch_id,
                                'created_by' => auth()->id(),
                            ]);

                            Notification::make()
                                ->title(__('Advance wage recorded successfully.'))
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title(__('Error'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label(__('Edit'))
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn(AdvanceWage $record) => null)
                    ->fillForm(fn(AdvanceWage $record): array => $record->toArray())
                    ->schema(fn() => $this->form($this->makeSchema())->getComponents())
                    ->action(function (AdvanceWage $record, array $data): void {
                        try {
                            $record->update($data);
                            Notification::make()->success()->title(__('Updated successfully.'))->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title(__('Error'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->disabled(fn (AdvanceWage $record) => app(PayrollLockGuard::class)->isLocked(
                        (int) $record->employee_id,
                        (int) $record->year,
                        (int) $record->month
                    ))
                // ->visible(fn(AdvanceWage $record) => $record->status === AdvanceWage::STATUS_PENDING)
                ,

                Action::make('cancel')
                    ->label(__('Cancel'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(AdvanceWage $record) => in_array(
                        $record->status,
                        [AdvanceWage::STATUS_PENDING, AdvanceWage::STATUS_SETTLED]
                    ))
                    ->action(function (AdvanceWage $record): void {
                        $record->cancel();
                        Notification::make()->success()->title(__('Advance wage cancelled.'))->send();
                    })
                    ->disabled(fn (AdvanceWage $record) => app(PayrollLockGuard::class)->isLocked(
                        (int) $record->employee_id,
                        (int) $record->year,
                        (int) $record->month
                    )),
                Action::make('approve')
                    ->label(__('Approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(AdvanceWage $record) => in_array(
                        $record->status,
                        [AdvanceWage::STATUS_PENDING, AdvanceWage::STATUS_CANCELLED]
                    ))
                    ->action(function (AdvanceWage $record): void {
                        $record->update(['status' => AdvanceWage::STATUS_SETTLED]);
                        Notification::make()->success()->title(__('Advance wage approved.'))->send();
                    })
                    ->disabled(fn (AdvanceWage $record) => app(PayrollLockGuard::class)->isLocked(
                        (int) $record->employee_id,
                        (int) $record->year,
                        (int) $record->month
                    )),

                Action::make('delete')
                    ->label(__('Delete'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    // ->visible(fn(AdvanceWage $record) => $record->status === AdvanceWage::STATUS_PENDING)
                    ->action(function (AdvanceWage $record): void {
                        $record->delete();
                        Notification::make()->success()->title(__('Deleted successfully.'))->send();
                    })
                    ->disabled(fn (AdvanceWage $record) => app(PayrollLockGuard::class)->isLocked(
                        (int) $record->employee_id,
                        (int) $record->year,
                        (int) $record->month
                    )),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }

    protected function canCreate(): bool
    {
        return isSuperAdmin() || isHR() || isSystemManager() || isBranchManager();
    }

    protected function canEdit(Model $record): bool
    {
        return $record->status === AdvanceWage::STATUS_PENDING
            && (isSuperAdmin() || isHR() || isSystemManager());
    }

    protected function canDelete(Model $record): bool
    {
        return $record->status === AdvanceWage::STATUS_PENDING
            && (isSuperAdmin() || isHR() || isSystemManager());
    }
}
