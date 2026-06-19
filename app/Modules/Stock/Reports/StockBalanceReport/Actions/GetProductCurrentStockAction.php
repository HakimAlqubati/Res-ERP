<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\StockBalanceReport\Actions;

use App\Modules\Stock\Reports\StockBalanceReport\Contracts\StockBalanceRepositoryInterface;
use Exception;

final readonly class GetProductCurrentStockAction
{
    /**
     * نحقن المستودع لجلب الرصيد الخام، ومُنسّق (Mapper) لتحويله إلى وحدات متعددة.
     * (سنقوم بإنشاء الـ Mapper لاحقاً)
     */
    public function __construct(
        private StockBalanceRepositoryInterface $repository
        // private ProductStockMapper $mapper  👈 سنضيفه لاحقاً لتحويل الرصيد للوحدات
    ) {}

    /**
     * تنفيذ الإجراء.
     * نمرر رقم المنتج ورقم المخزن مباشرة لأنه طلب بسيط لا يحتاج لـ DTO معقد.
     * * @throws Exception إذا لم يتم العثور على المنتج
     */
    public function execute(int $productId, int $storeId): array
    {
        // 1. استدعاء دالة مخصصة من الـ Repository لجلب رصيد هذا المنتج فقط
        $rawStock = $this->repository->getSingleProductBalance($productId, $storeId);

        if (!$rawStock) {
            throw new Exception("❌ Product (ID: {$productId}) not found in store (ID: {$storeId})");
        }

        // 2. هنا سنستخدم الـ Mapper لتحويل الرصيد الأساسي (Base Qty) 
        // إلى تفاصيل الوحدات المختلفة (كرتون، حبة، إلخ) وأسعارها.
        // return $this->mapper->mapToUnits($rawStock, $productId);
        
        return (array) $rawStock; // مؤقتاً نعيد البيانات الخام حتى نبني الـ Mapper
    }
}