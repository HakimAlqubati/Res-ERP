<?php

namespace App\Filament\Clusters\AccountingCluster\Resources;

use App\Filament\Clusters\AccountingCluster;
use App\Filament\Clusters\AccountingCluster\Resources\AccountResource\Pages;
use App\Filament\Clusters\AccountingCluster\Resources\AccountResource\RelationManagers;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = AccountingCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->columns(2)->schema([
                    Select::make('parent_id')
                        ->label('Parent Account')
                        ->helperText('Select the parent account for this one, or leave empty for a top-level account.')
                        ->live()
                        ->searchable()->required()
                        ->afterStateUpdated(function ($get, $set, $state) {
                            $code = Account::generateNextCode($state);
                            $set('code', $code);
                        })
                        ->options(Account::all()->mapWithKeys(fn($acc) => [
                            $acc->id => "{$acc->code} - {$acc->name}"
                        ])),
                    TextInput::make('name')
                        ->label('Account Name')
                        ->helperText('The full name of the account, e.g., Inventory - Main Store')

                        ->required()
                        ->maxLength(255),

                    TextInput::make('code')
                        ->label('Code')
                        ->helperText('The unique code used to identify this account.')
                        ->required()
                        ->maxLength(20)
                        ->reactive()
                        ->unique(ignoreRecord: true),

                    Select::make('type')
                        ->label('Account Type')
                        ->helperText('Choose the category this account belongs to in the chart of accounts.')

                        ->required()
                        ->options(Account::getTypeOptions()),


                ])
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('name')->label('Account Name')->searchable(),
                TextColumn::make('code')->label('Account Code')->sortable(),
                BadgeColumn::make('type')->label('Type')->colors([
                    'primary' => 'asset',
                    'danger' => 'liability',
                    'warning' => 'equity',
                    'success' => 'revenue',
                    'info' => 'expense',
                ])->formatStateUsing(fn($state) => match ($state) {
                    'asset' => 'أصل',
                    'liability' => 'التزام',
                    'equity' => 'حقوق',
                    'revenue' => 'إيراد',
                    'expense' => 'مصروف',
                }),
                TextColumn::make('parent.name')->label('Parent Account')->sortable()->searchable(),
            ])
            ->defaultSort('code')
            ->filters([
                Filter::make('main_only')
                    ->label('الحسابات الرئيسية فقط')
                    ->query(fn(Builder $query): Builder => $query->whereNull('parent_id')),

                SelectFilter::make('parent_id')
                    ->label('حسب الحساب الرئيسي')
                    ->options(Account::whereNull('parent_id')->pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}
