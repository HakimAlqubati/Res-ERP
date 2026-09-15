<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers;

use App\Models\EmployeeServiceTermination;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ServiceTerminationRelationManager extends RelationManager
{
    protected static string $relationship = 'serviceTerminations';

    protected static ?string $title = 'Service & Termination History';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->serviceTerminations()->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        $hasPending = $ownerRecord->serviceTerminations()
            ->where('status', EmployeeServiceTermination::STATUS_PENDING)
            ->exists();

        if ($hasPending) {
            return 'warning';
        }

        return 'gray';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Termination Details')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('service_start_date')
                                    ->label('Service Start Date')
                                    ->disabled(),

                                DatePicker::make('termination_date')
                                    ->label('Termination Date')
                                    ->disabled(),

                                TextInput::make('status')
                                    ->label('Status')
                                    ->disabled(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Textarea::make('termination_reason')
                                    ->label('Termination Reason')
                                    ->columnSpanFull()
                                    ->disabled(),

                                Textarea::make('notes')
                                    ->label('Notes')
                                    ->columnSpanFull()
                                    ->disabled(),
                            ]),

                        Grid::make(3)
                            ->schema([
                                DateTimePicker::make('approved_at')
                                    ->label('Approved At')
                                    ->disabled(),

                                TextInput::make('approvedBy.name')
                                    ->label('Approved By')
                                    ->disabled(),

                                DateTimePicker::make('rehired_at')
                                    ->label('Rehired At')
                                    ->disabled(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('rejectedBy.name')
                                    ->label('Rejected By')
                                    ->disabled(),

                                Textarea::make('rejection_reason')
                                    ->label('Rejection Reason')
                                    ->disabled(),
                            ])
                            ->visible(fn ($record) => filled($record?->rejected_at)),
                    ])
                    ->compact(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('termination_reason')
            ->striped()
            ->defaultSort('termination_date', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('service_start_date')
                    ->label('Service Start')
                    ->date('Y-m-d')
                    ->placeholder(fn ($record) => $record->employee?->join_date ? \Carbon\Carbon::parse($record->employee->join_date)->format('Y-m-d') : '-')
                    ->sortable(),

                TextColumn::make('termination_date')
                    ->label('Termination Date')
                    ->date('Y-m-d')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => EmployeeServiceTermination::STATUS_PENDING,
                        'success' => EmployeeServiceTermination::STATUS_APPROVED,
                        'danger'  => EmployeeServiceTermination::STATUS_REJECTED,
                        'gray'    => EmployeeServiceTermination::STATUS_CANCEL,
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        EmployeeServiceTermination::STATUS_PENDING  => 'Pending',
                        EmployeeServiceTermination::STATUS_APPROVED => 'Approved',
                        EmployeeServiceTermination::STATUS_REJECTED => 'Rejected',
                        EmployeeServiceTermination::STATUS_CANCEL   => 'Cancelled',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                TextColumn::make('rehired_at')
                    ->label('Rehired At')
                    ->date('Y-m-d')
                    ->placeholder('Not Rehired')
                    ->badge()
                    ->color(fn ($state) => $state ? 'info' : 'gray')
                    ->sortable(),

                TextColumn::make('termination_reason')
                    ->label('Reason')
                    ->limit(35)
                    ->tooltip(fn ($state) => $state)
                    ->searchable(),

                TextColumn::make('approvedBy.name')
                    ->label('Approved By')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('approved_at')
                    ->label('Approved At')
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('rejectedBy.name')
                    ->label('Rejected By')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->size('xs')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('View')
                    ->icon('heroicon-m-eye'),
            ]);
    }
}
