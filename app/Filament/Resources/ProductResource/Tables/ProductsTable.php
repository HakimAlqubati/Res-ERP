<?php
namespace App\Filament\Resources\ProductResource\Tables;

use Illuminate\Database\Eloquent\Builder;
use App\Exports\ProductsExport;
use App\Imports\ProductImport;
use App\Imports\ProductItemsImport;
use App\Imports\ProductItemsQuantityImport;
use App\Models\Product;
use App\Services\BatchProductCostingService;
use App\Services\MigrationScripts\ProductMigrationService;
use App\Services\ProductCostingService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\CheckboxColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ProductsTable
{

    public static function configure(Table $table): Table
    {
        return $table->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->headerActions([
                Action::make('import_items_quantities')
                    ->label('Import Quantities')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Upload Excel file')
                            ->required()
                            ->disk('public')
                            ->directory('product_items_imports'),
                    ])
                    ->action(function (array $data) {
                        $filePath = 'public/' . $data['file'];
                        $import   = new ProductItemsQuantityImport();

                        try {
                            Excel::import($import, $filePath);
                            showSuccessNotifiMessage("✅ تم تعديل كميات المكونات بنجاح.");
                        } catch (Throwable $e) {
                            showWarningNotifiMessage("❌ فشل الاستيراد: " . $e->getMessage());
                        }
                    })
                    ->requiresConfirmation(),
                Action::make('import_products')
                    ->label('Import Products')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Upload Excel file')
                            ->required()
                            // ->acceptedFileTypes(['.xlsx', '.xls'])
                            ->disk('public')
                            ->directory('product_imports'),
                    ])
                    ->color('success')
                    ->action(function (array $data) {
                        $filePath = 'public/' . $data['file'];
                        $import   = new ProductImport();

                        try {
                            Excel::import($import, $filePath);

                            if ($import->getSuccessfulImportsCount() > 0) {
                                showSuccessNotifiMessage("✅ Imported {$import->getSuccessfulImportsCount()} products successfully.");
                            } else {
                                showWarningNotifiMessage("⚠️ No products were added. Please check your file.");
                            }
                        } catch (Throwable $e) {
                            showWarningNotifiMessage('❌ Failed to import products: ' . $e->getMessage());
                        }
                    }),

                Action::make('export')
                    ->label('Export to Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('warning')
                    ->action(function () {
                        $data = Product::where('active', 1)->select('id', 'name', 'description', 'code')->get();
                        return Excel::download(new ProductsExport($data), 'products.xlsx');
                    }),
            ])->deferFilters(false)
            ->columns([
                TextColumn::make('id')
                    ->label(__('lang.id'))
                    ->copyable()
                    ->copyMessage(__('lang.product_id_copied'))
                    ->copyMessageDuration(1500)
                    ->sortable()->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(isIndividual: false, isGlobal: true),
                TextColumn::make('code')
                    ->label(__('lang.code'))->copyable()->sortable()
                    ->searchable(isIndividual: false, isGlobal: true),

                TextColumn::make('name')
                    ->label(__('lang.name'))
                    ->toggleable()

                    ->searchable(isIndividual: false, isGlobal: true)
                    ->tooltip(fn(Model $record): string => "By {$record->name}"),

                TextColumn::make('waste_stock_percentage')
                    ->label('Waste %')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(true),
                TextColumn::make('minimum_stock_qty')
                    ->label('Min. Qty')->sortable()
                    ->alignCenter(true)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('formatted_unit_prices')
                    ->label('Unit Prices')->toggleable(isToggledHiddenByDefault: false)
                    ->limit(50)->tooltip(fn($state) => $state)
                // ->alignCenter(true)
                ,
                TextColumn::make('description')->searchable()
                    ->searchable(isIndividual: false, isGlobal: true)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('lang.description')),
                IconColumn::make('is_manufacturing')->boolean()->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('lang.is_manufacturing')),
                TextColumn::make('category.name')->searchable()->label(__('lang.category'))->alignCenter(true)
                    ->searchable(isIndividual: false, isGlobal: false)->toggleable(),
                CheckboxColumn::make('active')->label('Active?')
                    ->sortable()->label(__('lang.active'))->toggleable()->alignCenter(true)
                    ->updateStateUsing(function (Product $record, $state) {
                        try {
                            $record->update(['active' => $state]);
                        } catch (Exception $e) {
                            showWarningNotifiMessage('Faild', $e->getMessage());
                        }
                    }),
                TextColumn::make('product_items_count')->label('Items No')
                    ->toggleable(isToggledHiddenByDefault: true)->default('-')->alignCenter(true),
            ])
            ->filters([
                Filter::make('active')->label(__('lang.active'))
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('active')),
                SelectFilter::make('category_id')
                    ->searchable()
                    ->multiple()
                    ->label(__('lang.category'))->relationship('category', 'name'),
                // New Filter for Manufacturing Products
                Filter::make('is_manufacturing')
                    ->label(__('lang.is_manufacturing'))
                    ->query(fn(Builder $query): Builder => $query->whereHas('category', fn($q) => $q->where('is_manafacturing', true))),

                TrashedFilter::make(),
                Filter::make('smallest_package_not_one')
                    ->label('Min Package Size ≠ 1')
                    ->query(function (Builder $query) {
                        $query->whereIn('id', function ($q) {
                            $q->select('product_id')
                                ->from('unit_prices')
                                ->whereNull('deleted_at')
                                ->groupBy('product_id')
                                ->havingRaw('MIN(package_size) != 1');
                        });
                    }),
            ])
            ->recordActions([
                // Action::make('updateUnitPrice')
                //     ->label('Update Unit Price')->button()->action(function ($record) {
                //         $update = ProductMigrationService::updatePackageSizeForProduct($record->id);
                //         if ($update) {
                //             showSuccessNotifiMessage('Done');
                //         } else {
                //             showWarningNotifiMessage('Faild');
                //         }
                //     })->hidden(),

                ActionGroup::make([
                    Action::make('exportItemsPdf')
                        ->label('Export Items PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('danger')
                        // ->visible(fn($record) => $record->productItems()->exists())
                        ->url(fn($record) => route('products.export-items-pdf', $record->id))
                        ->openUrlInNewTab(),
                    Action::make('updateComponentPrices')
                        ->label('Update Price')
                        ->icon('heroicon-o-currency-dollar')->button()
                        ->color('info')->visible(fn($record): bool => $record->is_manufacturing)
                        ->action(function ($record) {
                            $count = ProductCostingService::updateComponentPricesForProduct($record->id);
                            if ($count > 0) {
                                showSuccessNotifiMessage("✅ تم تحديث أسعار {$count} من المكونات.");
                            } else {
                                showWarningNotifiMessage("⚠️ لم يتم تحديث أي مكوّن. تأكد من أن المنتج مركب أو أن هناك أسعار متاحة.");
                            }
                        }),

                    Action::make('import_items')
                        ->label('Import Items')
                        ->icon('heroicon-o-arrow-up-tray')->button()
                        ->visible(fn($record) => $record->is_manufacturing)
                        ->schema([
                            FileUpload::make('file')
                                ->label('Upload Excel file')
                                ->required()
                                ->disk('public')
                                ->directory('product_items_imports'),
                        ])
                        ->color('success')
                        ->action(function (array $data, $record) {
                            $filePath = 'public/' . $data['file'];
                            $import   = new ProductItemsImport($record->id);

                            try {
                                Excel::import($import, $filePath);

                                $imported = $import->getImportedCount();
                                $failed   = count($import->getFailedRows());

                                if ($imported > 0) {
                                    showSuccessNotifiMessage("✅ تم استيراد {$imported} عناصر بنجاح.");
                                }

                                if ($failed > 0) {
                                    Log::warning("⚠️ بعض الصفوف فشلت في الاستيراد.", $import->getFailedRows());
                                    showWarningNotifiMessage("⚠️ تم استيراد بعض العناصر. راجع السجل للأخطاء.");
                                }

                                if ($imported === 0 && $failed === 0) {
                                    showWarningNotifiMessage("⚠️ لم يتم استيراد أي عنصر. تأكد من الملف.");
                                }
                            } catch (Throwable $e) {
                                showWarningNotifiMessage("❌ فشل الاستيراد: " . $e->getMessage());
                            }
                        }),

                    EditAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkAction::make('updateComponentPrices')
                    ->label('Update Price')
                    ->icon('heroicon-o-currency-dollar')->button()
                    ->color('info')
                    ->action(function (Collection $records) {

                        $result = [];
                        foreach ($records as $record) {

                            $count = ProductCostingService::updateComponentPricesForProduct($record->id);
                            if ($count > 0) {
                                $result[] = "✅ تم تحديث أسعار {$count} من المكونات للمنتج {$record->name}.";
                            } else {
                                $result[] = "⚠️ لم يتم تحديث أي مكوّن للمنتج {$record->name}. تأكد من أن المنتج مركب أو أن هناك أسعار متاحة.";
                            }
                        }
                        Log::info('Update Component Prices Results:', $result);
                    })->hidden(),
                BulkAction::make('updateComponentPricesNew')
                    ->label('Update Price')
                    ->icon('heroicon-o-currency-dollar')->button()
                    ->color('info')
                    ->action(function (Collection $records) {
                        $productIds = $records->pluck('id')->toArray();
                        BatchProductCostingService::updateComponentPricesForMany($productIds);
                        showSuccessNotifiMessage('Done for ' . count($productIds) . ' products');
                    }),

                BulkAction::make('exportProductsWithUnits')
                    ->label('Export with Unit Prices')
                    // ->icon('heroicon-o-download')
                    ->action(function (Collection $records): BinaryFileResponse {
                        $data = [];

                        foreach ($records as $product) {
                            $product->load(['allUnitPrices.unit', 'category']);
                            foreach ($product->unitPrices as $unitPrice) {
                                $data[] = [
                                    'product_id'   => $product->id,
                                    'product_name' => $product->name,
                                    'product_code' => $product->code,
                                    'category'     => $product->category?->name ?? '',
                                    'unit'         => $unitPrice->unit?->name ?? '',
                                    // 'price' => $unitPrice->price,
                                ];
                            }
                        }

                        // توليد وتصدير Excel
                        return Excel::download(new class($data) implements FromCollection, WithHeadings
                        {
                            public function __construct(public array $data) {}

                            public function collection()
                            {
                                return collect($this->data);
                            }

                            public function headings(): array
                            {
                                return ['product_id', 'product_name', 'product_code', 'category', 'unit'];
                            }
                        }, 'products_with_units.xlsx');
                    })
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->color('success'),
                // ForceDeleteAction::make(),
                ForceDeleteBulkAction::make(),

                DeleteBulkAction::make(),
                // ExportBulkAction::make(),
                // Tables\Actions\ForceDeleteBulkAction::make(),
                RestoreBulkAction::make(),
            ]);
    }
}
