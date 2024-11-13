<?php

namespace App\Filament\Widgets;

use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource;
use App\Models\Task;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TaskWidget extends BaseWidget
{
    // protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected function getTableHeading(): string
    {
        return 'My tasks'; // Set your desired heading here
    }
    public function table(Table $table): Table
    {
        return $table
            ->query(function(){
                $query = TaskResource::getEloquentQuery();
                if(!isSuperAdmin()){
                    $query->where('assigned_to',auth()?->user()?->employee?->id);
                }
                return $query;
            })
            ->defaultPaginationPageOption(5)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->sortable()->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('title')->sortable()->words(2)
                    // ->color(Color::Blue)
                    ->tooltip(fn($record):string=> $record->title . '  Task no #'. $record->id)
                    ->size(TextColumnSize::Small)
                    ->color(Color::Green)
                    // ->weight(FontWeight::ExtraBold)
                    // ->description('Click')
                    ->searchable()->icon('heroicon-o-eye')
                    ->url(fn($record):string=> 'admin/h-r-tasks-system/tasks/'.$record->id.'/edit')->openUrlInNewTab(),
                TextColumn::make('step_count')->label('Steps')
                    ->color(Color::Blue)->alignCenter(true)
                    ->searchable()->toggleable(isToggledHiddenByDefault:true),
                TextColumn::make('views')->label('Views')->sortable()
                    ->color(Color::Blue)->alignCenter(true)
                    ->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('task_status')->label('Status')
                    ->badge()->alignCenter(true)
                    ->icon(fn(string $state): string => match ($state) {
                        Task::STATUS_NEW =>  Task::ICON_NEW,
                        // Task::STATUS_PENDING =>  Task::ICON_PENDING,
                        Task::STATUS_IN_PROGRESS =>  Task::ICON_IN_PROGRESS,
                        Task::STATUS_CLOSED =>  Task::ICON_CLOSED,
                        Task::STATUS_REJECTED =>  Task::ICON_REJECTED,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        Task::STATUS_NEW => Task::STATUS_NEW,
                        // Task::STATUS_PENDING => Task::COLOR_PENDING,
                        Task::STATUS_IN_PROGRESS => Task::COLOR_IN_PROGRESS,

                        Task::STATUS_CLOSED => Task::COLOR_CLOSED,
                        Task::STATUS_REJECTED => Task::COLOR_REJECTED,
                        // default => 'gray', // Fallback color in case of unknown status
                    })
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('assigned.name')
                    ->label('Assigned To')
                    ->searchable()->wrap()->limit(20)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('createdby.name')
                    ->label('created By')->toggleable(isToggledHiddenByDefault:true)
                    ->searchable()->wrap()->limit(20)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('assignedby.name')
                    ->label('Assigned By')
                    ->searchable()
                    ->tooltip(fn($record):string=>$record?->assignedby?->name?? '')
                    ->size(TextColumnSize::Small)
                    // ->wrap()
                    ->limit(10)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])->striped()
            ->filters([
                SelectFilter::make('task_status')->label('Status')
                ->options([
                    Task::STATUS_NEW => 'New',
                    Task::STATUS_IN_PROGRESS => 'In Progress',
                    Task::STATUS_CLOSED => 'Closed',
                    Task::STATUS_REJECTED => 'Rejected',
                ]),
            ])
            ->actions([
                
                // Action::make('Show')
                // ->icon('heroicon-o-eye')
                // ->url(fn($record):string=> 'admin/h-r-tasks-system/tasks/'.$record->id.'/edit')->openUrlInNewTab(),
            ])
        ;
    }

}
