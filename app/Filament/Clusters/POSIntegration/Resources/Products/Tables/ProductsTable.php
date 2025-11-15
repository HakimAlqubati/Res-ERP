<?php

namespace App\Filament\Clusters\POSIntegration\Resources\Products\Tables;

use App\Models\InventoryTransaction;
use App\Models\PosSale;
use App\Models\Product;
use App\Models\Store;
use App\Models\UnitPrice;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\CheckboxColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                // TextColumn::make('default_store')
                //     ->label('Default Store')
                //     ->alignCenter(true)
                //     ->getStateUsing(function (Model $record) {
                //         // dd('sdf');
                //         $store = defaultManufacturingStore($record);
                //         return $store->name ?? '-';
                //         return $record->defaultManufacturingStore->name ?? '-';
                //     }) 
                //     ,
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
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('testPos')->button()
                    ->schema([
                        Select::make('store_id')->columnSpanFull()->label(__('lang.branch'))->searchable()
                            ->options(
                                Store::query()
                                    ->whereHas('branches', function ($q) {
                                        $q->branches(); // يستدعي scopeBranches على موديل Branch
                                    })
                                    ->pluck('name', 'id')
                            ),
                        TextInput::make('qty')->columnSpanFull(),
                    ])
                    ->requiresConfirmation()
                    ->action(fn(Product $record, array $data) => static::handleTestPos($record, $data)),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }


    /**
     * Static handler for the testPos action.
     *
     * @param  Product  $record  المنتج الحالي في الصف
     * @param  array    $data    بيانات الفورم (store_id, qty, ...)
     * @return void
     */
    public static function handleTestPos(Product $record, array $data): void
    {
        try {
            // 1) التحقق من أن المنتج من نوع POS جاهز للبيع
            if ($record->type !== Product::TYPE_FINISHED_POS) {
                throw new Exception("هذا المنتج ليس من نوع POS الجاهز للبيع (TYPE_FINISHED_POS).");
            }

            // 2) قراءة الكمية والمخزن والتحقق منهما
            $qty = (float) ($data['qty'] ?? 0);
            if ($qty <= 0) {
                throw new Exception('الكمية يجب أن تكون أكبر من صفر.');
            }

            /** @var Store $store */
            $store = Store::query()->findOrFail($data['store_id']);

             // 3) تحديد الفرع من المستخدم الحالي
            $user     = Auth::user();
            $branchId = optional($user->branch)->id ?? $user->branch_id ?? null;

            if (! $branchId) {
                throw new Exception('تعذّر تحديد الفرع للمستخدم الحالي.');
            }

            // 4) التأكد أن المنتج لديه items (مكوّنات / تركيب)
            $record->loadMissing(['productItems.product.unitPrices']);
            if ($record->productItems->isEmpty()) {
                throw new Exception('هذا المنتج لا يحتوي على أي Items (مكوّنات) لبيعها عبر POS.');
            }

            // 5) إنشاء سند POS + البنود داخل Transaction لضمان التكامل
            $sale = DB::transaction(function () use ($record, $qty, $store, $branchId, $user): PosSale {

                // 5.1 إنشاء السند كـ DRAFT أولاً
                $sale = PosSale::create([
                    'branch_id'       => $branchId,
                    'store_id'        => $store->id,
                    'sale_date'       => now(),
                    'status'          => PosSale::STATUS_DRAFT,
                    'total_quantity'  => 0,
                    'total_amount'    => 0,
                    'cancelled'       => false,
                    'cancel_reason'   => null,
                    'notes'           => "Test POS sale from Product #{$record->id}",
                    'created_by'      => $user?->id,
                    'updated_by'      => $user?->id,
                ]);

                // 5.2 الدوران على productItems الخاصة بالمنتج
                foreach ($record->productItems as $productItem) {

                    $childProduct = $productItem->product;

                    // إذا كان الـ item غير مربوط بمنتج لأي سبب، نتجاوزه
                    if (! $childProduct) {
                        continue;
                    }

                    $unitId = $productItem->unit_id;

                    // جلب سعر الوحدة من UnitPrice الخاصة بالمنتج الطفل بهذا الـ unit_id
                    $unitPrice = $childProduct->unitPrices()
                        ->where('unit_id', $unitId)
                        ->first();

                    $unitPriceValue = (float) ($unitPrice?->price ?? 0);
                    $packageSize    = (float) ($unitPrice?->package_size ?? $productItem->package_size ?? 1);

                    // الكمية = كمية الـ item * كمية البيع المدخلة من المستخدم
                    $lineQty   = (float) $productItem->quantity * $qty;
                    $lineTotal = $lineQty * $unitPriceValue;

                    // إنشاء البند عبر العلاقة items()
                    $sale->items()->create([
                        'product_id'   => $childProduct->id,
                        'unit_id'      => $unitId,
                        'quantity'     => $lineQty,
                        'price'        => $unitPriceValue,
                        'total_price'  => $lineTotal,
                        'package_size' => $packageSize,
                        'notes'        => "Auto from Product {$record->id} (Test POS)",
                    ]);
                }

                // 5.3 إعادة حساب الإجماليات في السند
                $sale->loadMissing('items');
                $sale->recalculateTotals();

                // 5.4 تحويل الحالة إلى مكتملة
                $sale->status     = PosSale::STATUS_COMPLETED;
                $sale->updated_by = $user?->id;
                $sale->save();

                return $sale;
            });

            // 6) بعد اكتمال المعاملة، ننشئ حركات المخزون باستخدام FIFO
            $sale->refresh();
            $sale->createInventoryTransactionsFromItems();

            // 7) تنبيه بالنجاح
            showSuccessNotifiMessage(
                'تم إنشاء عملية بيع POS تجريبية بنجاح.',
                "رقم السند: {$sale->id}"
            );
        } catch (\Throwable $e) {
            // تسجيل الخطأ وإظهار رسالة للمستخدم
            report($e);

            showWarningNotifiMessage(
                'فشل تنفيذ عملية البيع التجريبية.',
                $e->getMessage()
            );
        }
    }
}
