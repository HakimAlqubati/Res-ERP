<?php

namespace App\Filament\Resources\BranchResource\RelationManagers;

use App\Filament\Resources\EmployeeResource\Schemas\EmployeeForm;
use App\Filament\Resources\EmployeeResource\Tables\EmployeeTable;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EmployeesRelationManager extends RelationManager
{
    protected static string $relationship = 'employees';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('lang.employees');
    }

    public static function getModelLabel(): ?string
    {
        return __('lang.employee');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->employees()->count();
    }

    public function form(Schema $schema): Schema
    {
        return EmployeeForm::configure($schema, $this->getOwnerRecord()->id);
    }

    public function table(Table $table): Table
    {
        return EmployeeTable::configure($table)
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
