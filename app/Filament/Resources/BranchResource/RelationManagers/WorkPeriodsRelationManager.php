<?php

namespace App\Filament\Resources\BranchResource\RelationManagers;

use App\Filament\Clusters\HRAttenanceCluster\Resources\WorkPeriodResource;
use App\Filament\Resources\BranchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class WorkPeriodsRelationManager extends RelationManager
{
    protected static string $relationship = 'workPeriods';
    protected static ?string $title       = 'Shifts';
    protected static ?string $modelLabel = 'Shift';
    protected static ?string $pluralLabel = 'Shifts';
    protected static ?string $label = 'Shift';


    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        // مثال: عدد الشفتات لهذا الموظف
        return $ownerRecord->workPeriods()->count();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            ...WorkPeriodResource::getFormSchema($this->getOwnerRecord()->id)
        ]);
    }
    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        // تقدر ترجع لون حسب الحالة
        $count = $ownerRecord->workPeriods()->count();

        if ($count === 0) {
            return 'gray';
        }

        if ($count < 3) {
            return 'warning';
        }

        return 'success';
    }

    public static function getBadgeTooltip(Model $ownerRecord, string $pageClass): ?string
    {
        return "Shift Count: " . $ownerRecord->workPeriods()->count();
    }

    public function table(Table $table): Table
    {
        return WorkPeriodResource::table($table);
        return $table
            ->columns([
                // TextColumn::make('id')->label(__('lang.shift_id'))->alignCenter(true)->sortable()->toggleable(isToggledHiddenByDefault: true),
                // TextColumn::make('name')->label(__('lang.name'))->sortable()->searchable(),
                // TextColumn::make('start_at')->label(__('lang.start_date'))->sortable(),
                // TextColumn::make('end_at')->label(__('lang.end_date'))->sortable(),
                // TextColumn::make('supposed_duration')
                //     ->label('Duration')->sortable()->toggleable()->alignCenter(),
                // TextColumn::make('employee_periods_count')
                //     ->counts('employeePeriods')
                //     ->label('Staff No')
                //     ->sortable()
                //     ->alignCenter(),
                // IconColumn::make('active')->boolean()->label(__('lang.active'))->sortable()->alignCenter(true),

            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
