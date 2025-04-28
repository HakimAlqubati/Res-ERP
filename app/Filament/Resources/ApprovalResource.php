<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApprovalResource\Pages;
use App\Filament\Resources\ApprovalResource\RelationManagers;
use App\Models\Approval;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ApprovalResource extends Resource
{
    protected static ?string $model = Approval::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('route_name')
                    ->required(),
                Forms\Components\TextInput::make('date')
                    ->required(),
                Forms\Components\TextInput::make('time')
                    ->required(),
                Forms\Components\Checkbox::make('is_approved')
                    ->label('Approved')
                    ->default(false),
                Forms\Components\Select::make('approved_by')
                    ->relationship('approver', 'name')
                    ->searchable()
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->columns([
                Tables\Columns\TextColumn::make('created_by_name')->label('Created By'),
                Tables\Columns\TextColumn::make('route_name')->label('Route Name'),
                Tables\Columns\TextColumn::make('date')->label('Date')->date('Y-m-d')->alignCenter(true),
                Tables\Columns\TextColumn::make('time')->label('Time')->alignCenter(true),
                Tables\Columns\BooleanColumn::make('is_approved')->label('Approved')->alignCenter(true),
                Tables\Columns\TextColumn::make('approved_by_name')->label('Approved By'),
                Tables\Columns\TextColumn::make('created_at')->label('Requested At'),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')->button()
                    ->action(function (Approval $record) {
                        $record->is_approved = true;
                        $record->approved_by = auth()->id();
                        $record->save();
                    })
                    ->requiresConfirmation()
                    ->color('success')->visible(fn($record): bool => $record->is_approved == false),

                Tables\Actions\Action::make('rollbackApprove')
                    ->label('Rollback')->button()
                    ->action(function (Approval $record) {
                        $record->is_approved = false;
                        $record->approved_by = null;
                        $record->save();
                    })
                    ->requiresConfirmation()
                    ->color('danger')->visible(fn($record): bool => $record->is_approved == true),
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
            'index' => Pages\ListApprovals::route('/'),
        ];
    }


    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    

    public static function canAccess(): bool
    {
        return self::canViewAny();
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->can('view_any_approval')) {
            return true;
        }
        return false;
    }
}
