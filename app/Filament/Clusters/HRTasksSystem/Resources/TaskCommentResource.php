<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskCommentResource\Pages\ListTaskComments;
use App\Filament\Clusters\HRTasksSystem;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskCommentResource\Pages;
use App\Models\Task;
use App\Models\TaskComment;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TaskCommentResource extends Resource
{
    protected static ?string $model = TaskComment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    // protected static ?string $cluster = HRTasksSystem::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('task_id')->options(Task::select('id')->get()->pluck('id')),
                Textarea::make('comment')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('task_id')->searchable()->sortable(),
                TextColumn::make('employee.name')->label('Commentor')->searchable()->sortable(),
                TextColumn::make('comment')->searchable(),
                TextColumn::make('updated_at')->searchable()->sortable(),
                TextColumn::make('created_at')->searchable()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make(),
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
            'index' => ListTaskComments::route('/'),
            // 'create' => Pages\CreateTaskComment::route('/create'),
            // 'edit' => Pages\EditTaskComment::route('/{record}/edit'),
        ];
    }
}
