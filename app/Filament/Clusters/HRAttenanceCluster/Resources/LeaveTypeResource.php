<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\LeaveTypeResource\Pages;
use App\Filament\Clusters\HRAttenanceCluster\Resources\LeaveTypeResource\RelationManagers;
use App\Filament\Clusters\HRLeaveManagementCluster;
use App\Models\LeaveType;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRLeaveManagementCluster::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 4;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->schema([
                    Grid::make()->label('')->columns(7)->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Leave type name')
                            ->unique(ignoreRecord: true)->columnSpan(2)
                            ->required(),

                        Forms\Components\TextInput::make('count_days')
                            ->label('Number of days')
                            ->numeric()
                            ->required(),

                        Select::make('type')->label('Type')->options(LeaveType::getTypes()),
                        Select::make('balance_period')->label('Accural cycle')->options(LeaveType::getBalancePeriods()),
                        Forms\Components\Toggle::make('active')
                            ->label('Active')->inline(false)
                            ->default(true),
                        Forms\Components\Toggle::make('is_paid')
                            ->label('Is paid')->inline(false)
                            ->default(true),

                    ]),
                    Forms\Components\Textarea::make('description')->columnSpanFull()
                        ->label('Description')
                        ->nullable(),
                ]),



            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Leave Type')
                ->toggleable()
                ,
                Tables\Columns\TextColumn::make('count_days')->label('Number of Days')->alignCenter(true)->toggleable(),

                Tables\Columns\TextColumn::make('type_label')->label('Type')->alignCenter(true)->toggleable(),
                Tables\Columns\TextColumn::make('balance_period_label')->label('Accural cycle')->alignCenter(true),
                Tables\Columns\TextColumn::make('created_at')->label('Created At')->toggleable(isToggledHiddenByDefault: true)->dateTime(),
                Tables\Columns\BooleanColumn::make('active')->toggleable()
                    ->label('Active')->alignCenter(true)
                    ->boolean(),
                Tables\Columns\BooleanColumn::make('is_paid')
                    ->label('Is paid')->alignCenter(true)->toggleable()
                    ->boolean(),

            ])
            ->filters([
                SelectFilter::make('type')->options(LeaveType::getTypes()),
                SelectFilter::make('balance_period')->options(LeaveType::getBalancePeriods())->label('Accural cycle'),
            ],FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListLeaveTypes::route('/'),
            'create' => Pages\CreateLeaveType::route('/create'),
            'edit' => Pages\EditLeaveType::route('/{record}/edit'),
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
