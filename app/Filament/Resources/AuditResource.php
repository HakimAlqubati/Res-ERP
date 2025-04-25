<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditResource\Pages;
use App\Filament\Resources\AuditResource\RelationManagers;
use App\Models\Audit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $label = 'Audit Log';
    protected static ?string $pluralLabel = 'Audit Logs';
    protected static ?string $slug = 'audit-logs';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->columns([
                TextColumn::make('id')->sortable()->searchable(),

                TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable()
                    ->default(fn($record) => $record->user?->name ?? '—'),

                BadgeColumn::make('event')
                    ->label('Event')
                    ->colors([
                        'success' => 'created',
                        'warning' => 'updated',
                        'danger' => 'deleted',
                    ])
                    ->formatStateUsing(fn(string $state) => ucfirst($state)),

                TextColumn::make('auditable_type')
                    ->label('Model')
                    ->formatStateUsing(fn(string $state) => class_basename($state)),

                TextColumn::make('auditable_id')->label('Model ID')->sortable(),
                IconColumn::make('has_parent')->label('Has Parent')->boolean()->alignCenter(true),
                TextColumn::make('parent_id')->label('Parent ID')->sortable(),
                TextColumn::make('parent_name')->label('Parent Name'),

                TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->tooltip(fn($record) => $record->created_at->format('Y-m-d H:i:s')),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
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
            'index' => Pages\ListAudits::route('/'),
            'create' => Pages\CreateAudit::route('/create'),
            'edit' => Pages\EditAudit::route('/{record}/edit'),
            'view' => Pages\ViewAudit::route('/{record}'),
        ];
    }
}
