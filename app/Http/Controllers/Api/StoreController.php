<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Throwable;

class StoreController extends Controller
{
    /**
     * Display a listing of all stores as key-value pairs (id => name).
     *
     * قواعد الفلترة:
     * 1) استثناء المخزن الرئيسي (default_store) دائماً.
     * 2) مدير الفرع: يرى فقط المخزن المرتبط بفرعه.
     * 3) المستخدم المرتبط بفروع إضافية من نوع تصنيعي (central_kitchen): يرى مخازن تلك الفروع.
     * 4) أمين المخزن الرئيسي (default store keeper): لا يُعرض له شيء.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $stores = $this->storesQuery(auth()->user())
                ->orderBy('id')
                ->pluck('name', 'id');

            return response()->json([
                'success' => true,
                'count'   => $stores->count(),
                'data'    => $stores,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * يبني كويري المخازن المسموح للمستخدم برؤيتها.
     */
    protected function storesQuery(?User $user): Builder
    {
        // (1) استثناء المخزن الرئيسي دائماً
        $query = Store::query()->where(function (Builder $q) {
            $q->where('default_store', false)->orWhereNull('default_store');
        });

        // بدون مستخدم أو أمين المخزن الرئيسي (4) => لا شيء
        if (!$user || $user->isDefaultStoreManager()) {
            return $query->whereRaw('1 = 0');
        }

        // السوبر أدمن ومدير النظام: كل المخازن (عدا الرئيسي)
        if ($user->isSuperAdmin() || $user->isSystemManager()) {
            return $query;
        }

        $storeIds = [];

        // (2) مدير الفرع: مخزن فرعه فقط
        if ($user->isBranchManager()) {
            $storeIds = array_merge(
                $storeIds,
                Branch::withoutGlobalScopes()
                    ->forBranchManager('id')
                    ->whereNotNull('store_id')
                    ->pluck('store_id')
                    ->all()
            );
        }

        // (3) الفروع الإضافية من النوع التصنيعي => مخازنها
        $storeIds = array_merge(
            $storeIds,
            $user->branches()
                ->withoutGlobalScopes()
                ->centralKitchens()
                ->whereNotNull('branches.store_id')
                ->pluck('branches.store_id')
                ->all()
        );

        return $query->whereIn('id', array_values(array_unique($storeIds)));
    }
}
