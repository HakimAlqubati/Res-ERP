<?php

namespace App\Filament\Resources\EmployeePaymentMethods;

use App\Filament\Clusters\HRSalarySettingCluster;
use App\Filament\Resources\EmployeePaymentMethods\Pages\CreateEmployeePaymentMethod;
use App\Filament\Resources\EmployeePaymentMethods\Pages\EditEmployeePaymentMethod;
use App\Filament\Resources\EmployeePaymentMethods\Pages\ListEmployeePaymentMethods;
use App\Filament\Resources\EmployeePaymentMethods\Schemas\EmployeePaymentMethodForm;
use App\Filament\Resources\EmployeePaymentMethods\Tables\EmployeePaymentMethodsTable;
use App\Models\EmployeePaymentMethod;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeePaymentMethodResource extends Resource
{
    protected static ?string $model = EmployeePaymentMethod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CircleStack;

    protected static ?string $recordTitleAttribute = 'name';

    
    protected static ?string $cluster = HRSalarySettingCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 7;
    
        public static function getNavigationLabel(): string
    {
        return 'Payment Methods';
    }
    public static function getPluralLabel(): ?string
    {
        return 'Payment Methods';
    }

    public static function getModelLabel(): string
    {
        return 'Payment Method';
    }
    public static function getLabel(): ?string
    {
        return 'Payment Methods';
    }

    public static function form(Schema $schema): Schema
    {
        return EmployeePaymentMethodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeePaymentMethodsTable::configure($table);
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
            'index' => ListEmployeePaymentMethods::route('/'),
            'create' => CreateEmployeePaymentMethod::route('/create'),
            'edit' => EditEmployeePaymentMethod::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
