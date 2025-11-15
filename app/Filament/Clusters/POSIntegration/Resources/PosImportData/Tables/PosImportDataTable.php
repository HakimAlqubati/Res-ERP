<?php

namespace App\Filament\Clusters\POSIntegration\Resources\PosImportData\Tables;

use App\Imports\PosImportDataImport;
use App\Models\Branch;
use App\Models\Store;
use App\Models\Unit;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class PosImportDataTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')
            ->headerActions([])

            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->searchable()
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('date')
                    ->label('Import Date')
                    ->sortable()
                    ->date('Y-m-d')
                    ->toggleable(),

                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50)
                    ->toggleable(),

                BadgeColumn::make('details_count')
                    ->label('Items Count')
                    ->counts('details')
                    ->color('info')
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                TrashedFilter::make(),
            ])

            ->recordActions([
                // EditAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
