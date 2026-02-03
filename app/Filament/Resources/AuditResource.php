<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\AuditResource\Pages\ListAudits;
use App\Filament\Resources\AuditResource\Pages\CreateAudit;
use App\Filament\Resources\AuditResource\Pages\EditAudit;
use App\Filament\Resources\AuditResource\Pages\ViewAudit;
use App\Filament\Resources\AuditResource\Pages;
use App\Filament\Resources\AuditResource\RelationManagers;
use App\Models\Audit;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $label = 'Audit Log';
    protected static ?string $pluralLabel = 'Audit Logs';
    protected static ?string $slug = 'audit-logs';
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true),

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

                TextColumn::make('auditable_id')->label('Model ID')->sortable()->searchable()->alignCenter(true),
                IconColumn::make('has_parent')->label('Has Parent')->boolean()->alignCenter(true)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('parent_id')->label('Parent ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
                // TextColumn::make('field')->label('Field'),
                TextColumn::make('old_values')->label('Old Value'),
                TextColumn::make('new_values')->label('New Value'),

                TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->tooltip(fn($record) => $record->created_at->format('Y-m-d H:i:s')),
            ])
            ->filters([
                SelectFilter::make('auditable_type')
                    ->label('Model')
                    ->options(
                        fn() => Audit::query()
                            ->select('auditable_type')
                            ->distinct()
                            ->pluck('auditable_type')
                            ->mapWithKeys(fn($type) => [$type => class_basename($type)])
                            ->toArray()
                    )
                    ->searchable(),
                SelectFilter::make('event')
                    ->label('Event')
                    ->options(
                        fn() => Audit::query()
                            ->select('event')
                            ->distinct()
                            ->pluck('event')
                            ->mapWithKeys(fn($event) => [$event => ucfirst($event)])
                            ->toArray()
                    ),

            ], FiltersLayout::AboveContent)
            ->recordActions([
                // Tables\Actions\EditAction::make(),
            ])
            ->toolbarActions([
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
            'index' => ListAudits::route('/'),
            'create' => CreateAudit::route('/create'),
            'edit' => EditAudit::route('/{record}/edit'),
            'view' => ViewAudit::route('/{record}'),
        ];
    }
}
