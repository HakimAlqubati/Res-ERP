<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Actions\BulkActionGroup;
use App\Models\Attendance;
use App\Models\EmployeePeriod;
use App\Models\EmployeePeriodHistory;
use App\Models\WorkPeriod;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BranchLogRelationManager extends RelationManager
{
    protected static string $relationship = 'branchLogs';
    protected static ?string $title = 'Branch Logs';
    // protected static ?string $badge = count($this->ownerRecord->periods);
       public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        // مثال: عدد الشفتات لهذا الموظف
        return $ownerRecord->periods()->count();
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        // تقدر ترجع لون حسب الحالة
        $count = $ownerRecord->periods()->count();

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
        return "Branch Logs Count: " . $ownerRecord->periods()->count();
    }
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
              
            ]);
    }

    public function table(Table $table): Table
    {

        // $explodeHost = explode('.', request()->getHost());

        // $count = count($explodeHost);
        // dd($this->ownerRecord,$this->ownerRecord->branch_id,$count);
        return $table
            ->recordTitleAttribute('branch_id')
            ->striped()
            ->columns([ 
                TextColumn::make('id')->label('ID'),
                TextColumn::make('branch.name')->label('Branch'),
                TextColumn::make('start_at')->label('Start Date')
                    ->date('Y-m-d'), // Format the date
                TextColumn::make('end_at')->label('End Date')
                    ->date('Y-m-d')
                    , // Allow nullable dates for ongoing records
                TextColumn::make('createdBy.name')->label('Created By')
                    ->searchable() // Make the creator searchable in the table

            ])
            ->filters([
                //
            ])
           
            
            ->toolbarActions([
                BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function canCreate(): bool
    {
        if (isSuperAdmin() || isBranchManager() || isSystemManager()) {
            return true;
        }
        return false;
    }
}
