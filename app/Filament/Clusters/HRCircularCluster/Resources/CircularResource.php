<?php

namespace App\Filament\Clusters\HRCircularCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\HRCircularCluster\Resources\CircularResource\Pages\ListCirculars;
use App\Filament\Clusters\HRCircularCluster\Resources\CircularResource\Pages\CreateCircular;
use App\Filament\Clusters\HRCircularCluster\Resources\CircularResource\Pages\EditCircular;
use App\Filament\Clusters\HRCircularCluster\Resources\CircularResource\Pages\ViewCircular;
use App\Filament\Clusters\HRCircularCluster;
use App\Filament\Clusters\HRCircularCluster\Resources\CircularResource\Pages;
use App\Models\Branch;
use App\Models\Circular;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CircularResource extends Resource
{
    protected static ?string $model = Circular::class;

    protected static string | \BackedEnum | null $navigationIcon =  Heroicon::SpeakerWave;

    protected static ?string $cluster = HRCircularCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    public static function getModelLabel(): string
    {
        return __('lang.engagement');
    }

    public static function getPluralLabel(): string
    {
        return __('lang.engagement');
    }

    protected static ?int $navigationSort = 1;
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make(__('lang.basic_data'))->columnSpanFull()
                        ->schema([
                            Fieldset::make()->columnSpanFull()->schema([
                                Grid::make()->columnSpanFull()->columns(2)->schema([
                                    Fieldset::make()->columnSpanFull()->label(__('lang.set_title_of_circular'))->schema([
                                        TextInput::make('title')->label(__('lang.subject'))
                                            ->required()
                                            ->maxLength(255),
                                        DatePicker::make('released_date')->default(date('Y-m-d'))
                                            ->helperText(__('lang.date_will_be_released'))
                                            ->required(),
                                    ]),
                                    Fieldset::make()->columnSpanFull()
                                        ->hiddenOn('view')
                                        ->label(__('lang.set_branches_and_users'))->schema([
                                            Select::make('group_id')->label(__('lang.group'))
                                                ->helperText(__('lang.users_group_will_receive'))
                                                ->options(getUserTypes())
                                                ->reactive()
                                                ->required(),
                                            Select::make('branch_ids')->label(__('lang.choose_branch'))
                                                ->hidden(fn(Get $get): bool => $get('group_id') == 1)
                                                ->options(Branch::where('active', 1)->select('id', 'name')->get()->pluck('name', 'id'))
                                                ->multiple()
                                                ->required()
                                                ->helperText(__('lang.can_choose_multiple_branches')),
                                        ]),

                                ]),

                                Grid::make()->columnSpanFull()->columns(1)->schema([
                                    RichEditor::make('description'),
                                ]),
                            ]),
                        ]),
                    Step::make(__('lang.images'))->columnSpanFull()->hiddenOn('view')
                        ->schema([
                            Fieldset::make()->columnSpanFull()->label('')->schema([

                                Grid::make()->columnSpanFull()->columns(1)->schema([
                                    FileUpload::make('file_path')
                                        ->label(__('lang.add_photos'))->columnSpanFull()
                                        ->disk('public')
                                        ->directory('circulars')
                                        ->visibility('public')
                                        ->columnSpanFull()
                                        ->imagePreviewHeight('250')
                                        ->image()
                                        // ->resize(5)
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
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50, 100])
            ->columns([
                TextColumn::make('title')->label(__('lang.subject'))->sortable(),
                // TextColumn::make('description')->limit(50),
                TextColumn::make('group.name')->label(__('lang.group')),
                TextColumn::make('released_date')->date()->sortable(),
                TextColumn::make('createdBy.name')->label(__('lang.created_by'))->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->date()->sortable()->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('viewGallery')
                    ->hidden(function ($record) {
                        return $record->photos_count <= 0 ? true : false;
                    })
                    ->label(__('lang.browse_photos'))
                    ->label(function ($record) {
                        return $record->photos_count;
                    })
                    ->modalHeading(__('lang.photos'))
                    ->modalWidth('lg') // Adjust modal size
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('lang.close'))
                    // ->iconButton()
                    ->button()
                    ->icon('heroicon-o-camera')
                    ->modalContent(function ($record) {
                        return view('filament.resources.circulars.gallery', ['photos' => $record->photos]);
                    }),
                // Tables\Actions\EditAction::make(),
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
            'index' => ListCirculars::route('/'),
            'create' => CreateCircular::route('/create'),
            'edit' => EditCircular::route('/{record}/edit'),
            'view' => ViewCircular::route('/{record}'),
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
