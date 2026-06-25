<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers;

use App\Models\LeaveType;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class LeaveBalancesRelationManager extends RelationManager
{
    protected static string $relationship = 'activeLeaveBalances';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('lang.leave_balance') ?? 'Leave Balances';
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->leaveBalances()->count();
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->leaveBalances()->count();
        return match (true) {
            $count === 0 => 'gray',
            default => 'success',
        };
    }

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table
            ->striped()

            ->columns([
                TextColumn::make('year')
                    ->label(app()->getLocale() === 'ar' ? 'السنة' : 'Year')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('month')
                    ->label(app()->getLocale() === 'ar' ? 'الشهر' : 'Month')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if (blank($state)) {
                            return app()->getLocale() === 'ar' ? 'سنوي' : 'Annual';
                        }
                        try {
                            $monthName = Carbon::create()->month((int) $state)->translatedFormat('F');
                            return $monthName;
                        } catch (\Exception $e) {
                            return $state;
                        }
                    })
                    ->searchable(),

                TextColumn::make('leaveType.name')
                    ->label(app()->getLocale() === 'ar' ? 'نوع الإجازة' : 'Leave Type')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('entitled_days')
                    ->label(app()->getLocale() === 'ar' ? 'الأيام المستحقة' : 'Entitled Days')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('supposed_days')
                    ->label(app()->getLocale() === 'ar' ? 'الأيام المفترضة' : 'Supposed Days')
                    ->sortable()
                    ->alignCenter()
                    ->color('gray'),

                TextColumn::make('used_days')
                    ->label(app()->getLocale() === 'ar' ? 'الأيام المستخدمة' : 'Used Days')
                    ->sortable()
                    ->alignCenter()
                    ->color('danger'),

                TextColumn::make('pending_days')
                    ->label(app()->getLocale() === 'ar' ? 'الأيام المعلقة' : 'Pending Days')
                    ->sortable()
                    ->alignCenter()
                    ->color('warning'),

                TextColumn::make('available_balance')
                    ->label(app()->getLocale() === 'ar' ? 'الرصيد المتاح' : 'Available Balance')
                    ->alignCenter()
                    ->weight('bold')
                    ->color('success'),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Read-only
            ])
            ->bulkActions([
                // Read-only
            ]);
    }

    protected function canCreate(): bool
    {
        return false;
    }

    protected function canEdit(Model $record): bool
    {
        return false;
    }

    protected function canDelete(Model $record): bool
    {
        return false;
    }

    protected function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('leaveType', function (Builder $query) {
                $query
                    // ->where('active', true)
                    ->whereNull('deleted_at')
                    ->whereIn('type', [LeaveType::TYPE_MONTHLY, LeaveType::TYPE_YEARLY, LeaveType::TYPE_SPECIAL]);
            });
    }
}
