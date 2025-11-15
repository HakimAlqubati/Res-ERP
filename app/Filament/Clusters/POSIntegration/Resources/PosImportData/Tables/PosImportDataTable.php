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
        return $table->defaultSort('id','desc')
            ->headerActions([
                Action::make('import_items_quantities')
                    ->label('Import Quantities')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->modalHeading('Import Items Quantities from Excel')
                    ->modalWidth('lg')
                    ->schema([
                        // 1) ملف الإكسل
                        FileUpload::make('file')
                            ->label('Upload Excel file')
                            ->required()
                            // ->acceptedFileTypes([
                            //     'application/vnd.ms-excel',
                            //     'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            //     '.xls',
                            //     '.xlsx',
                            //     '.csv',
                            // ])
                            ->disk('public')
                            ->directory('product_items_imports'),

                        // 2) بيانات رأس الاستيراد
                        Select::make('branch_id')->columnSpanFull()->label(__('lang.branch'))->searchable()
                            ->options(
                                Branch::query()
                                    ->branches()
                                    ->pluck('name', 'id')
                            ),

                        DatePicker::make('date')
                            ->label('Import Date')
                            ->default(now())
                            ->required(),

                        // 3) وحدة افتراضية في حال لم تُذكر في الملف
                        Select::make('default_unit_id')
                            ->label('Default Unit (optional)')
                            ->options(fn() => Unit::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->native(false),

                        // 4) ملاحظات اختيارية
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->maxLength(2000),
                    ])
                    ->action(function (array $data) {
                        // مسار الملف على القرص العام
                        $fullPath = Storage::disk('public')->path($data['file']);

                        // تهيئة المستورد مع رأس الاستيراد
                        $import = new PosImportDataImport(
                            branchId: (int) $data['branch_id'],
                            createdBy: auth()->id(),
                            date: $data['date'],
                            notes: $data['notes'] ?? null,
                            defaultUnitId: $data['default_unit_id'] ?? null,
                        );

                        try {
                            Excel::import($import, $fullPath);

                            $count = $import->getSuccessfulImportsCount();
                            showSuccessNotifiMessage("Imported successfully. Lines: {$count}");
                        } catch (Throwable $e) {
                            showWarningNotifiMessage("❌ Import failed: " . $e->getMessage());
                        }
                    })
                    ->requiresConfirmation(),
            ])

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
