<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource;
use App\Models\Product;
use App\Models\StockInventory;
use App\Models\UnitPrice;
use App\Services\MultiProductsInventoryService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CreateStockInventory extends CreateRecord
{
    protected static string $resource = StockInventoryResource::class;
    protected static bool $canCreateAnother = false;

    protected array $extraDetails = [];

    public function mount(): void
    {
        parent::mount();

        $this->restoreActiveDraftIfExists();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('start_new')
                ->label(fn () => __('lang.start_new_stocktake'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn () => ! empty($this->data['saved_draft_id']))
                ->requiresConfirmation()
                ->modalHeading(fn () => __('lang.start_new_stocktake'))
                ->modalDescription(fn () => app()->getLocale() === 'ar'
                    ? 'سيتم إلغاء ربط المسودة المحفوظة وبدء نموذج جرد فارغ جديد. هل أنت متأكد؟'
                    : 'The saved draft will be detached and a new blank stocktake form will start. Are you sure?')
                ->action(function () {
                    $this->clearActiveDraft();
                    $this->redirect(static::getResource()::getUrl('create', ['new' => 1]));
                }),
        ];
    }

    public function getInventoryRowData($productId, $unitId, $storeId): array
    {
        $unitPrice = UnitPrice::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->first();

        $packageSize = (float) ($unitPrice?->package_size ?? 0);

        $baseRem = (float) DB::table('inventory_transactions')
            ->whereNull('deleted_at')
            ->where('store_id', $storeId)
            ->where('product_id', $productId)
            ->select(DB::raw("SUM(CASE WHEN movement_type = 'in' THEN (quantity * package_size) ELSE -(quantity * package_size) END) as rem_qty"))
            ->value('rem_qty');

        $remainingQty = $packageSize > 0 ? round($baseRem / $packageSize, 4) : 0;

        return [
            'package_size'  => $packageSize,
            'remaining_qty' => $remainingQty,
        ];
    }

    /**
     * 💾 الحفظ التلقائي في الخلفية بدون الحاجة لضغط زر
     */
    public function autoSaveDraft(): void
    {
        $storeId = (int) ($this->data['store_id'] ?? null);
        $inventoryDate = $this->data['inventory_date'] ?? now()->toDateString();
        $responsibleUserId = (int) ($this->data['responsible_user_id'] ?? auth()->id());

        if (! $storeId) {
            return;
        }

        // 1. مزامنة بيانات الصفحة الحالية في details_pages
        $currentPage = (int) ($this->data['current_page'] ?? 1);
        $pages = (array) ($this->data['details_pages'] ?? []);
        $pageDetails = (array) ($this->data['page_details'] ?? []);

        if (! empty($pageDetails)) {
            $pages[$currentPage] = $pageDetails;
            $this->data['details_pages'] = $pages;
        }

        ksort($pages);
        $merged = [];
        foreach ($pages as $rows) {
            foreach ((array) $rows as $row) {
                if (empty($row['product_id']) || empty($row['unit_id'])) {
                    continue;
                }
                unset($row['rowInventoryCache'], $row['rowUnitsCache']);
                $merged[] = [
                    'product_id'        => $row['product_id'],
                    'unit_id'           => $row['unit_id'],
                    'package_size'      => (float) ($row['package_size'] ?? 1),
                    'system_quantity'   => (float) ($row['system_quantity'] ?? 0),
                    'physical_quantity' => (float) ($row['physical_quantity'] ?? 0),
                    'difference'        => (float) ($row['difference'] ?? 0),
                ];
            }
        }

        if (empty($merged)) {
            return;
        }

        try {
            DB::beginTransaction();

            $draftId = $this->data['saved_draft_id'] ?? null;
            $inventory = $draftId ? StockInventory::find($draftId) : null;

            if (! $inventory) {
                $inventory = new StockInventory();
                $inventory->created_by = auth()->id();
            }

            $inventory->inventory_date      = $inventoryDate;
            $inventory->store_id            = $storeId;
            $inventory->responsible_user_id = $responsibleUserId;
            $inventory->finalized           = false;
            $inventory->inventory_type      = StockInventory::TYPE_MANUAL;
            $inventory->save();

            $this->data['saved_draft_id'] = $inventory->id;

            $inventory->details()->delete();
            $inventory->details()->createMany($merged);

            DB::commit();

            // حفظ تفاصيل الحالة الكاملة لاستعادتها فورياً في أي لحظة
            $userId = auth()->id();
            $meta = [
                'store_id'             => $storeId,
                'inventory_date'       => $inventoryDate,
                'responsible_user_id'  => $responsibleUserId,
                'category_id'          => $this->data['category_id'] ?? null,
                'unit_size_preference' => $this->data['unit_size_preference'] ?? 'largest',
                'product_ids_pool'     => $this->data['product_ids_pool'] ?? [],
                'details_pages'        => $pages,
                'current_page'         => $currentPage,
                'total_pages'          => $this->data['total_pages'] ?? 1,
                'per_page'             => $this->data['per_page'] ?? 20,
            ];

            Cache::put("stock_inventory_draft_meta_{$inventory->id}", $meta, now()->addDays(30));
            Cache::put("stock_inventory_user_active_draft_{$userId}", $inventory->id, now()->addDays(30));

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::warning('StockInventory Auto-Save Notice: ' . $e->getMessage());
        }
    }

    /**
     * ♻️ استعادة المسودة النشطة تلقائياً عند فتح الصفحة أو بعد إعادة التحميل / الانقطاع
     */
    protected function restoreActiveDraftIfExists(): void
    {
        if (request()->has('new')) {
            $this->clearActiveDraft();
            return;
        }

        $userId = auth()->id();
        $requestedDraftId = request()->query('draft_id');
        $draftId = $requestedDraftId ?? Cache::get("stock_inventory_user_active_draft_{$userId}");

        $draft = null;
        if ($draftId) {
            $draft = StockInventory::where('id', $draftId)
                ->where('finalized', false)
                ->first();
        }

        if (! $draft && ! $requestedDraftId) {
            // ابحث عن آخر مسودة غير مكتملة لهذا المستخدم خلال آخر 7 أيام
            $draft = StockInventory::where('created_by', $userId)
                ->where('finalized', false)
                ->whereDate('created_at', '>=', now()->subDays(7))
                ->latest('id')
                ->first();
        }

        if (! $draft) {
            return;
        }

        $meta = Cache::get("stock_inventory_draft_meta_{$draft->id}");
        $detailsPages = $meta['details_pages'] ?? [];
        $currentPage = (int) ($meta['current_page'] ?? 1);
        $pool = (array) ($meta['product_ids_pool'] ?? []);

        // إذا كان الكاش غير متوفر، نبني الصفحات من تفاصيل الجرد المحفوظة في قاعدة البيانات
        if (empty($detailsPages) && $draft->details()->exists()) {
            $details = $draft->details()->with(['product.supplyOutUnitPrices.unit'])->get();
            $pool = $details->pluck('product_id')->unique()->values()->all();
            $perPage = 20;
            $chunks = array_chunk($details->all(), $perPage);
            foreach ($chunks as $idx => $chunk) {
                $pageNum = $idx + 1;
                $detailsPages[$pageNum] = array_map(function ($d) {
                    $prod = $d->product;
                    $unitPrices = $prod?->supplyOutUnitPrices ?? collect();
                    return [
                        'product_id'        => $d->product_id,
                        'unit_id'           => $d->unit_id,
                        'package_size'      => (float) $d->package_size,
                        'system_quantity'   => (float) $d->system_quantity,
                        'physical_quantity' => (float) $d->physical_quantity,
                        'difference'        => (float) $d->difference,
                        'rowUnitsCache'     => $unitPrices->pluck('unit.name', 'unit_id')->toArray(),
                    ];
                }, $chunk);
            }
        }

        if (empty($pool) && empty($detailsPages)) {
            return;
        }

        $perPage = (int) ($meta['per_page'] ?? 20);
        $totalPages = (int) ceil(count($pool) / max(1, $perPage));

        $this->data['saved_draft_id']        = $draft->id;
        $this->data['store_id']              = $draft->store_id;
        $this->data['inventory_date']        = $draft->inventory_date;
        $this->data['responsible_user_id']   = $draft->responsible_user_id;
        $this->data['category_id']           = $meta['category_id'] ?? null;
        $this->data['unit_size_preference']  = $meta['unit_size_preference'] ?? 'smallest';
        $this->data['product_ids_pool']      = $pool;
        $this->data['details_pages']         = $detailsPages;
        $this->data['current_page']          = $currentPage;
        $this->data['total_pages']           = $totalPages;
        $this->data['per_page']              = $perPage;
        $this->data['page_details']          = $detailsPages[$currentPage] ?? [];
        $this->data['page_selector_bottom']  = $currentPage;

        $this->form->fill($this->data);

        Cache::put("stock_inventory_user_active_draft_{$userId}", $draft->id, now()->addDays(30));

        Notification::make()
            ->title('تمت استعادة مسودة الجرد تلقائياً')
            ->body("تمت استعادة بيانات الجرد غير المكتمل رقم #{$draft->id} (مخزن: {$draft->store?->name}) مع كافة الكميات المدخلة سابقاً.")
            ->info()
            ->duration(7000)
            ->send();
    }

    public function clearActiveDraft(): void
    {
        $userId = auth()->id();
        $draftId = $this->data['saved_draft_id'] ?? Cache::get("stock_inventory_user_active_draft_{$userId}");
        if ($draftId) {
            Cache::forget("stock_inventory_draft_meta_{$draftId}");
        }
        Cache::forget("stock_inventory_user_active_draft_{$userId}");
        $this->data['saved_draft_id'] = null;
    }

    public function updated($property = null): void
    {
        if (
            str_starts_with((string) $property, 'data.page_details') ||
            str_starts_with((string) $property, 'data.details_pages') ||
            $property === 'data.category_id' ||
            $property === 'data.store_id' ||
            $property === 'data.current_page'
        ) {
            $this->autoSaveDraft();
        }
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label(fn () => __('lang.create_stocktake')),
            $this->getCancelFormAction(),
        ];
    }

    protected function afterValidate(): void
    {
        try {
            $inventory = new StockInventory($this->data);
            \App\Validators\Inventory\StockInventoryCreationValidator::validate($inventory);
        } catch (ValidationException $e) {
            $messages = [];
            foreach ($e->errors() as $key => $errors) {
                $messages["data.{$key}"] = $errors;
            }
            throw ValidationException::withMessages($messages);
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        $draftId = $this->data['saved_draft_id'] ?? ($data['saved_draft_id'] ?? null);
        unset($data['saved_draft_id']);

        if ($draftId && $existing = StockInventory::find($draftId)) {
            $existing->update([
                'inventory_date'      => $data['inventory_date'] ?? $existing->inventory_date,
                'store_id'            => $data['store_id'] ?? $existing->store_id,
                'responsible_user_id' => $data['responsible_user_id'] ?? $existing->responsible_user_id,
                'finalized'           => false,
            ]);
            $existing->details()->delete();
            $existing->details()->createMany($data['details']);

            $this->clearActiveDraft();
            return $existing;
        }

        $record = new ($this->getModel())($data);

        if (
            static::getResource()::isScopedToTenant() &&
            ($tenant = Filament::getTenant())
        ) {
            $record = $this->associateRecordWithTenant($record, $tenant);
        } else {
            $record->save();
        }

        $record->details()->createMany($data['details']);

        $this->clearActiveDraft();
        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // جمع كل الصفحات من الكاش
        $pages       = (array) ($this->data['details_pages'] ?? []);
        $currentPage = (int) ($this->data['current_page'] ?? 1);
        $pageDetails = (array) ($this->data['page_details'] ?? []);

        // ✅ دمج الصفحة الحالية في الـ cache قبل الحفظ
        if (! empty($pageDetails)) {
            $pages[$currentPage] = $pageDetails;
        }

        // ترتيب الصفحات ودمجها
        ksort($pages);
        $merged = [];
        foreach ($pages as $rows) {
            foreach ((array) $rows as $row) {
                // نظّف حقول الواجهة قبل الحفظ
                unset($row['rowInventoryCache'], $row['rowUnitsCache']);
                $merged[] = $row;
            }
        }

        $data['details'] = $merged;

        return $data;
    }
}
