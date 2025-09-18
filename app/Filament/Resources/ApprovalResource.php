<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ApprovalResource\Pages\ListApprovals;
use App\Filament\Resources\ApprovalResource\Pages;
use App\Filament\Resources\ApprovalResource\RelationManagers;
use App\Models\Approval;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ApprovalResource extends Resource
{
    protected static ?string $model = Approval::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('route_name')
                    ->required(),
                TextInput::make('date')
                    ->required(),
                TextInput::make('time')
                    ->required(),
                Checkbox::make('is_approved')
                    ->label('Approved')
                    ->default(false),
                Select::make('approved_by')
                    ->relationship('approver', 'name')
                    ->searchable()
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->columns([
                TextColumn::make('created_by_name')->label('Created By'),
                TextColumn::make('route_name')->label('Route Name'),
                TextColumn::make('date')->label('Date')->date('Y-m-d')->alignCenter(true),
                TextColumn::make('time')->label('Time')->alignCenter(true),
                BooleanColumn::make('is_approved')->label('Approved')->alignCenter(true),
                TextColumn::make('approved_by_name')->label('Approved By'),
                TextColumn::make('created_at')->label('Requested At'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                // Tables\Actions\EditAction::make(),
                Action::make('approve')
                    ->label('Approve')->button()
                    ->action(function (Approval $record) {
                        $record->is_approved = true;
                        $record->approved_by = auth()->id();
                        $record->save();
                    })
                    ->requiresConfirmation()
                    ->color('success')->visible(fn($record): bool => $record->is_approved == false),

                Action::make('rollbackApprove')
                    ->label('Rollback')->button()
                    ->action(function (Approval $record) {
                        $record->is_approved = false;
                        $record->approved_by = null;
                        $record->save();
                    })
                    ->requiresConfirmation()
                    ->color('danger')->visible(fn($record): bool => $record->is_approved == true),
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
            'index' => ListApprovals::route('/'),
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

    public static function canView(Model $record): bool
    {
        if (isSuperAdmin() || isSystemManager()) {
            return true;
        }
        return false;
    }


    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager()) {
            return true;
        }
        return false;
    }
}
