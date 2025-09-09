<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
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
use App\Models\LeaveType;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeaveTypeResource extends Resource
{
    protected static ?string $model = LeaveType::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRLeaveManagementCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 4;
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->schema([
                    Grid::make()->columnSpanFull()->columns(7)->schema([
                        TextInput::make('name')
                            ->label('Leave type name')
                            ->unique(ignoreRecord: true)->columnSpan(2)
                            ->required(),

                        TextInput::make('count_days')
                            ->label('Number of days')
                            ->numeric()
                            ->required(),

                        Select::make('type')->label('Type')->options(LeaveType::getTypes()),
                        Select::make('balance_period')->label('Accural cycle')->options(LeaveType::getBalancePeriods()),
                        Toggle::make('active')
                            ->label('Active')->inline(false)
                            ->default(true),
                        Toggle::make('is_paid')
                            ->label('Is paid')->inline(false)
                            ->default(true),

                    ]),
                    Textarea::make('description')->columnSpanFull()
                        ->label('Description')
                        ->nullable(),
                ]),



            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->columns([
                TextColumn::make('name')->label('Leave Type')
                ->toggleable()
                ,
                TextColumn::make('count_days')->label('Number of Days')->alignCenter(true)->toggleable(),

                TextColumn::make('type_label')->label('Type')->alignCenter(true)->toggleable(),
                TextColumn::make('balance_period_label')->label('Accural cycle')->alignCenter(true),
                TextColumn::make('created_at')->label('Created At')->toggleable(isToggledHiddenByDefault: true)->dateTime(),
                BooleanColumn::make('active')->toggleable()
                    ->label('Active')->alignCenter(true)
                    ->boolean(),
                BooleanColumn::make('is_paid')
                    ->label('Is paid')->alignCenter(true)->toggleable()
                    ->boolean(),

            ])
            ->filters([
                SelectFilter::make('type')->options(LeaveType::getTypes()),
                SelectFilter::make('balance_period')->options(LeaveType::getBalancePeriods())->label('Accural cycle'),
            ],FiltersLayout::AboveContent)
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
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
        return static::getModel()::count();
    }

    public static function canViewAny(): bool
    {
        if (isSystemManager() || isSuperAdmin()) {
            return true;
        }
        return false;
    }
}
