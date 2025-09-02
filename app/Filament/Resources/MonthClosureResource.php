<?php
namespace App\Filament\Resources;

use App\Filament\Resources\MonthClosureResource\Pages;
use App\Models\MonthClosure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MonthClosureResource extends Resource
{
    protected static ?string $model          = MonthClosure::class;
    // protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    // protected static ?string $navigationLabel  = 'Month Closures';
    // protected static ?string $pluralModelLabel = 'Month Closures';
    // protected static ?string $modelLabel       = 'Month Closure';
    protected static ?string $slug             = 'month-closures';
    
    protected static bool $shouldRegisterNavigation = false;
    public static function form(Form $form): Form
    {
        return $form->schema([
            Fieldset::make('Closure Details')
                ->columns(2)
                ->schema([
                    TextInput::make('year')
                        ->label('Year')
                        ->required()
                        ->numeric()
                        ->minValue(2000)
                        ->maxValue(2100)
                        ->placeholder('Enter year (e.g., 2025)'),

                    Select::make('month')
                        ->label('Month')
                        ->options([
                            1  => 'January',
                            2  => 'February',
                            3  => 'March',
                            4  => 'April',
                            5  => 'May',
                            6  => 'June',
                            7  => 'July',
                            8  => 'August',
                            9  => 'September',
                            10 => 'October',
                            11 => 'November',
                            12 => 'December',
                        ])
                        ->required(),

                    Select::make('status')
                        ->label('Status')
                        ->options(array_combine(
                            MonthClosure::STATUSES,
                            array_map('ucfirst', MonthClosure::STATUSES)
                        ))
                        ->required()
                        ->default(MonthClosure::STATUS_OPEN),

                    DatePicker::make('closed_at')
                        ->label('Closed At')
                        ->placeholder('Select closure date')
                        ->helperText('Date when the month was officially closed.'),

                ]),
            Fieldset::make('Additional Information')
                ->columns(1)
                ->schema([
                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(2)
                        ->placeholder('Additional notes or comments about the closure.'),
 
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')
            ->striped()
            ->columns([
                TextColumn::make('year')->sortable(),
                TextColumn::make('month')
                    ->formatStateUsing(fn($state) => date('F', mktime(0, 0, 0, $state, 1)))
                    ->sortable(),
                TextColumn::make('status')->badge()
                    ->color(fn($state)            => match ($state) {
                        MonthClosure::STATUS_CLOSED   => 'danger',
                        MonthClosure::STATUS_APPROVED => 'success',
                        MonthClosure::STATUS_OPEN     => 'primary',
                        MonthClosure::STATUS_PENDING  => 'warning',
                        default                       => 'secondary'
                    })
                    ->sortable(),
                TextColumn::make('closed_at')->date()->sortable(),
            ])
            ->filters([
                //
            ])
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
            'index'  => Pages\ListMonthClosures::route('/'),
            'create' => Pages\CreateMonthClosure::route('/create'),
            'edit'   => Pages\EditMonthClosure::route('/{record}/edit'),
        ];
    }
}