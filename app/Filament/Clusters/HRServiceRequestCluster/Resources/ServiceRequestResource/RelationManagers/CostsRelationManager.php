<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\RelationManagers;

use App\Models\MaintenanceCost;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Relation Manager لإدارة تكاليف الصيانة المرتبطة بطلب الصيانة
 */
class CostsRelationManager extends RelationManager
{
    protected static string $relationship = 'costs';

    protected static ?string $title = 'Costs';

   protected static string | BackedEnum | null $icon = null;

    protected static IconPosition $iconPosition = IconPosition::Before;

     public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->costs->count();
    }
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('amount')
                    ->label(__('Amount'))
                    ->numeric()
                    ->required()
                    ->prefix('$')
                    ->minValue(0.01)
                    ->step(0.01),

                Select::make('cost_type')
                    ->label(__('Cost Type'))
                    ->options(MaintenanceCost::getTypeOptions())
                    ->default(MaintenanceCost::TYPE_REPAIR)
                    ->required(),

                DatePicker::make('cost_date')
                    ->label(__('Cost Date'))
                    ->default(now())
                    ->required(),

                Textarea::make('description')
                    ->label(__('Description'))
                    ->rows(3)
                    ->columnSpanFull()
                    ->maxLength(500),
            ])
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->money('MYR')
                    ->sortable()
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Sum::make()
                            ->money('MYR')
                            ->label(__('Total')),
                    ]),

                TextColumn::make('cost_type')
                    ->label(__('Type'))
                    ->badge()
                    ->formatStateUsing(fn($state) => MaintenanceCost::TYPE_LABELS[$state] ?? $state)
                    ->colors([
                        'danger' => MaintenanceCost::TYPE_REPAIR,
                        'warning' => MaintenanceCost::TYPE_PARTS,
                        'info' => MaintenanceCost::TYPE_LABOR,
                        'success' => MaintenanceCost::TYPE_PURCHASE,
                        'secondary' => MaintenanceCost::TYPE_OTHER,
                    ])
                    ->sortable(),

                TextColumn::make('cost_date')
                    ->label(__('Date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('description')
                    ->label(__('Description'))
                    ->limit(30)
                    ->tooltip(fn($state) => $state)
                    ->toggleable(),

                IconColumn::make('synced_to_financial')
                    ->label(__('Synced'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                TextColumn::make('creator.name')
                    ->label(__('Created By'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['branch_id'] = $this->getOwnerRecord()->branch_id;
                        $data['created_by'] = auth()->id();
                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
