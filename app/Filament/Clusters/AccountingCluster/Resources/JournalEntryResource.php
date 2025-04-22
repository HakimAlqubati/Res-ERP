<?php

namespace App\Filament\Clusters\AccountingCluster\Resources;

use App\Filament\Clusters\AccountingCluster;
use App\Filament\Clusters\AccountingCluster\Resources\JournalEntryResource\Pages;
use App\Filament\Clusters\AccountingCluster\Resources\JournalEntryResource\RelationManagers;
use App\Filament\Clusters\AccountingCluster\Resources\JournalEntryResource\RelationManagers\LinesRelationManager;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = AccountingCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Journal Entry Info')
                    ->columns(1)
                    ->schema([
                        TextInput::make('date')
                            ->label('Date')
                            ->type('date')->default(now()->format('Y-m-d'))
                            ->required(),

                        TextInput::make('description')
                            ->label('Description')
                            ->helperText('A brief description of the journal entry.')
                            ->required(),
                    ]),

                Repeater::make('lines')->hiddenOn('view')
                    ->label('Journal Entry Lines')
                    ->relationship('lines')
                    ->columns(3)->columnSpanFull()
                    ->schema([
                        Select::make('account_id')
                            ->label('Account')
                            ->options(Account::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        TextInput::make('debit')
                            ->label('Debit')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        TextInput::make('credit')
                            ->label('Credit')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                    ])
                    ->minItems(2)
                    ->helperText('At least two lines are required. Debit and Credit must be balanced.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->columns([
                TextColumn::make('id')->label('Entry ID')->sortable(),
                TextColumn::make('date')->label('Date')->sortable(),
                TextColumn::make('description')->label('Description')->limit(50),
                TextColumn::make('related_model_type')->label('Source Type')->formatStateUsing(fn($state) => class_basename($state)),
                TextColumn::make('related_model_id')->label('Source ID'),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            LinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournalEntries::route('/'),
            'create' => Pages\CreateJournalEntry::route('/create'),
            'view' => Pages\ViewJournalEntry::route('/{record}'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ListJournalEntries::class,
            Pages\CreateJournalEntry::class,
            Pages\ViewJournalEntry::class,
        ]);
    }
}
