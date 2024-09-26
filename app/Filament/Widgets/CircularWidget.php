<?php

namespace App\Filament\Widgets;

use App\Filament\Clusters\HRCircularCluster\Resources\CircularResource;
use App\Models\Branch;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CircularWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;
    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                // CreateAction::make('create')
                CreateAction::make()->label('New memo')
                    ->form([
                        Wizard::make([
                            Step::make('Basic data')
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

                                    ]),
                                ]),
                            Step::make('Description content')
                                ->schema([
                                    Grid::make()->columns(1)->schema([
                                        RichEditor::make('description')->label('')->required(),
                                    ]),
                                ]),
                            Step::make('Images')->hiddenOn('view')
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
                        ])->columnSpanFull()->skippable(),

                    ])
                    ->modalHeading('')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->user()->id;
                        $data['branch_ids'] = json_encode($data['branch_ids']);
                        return $data;
                    })
                    ->after(function ($data, Model $record) {
                        // // Runs after the form fields are saved to the database.
                        if (is_array($data['file_path']) && count($data['file_path']) > 0) {
                            foreach ($data['file_path'] as $key => $image) {
                                $record->photos()->create([
                                    'image_name' => $image,
                                    'image_path' => $image,
                                    'created_by' => auth()->user()->id,
                                ]);
                            }
                        }
                    })
                    ->createAnother(false)
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Done')
                            ->body('Memo sent successfully'),
                    )
                ,
            ])
            ->query(CircularResource::getEloquentQuery())
            ->defaultPaginationPageOption(5)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Subject')->sortable(),
                TextColumn::make('group.name')->label('Group'),
                TextColumn::make('released_date')->date()->sortable(),
                TextColumn::make('created_at')->date()->sortable(),
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
                ViewAction::make()->modalHeading('')->form([

                    Fieldset::make()->columns(3)->label('')->schema([
                        TextInput::make('title')->label('Subject')
                            ->required()
                            ->maxLength(255),

                        DatePicker::make('released_date')->default(date('Y-m-d'))

                            ->required(),
                        Select::make('group_id')->label('Group')
                            ->options(getUserTypes())
                            ->required(),
                    ]),

                    Grid::make()->columns(1)->schema([
                        RichEditor::make('description')->label('')->required(),
                    ]),

                ]),
                // EditAction::make(),
            ])
        ;
    }

}
