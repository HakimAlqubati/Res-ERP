<?php

namespace App\Filament\Clusters\HRCircularCluster\Resources;

use App\Filament\Clusters\HRCircularCluster;
use App\Filament\Clusters\HRCircularCluster\Resources\CircularResource\Pages;
use App\Models\Branch;
use App\Models\Circular;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CircularResource extends Resource
{
    protected static ?string $model = Circular::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRCircularCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?string $modelLabel = 'Engagement';
    protected static ?string $pluralLabel = 'Engagement';

    protected static ?int $navigationSort = 1;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Basic data')
                        ->schema([
                            Fieldset::make()->schema([
                                Grid::make()->columns(2)->schema([
                                    Fieldset::make()->label('Set title of circular & the relased date')->schema([
                                        TextInput::make('title')->label('Subject')
                                            ->required()
                                            ->maxLength(255),
                                        DatePicker::make('released_date')->default(date('Y-m-d'))
                                            ->helperText('Date that will be released')
                                            ->required(),
                                    ]),
                                    Fieldset::make()
                                        ->hiddenOn('view')
                                        ->label('Set the branches that you want to send the circular & the group of users')->schema([
                                        Select::make('branch_ids')->label('Choose branch')
                                            ->options(Branch::where('active', 1)->select('id', 'name')->get()->pluck('name', 'id'))
                                            ->multiple()
                                            ->required()
                                            ->helperText('You can choose multiple branches'),
                                        Select::make('group_id')->label('Group')
                                            ->helperText('The users group that will recieve this circular')
                                            ->options(getUserTypes())
                                            ->required(),
                                    ]),

                                ]),

                                Grid::make()->columns(1)->schema([
                                    RichEditor::make('description'),
                                ]),
                            ]),
                        ]),
                    Wizard\Step::make('Images')->hiddenOn('view')
                        ->schema([
                            Fieldset::make()->label('')->schema([

                                Grid::make()->columns(1)->schema([
                                    FileUpload::make('file_path')
                                        ->label('Add photos')
                                        ->disk('public')
                                        ->directory('circulars')
                                        ->visibility('public')
                                        ->columnSpanFull()
                                        ->imagePreviewHeight('250')
                                        ->image()
                                        ->resize(5)
                                        ->loadingIndicatorPosition('left')
                                    // ->panelAspectRatio('2:1')
                                        ->panelLayout('integrated')
                                        ->removeUploadedFileButtonPosition('right')
                                        ->uploadButtonPosition('left')
                                        ->uploadProgressIndicatorPosition('left')
                                        ->multiple()
                                        ->panelLayout('grid')
                                        ->reorderable()
                                        ->openable()
                                        ->downloadable()
                                    // ->hiddenOn('create')
                                        ->previewable()
                                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                            return (string) str($file->getClientOriginalName())->prepend('circular-');
                                        }),

                                ]),
                            ]),
                        ]),
                ])->columnSpanFull(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
        ->paginated([10, 25, 50, 100])
            ->columns([
                TextColumn::make('title')->label('Subject')->sortable(),
                // TextColumn::make('description')->limit(50),
                TextColumn::make('group.name')->label('Group'),
                TextColumn::make('released_date')->date()->sortable(),
                TextColumn::make('createdBy.name')->label('Created by')->sortable()->toggleable(isToggledHiddenByDefault:true),
                TextColumn::make('created_at')->date()->sortable()->toggleable(isToggledHiddenByDefault:false),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('viewGallery')
                    ->hidden(function ($record) {
                        return $record->photos_count <= 0 ? true : false;
                    })
                    ->label('Browse photos')
                    ->label(function ($record) {
                        return $record->photos_count;
                    })
                    ->modalHeading('Photos')
                    ->modalWidth('lg') // Adjust modal size
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                // ->iconButton()
                    ->button()
                    ->icon('heroicon-o-camera')
                    ->modalContent(function ($record) {
                        return view('filament.resources.circulars.gallery', ['photos' => $record->photos]);
                    }),
                // Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListCirculars::route('/'),
            'create' => Pages\CreateCircular::route('/create'),
            'edit' => Pages\EditCircular::route('/{record}/edit'),
            'view' => Pages\ViewCircular::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function canCreate(): bool
    {
        return true;
    }

    
    public static function canDelete(Model $record): bool
    {
        if (isSuperAdmin() || isSystemManager()) {
            return true;
        }
        return false;
    }


    public static function canDeleteAny(): bool
    {
        if (isSuperAdmin() || isSystemManager()) {
            return true;
        }
        return false;
    }

}
