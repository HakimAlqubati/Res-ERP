<?php

namespace App\Filament\Resources\BranchResource\RelationManagers;

use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Forms\EquipmentForm;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Tables\EquipmentTable;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EquipmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'equipments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('lang.equipments');
    }

    public static function getModelLabel(): ?string
    {
        return __('lang.equipment');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->equipments()->count();
    }

    public function form(Schema $schema): Schema
    {
        return EquipmentForm::configure($schema, $this->getOwnerRecord()->id);
    }

    public function table(Table $table): Table
    {
        return EquipmentTable::configure($table)
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
