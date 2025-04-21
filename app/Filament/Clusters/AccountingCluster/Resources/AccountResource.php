<?php

namespace App\Filament\Clusters\AccountingCluster\Resources;

use App\Filament\Clusters\AccountingCluster;
use App\Filament\Clusters\AccountingCluster\Resources\AccountResource\Pages;
use App\Filament\Clusters\AccountingCluster\Resources\AccountResource\RelationManagers;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
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
                TextInput::make('name')
                    ->label('اسم الحساب')
                    ->required()
                    ->maxLength(255),

                TextInput::make('code')
                    ->label('رمز الحساب')
                    ->required()
                    ->maxLength(20),

                Select::make('type')
                    ->label('نوع الحساب')
                    ->required()
                    ->options([
                        'asset' => 'أصل',
                        'liability' => 'التزام',
                        'equity' => 'حقوق ملكية',
                        'revenue' => 'إيراد',
                        'expense' => 'مصاريف',
                    ]),

                Select::make('parent_id')
                    ->label('الحساب الرئيسي')
                    ->searchable()
                    ->options(Account::all()->pluck('name', 'id')),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('اسم الحساب')->searchable(),
                TextColumn::make('code')->label('رمز الحساب')->sortable(),
                BadgeColumn::make('type')->label('النوع')->colors([
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
                TextColumn::make('parent.name')->label('الحساب الرئيسي')->sortable()->searchable(),
            ])
            ->defaultSort('code')
            ->filters([
                // يمكن لاحقًا إضافة فلاتر حسب النوع أو الحسابات الرئيسية
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
