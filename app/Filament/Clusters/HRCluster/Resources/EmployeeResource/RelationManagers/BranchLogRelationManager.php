<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
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

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->branchLogs()->count();
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->branchLogs()->count();
        return match (true) {
            $count === 0 => 'gray',
            $count < 3 => 'warning',
            default => 'success',
        };
    }

    public static function getBadgeTooltip(Model $ownerRecord, string $pageClass): ?string
    {
        return "Branch Logs Count: " . $ownerRecord->branchLogs()->count();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Branch Assignment')->columnSpanFull()
                    ->description('Manage employee movement between branches.')
                    ->schema([
                        Select::make('branch_id')
                            ->label('Target Branch')
                            ->relationship('branch', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('start_at')
                                    ->label('Transfer Date')
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->displayFormat('Y-m-d')
                                    ->suffixIcon('heroicon-m-calendar-days'),

                                DatePicker::make('end_at')
                                    ->label('End Date (Optional)')
                                    ->native(false)
                                    ->displayFormat('Y-m-d')
                                    ->afterOrEqual('start_at')
                                    ->suffixIcon('heroicon-m-calendar-days'),
                            ]),
                    ])
                    ->compact(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('branch.name')
            ->striped()
            ->defaultSort('start_at', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('start_at')
                    ->label('Start Date')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label('End Date')
                    ->date('Y-m-d')
                    ->placeholder('Active/Ongoing')
                    ->sortable(),
                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->size('xs')
                    ->color('gray'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-m-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();
                        return $data;
                    })
                    ->visible(fn() => isHakimOrAdel())
                    ->successNotificationTitle('Branch log entry created'),
            ])
            ->recordActions([
                EditAction::make()
                    ->icon('heroicon-m-pencil-square')
                    ->successNotificationTitle('Log entry updated'),
                DeleteAction::make()->visible(fn() => isHakimOrAdel()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn() => isHakimOrAdel()),
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
