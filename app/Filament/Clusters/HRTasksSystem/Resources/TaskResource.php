<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources;

use App\Filament\Clusters\HRTasksSystem;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource\Pages;
use App\Models\Task;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRTasksSystem::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->autofocus()
                    ->maxLength(255),
                Forms\Components\Select::make('assigned_to')
                    ->label('Assign to')
                    ->required()
                    ->options(User::select('name', 'id')->get()->pluck('name', 'id'))->searchable()
                    ->selectablePlaceholder(false),

                Forms\Components\Textarea::make('description')
                    ->required()
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Select::make('status_id')
                    ->label('Status')
                    ->required()
                    ->default(1)->disabled(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                    ->selectablePlaceholder(false)
                    ->relationship('status', 'name', fn(Builder $query) => $query->orderBy('id')),
                DatePicker::make('due_date')->label('Due date')->required(false),
                Hidden::make('created_by')->default(auth()->user()->id),
                Hidden::make('updated_by')->default(auth()->user()->id),
                Fieldset::make('comments')
                ->hidden('created')
                ->relationship('comments')->label('Comments')->schema([
                    TextInput::make('comment'),
                    Hidden::make('user_id')->default(auth()->user()->id),
                    
                ]),
                // Fieldset::make('Task attachments')->relationship('attachments')->schema([
                //     FileUpload::make('file_path')
                //         ->label('Upload file')
                //         ->columnSpanFull()
                //         ->imagePreviewHeight('250')
                //         ->loadingIndicatorPosition('left')
                //         // ->panelAspectRatio('2:1')
                //         ->panelLayout('integrated')
                //         ->removeUploadedFileButtonPosition('right')
                //         ->uploadButtonPosition('left')
                //         ->uploadProgressIndicatorPosition('left')
                //         ->multiple()
                //         ->panelLayout('grid')
                //         ->reorderable()
                //         ->openable()
                //         ->downloadable()
                //         ->previewable()
                //         // ->storeFiles(false)
                //         // ->uploadingMessage('Uploading attachment...')
                //         ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                //             return (string) str($file->getClientOriginalName())->prepend('task-');
                //         }),
                //     Hidden::make('file_name')->default('test'),
                //     // Hidden::make('file_name')->default('test'),

                //     Hidden::make('created_by')->default(auth()->user()->id),
                //     Hidden::make('updated_by')->default(auth()->user()->id),

                // ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('title')
                    ->description(fn(Task $record): string => $record->description)
                    ->searchable(),
                TextColumn::make('status.name')
                    ->label('Status'),

                Tables\Columns\TextColumn::make('assigned.name')
                    ->label('Assigned To')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('createdby.name')
                    ->label('Assigned By')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
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
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
