<?php

namespace App\Filament\Clusters\POSIntegration\Resources\PosSales\Tables;

use App\Imports\PosImportDataImport;
use App\Models\Branch;
use App\Models\PosSale;
use App\Models\Unit;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class PosSalesTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')
            ->deferFilters(false)
            ->striped()
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()->alignCenter()->toggleable()
                    ->searchable(),

                TextColumn::make('formatted_sale_date')
                    ->label('Sale Date')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('store.name')
                    ->label('Store')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('status')->alignCenter()
                    ->label('Status')
                    ->formatStateUsing(fn($state, $record) => $record->status_label)
                    ->colors([
                        'gray'  => 'draft',
                        'green' => 'completed',
                        'red'   => 'cancelled',
                    ])
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')      // يعدّ علاقة items
                    ->numeric(0)
                    ->alignCenter()
                    ->sortable(),
                // TextColumn::make('total_quantity')
                //     ->label('Total Qty')
                //     ->numeric(4)->alignCenter()
                //     ->sortable(),

                TextColumn::make('total_amount')->alignCenter()
                    ->label(__('lang.total_cost'))
                    // ->money('USD') // أو SAR حسب مشروعك
                    ->sortable()->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                // ->getStateUsing(fn($state) => formatMoneyWithCurrency($state))
                ,

                IconColumn::make('cancelled')
                    ->label('Cancelled?')->alignCenter()
                    ->boolean()
                    ->sortable()
                    ->tooltip(fn($record) => $record->cancel_reason ?? 'No reason'),

                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('updatedBy.name')
                    ->label('Updated By')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('notes')
                    ->label('Notes')
                    ->wrap()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true)
            ])
            ->filters([
                // TrashedFilter::make(),
            ])
            ->recordActions([

                ViewAction::make(),
                self::approveAction(),

                // EditAction::make(),
            ])
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
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function approveAction(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->button()
            ->icon('heroicon-o-check')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Approve POS Sale')
            ->modalDescription('Are you sure you want to approve this POS sale? This will finalize it and create inventory movements.')
            ->visible(fn(PosSale $record) => $record->is_draft) // يظهر فقط لو السند Draft
            ->action(function (PosSale $record): void {
                // لو حاب تتأكد مرة ثانية:
                if (! $record->is_draft) {
                    showWarningNotifiMessage('This sale is not in draft status.');
                    return;
                }

                // إعادة حساب الإجماليات من البنود
                $record->recalculateTotals();

                // تغيير الحالة إلى مكتملة
                $record->status = PosSale::STATUS_COMPLETED;
                $record->save();

                // createInventoryTransactionsFromItems تُستدعى تلقائياً من الـ booted عند تغيير الـ status إلى COMPLETED
                // لكن لو حاب تفرض استدعاءها هنا صراحة:
                // $record->createInventoryTransactionsFromItems();

                showSuccessNotifiMessage('Sale approved and inventory updated successfully.');
            });
    }
}
