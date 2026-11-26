<?php

namespace Tests\Feature;

use App\Http\Controllers\TestController4;
use App\Models\Branch;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TestController4GetOrdersTest extends TestCase
{
    use DatabaseTransactions;

    private function createBranch(string $type, bool $active = true, bool $managerAbleShowOrders = false, ?int $managerId = null): int
    {
        return DB::table('branches')->insertGetId([
            'name' => 'Test Branch '.uniqid(),
            'type' => $type,
            'active' => $active ? 1 : 0,
            'manager_abel_show_orders' => $managerAbleShowOrders ? 1 : 0,
            'manager_id' => $managerId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUser(?int $branchId, int $roleId, ?int $ownerId = null): User
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test User '.uniqid(),
            'email' => 'test_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $branchId,
            'owner_id' => $ownerId,
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::withoutGlobalScopes()->findOrFail($userId);
        $role = Role::findById($roleId, 'web');
        $user->assignRole($role);

        return $user;
    }

    private function createCategory(bool $isManufacturing): int
    {
        return DB::table('categories')->insertGetId([
            'name' => 'Test Cat '.uniqid(),
            'active' => 1,
            'is_manafacturing' => $isManufacturing ? 1 : 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createProduct(int $categoryId): int
    {
        return DB::table('products')->insertGetId([
            'name' => 'Test Prod '.uniqid(),
            'category_id' => $categoryId,
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUnit(): int
    {
        return DB::table('units')->insertGetId([
            'name' => 'Test Unit '.uniqid(),
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createOrderWithProduct(int $branchId, int $customerId, int $productId, int $unitId, string $status = Order::ORDERED): int
    {
        $orderId = DB::table('orders')->insertGetId([
            'branch_id' => $branchId,
            'customer_id' => $customerId,
            'status' => $status,
            'active' => 1,
            'type' => Order::TYPE_NORMAL,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('orders_details')->insert([
            'order_id' => $orderId,
            'product_id' => $productId,
            'unit_id' => $unitId,
            'quantity' => 1,
            'available_quantity' => 1,
            'price' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $orderId;
    }

    private function fetchOrderIds(User $user, array $params = []): array
    {
        $this->actingAs($user, 'api');
        auth()->setUser($user->fresh('roles', 'branches', 'branch'));

        $request = Request::create('/api/testGetOrders', 'GET', array_merge(['per_page' => 200], $params));
        $response = TestController4::getOrders($request);
        $payload = $response->getData(true);

        return collect($payload['data'])->pluck('id')->all();
    }

    public function test_non_branch_manager_with_extra_central_kitchen_when_show_orders_disabled_sees_kitchen_and_own_orders(): void
    {
        $unitId = $this->createUnit();
        $normalCatId = $this->createCategory(false);
        $normalProductId = $this->createProduct($normalCatId);

        $primaryBranchId = $this->createBranch(Branch::TYPE_BRANCH, true, false);
        $kitchenBranchId = $this->createBranch(Branch::TYPE_CENTRAL_KITCHEN, true, false);
        $otherBranchId = $this->createBranch(Branch::TYPE_BRANCH, true, false);

        $ownerUser = $this->createUser($primaryBranchId, 7);
        $otherUser = $this->createUser($otherBranchId, 7);

        // Role 8 = Branch Staff (NOT Branch Manager role 7), linked to kitchen via branch_user (extra_branches)
        $currentUser = $this->createUser($primaryBranchId, 8, $ownerUser->id);
        DB::table('branches')->where('id', $kitchenBranchId)->update(['manager_id' => $currentUser->id]);
        DB::table('branch_user')->insert([
            'branch_id' => $kitchenBranchId,
            'user_id' => $currentUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $kitchenOwnOrderId = $this->createOrderWithProduct($kitchenBranchId, $currentUser->id, $normalProductId, $unitId);
        $kitchenOtherCustomerOrderId = $this->createOrderWithProduct($kitchenBranchId, $otherUser->id, $normalProductId, $unitId);
        $primaryOwnOrderId = $this->createOrderWithProduct($primaryBranchId, $currentUser->id, $normalProductId, $unitId);
        $primaryOwnerOrderId = $this->createOrderWithProduct($primaryBranchId, $ownerUser->id, $normalProductId, $unitId);
        $otherBranchOrderId = $this->createOrderWithProduct($otherBranchId, $otherUser->id, $normalProductId, $unitId);

        $ids = $this->fetchOrderIds($currentUser);

        $this->assertContains($kitchenOwnOrderId, $ids);
        $this->assertContains($kitchenOtherCustomerOrderId, $ids);
        $this->assertContains($primaryOwnOrderId, $ids);
        $this->assertNotContains($primaryOwnerOrderId, $ids);
        $this->assertNotContains($otherBranchOrderId, $ids);
    }

    public function test_non_branch_manager_with_extra_central_kitchen_when_show_orders_enabled_sees_kitchen_non_mfg_own_and_mfg_orders(): void
    {
        $unitId = $this->createUnit();
        $normalCatId = $this->createCategory(false);
        $mfgCatId = $this->createCategory(true);
        $otherKitchenCustomCatId = $this->createCategory(true);

        $normalProductId = $this->createProduct($normalCatId);
        $mfgProductId = $this->createProduct($mfgCatId);
        $otherKitchenCustomProductId = $this->createProduct($otherKitchenCustomCatId);

        $primaryBranchId = $this->createBranch(Branch::TYPE_BRANCH, true, false);
        $inactiveBranchId = $this->createBranch(Branch::TYPE_BRANCH, false, false);
        $kitchenBranchId = $this->createBranch(Branch::TYPE_CENTRAL_KITCHEN, true, true);
        $otherKitchenBranchId = $this->createBranch(Branch::TYPE_CENTRAL_KITCHEN, true, true);

        DB::table('branch_category')->insert([
            'branch_id' => $otherKitchenBranchId,
            'category_id' => $otherKitchenCustomCatId,
        ]);

        $otherUser = $this->createUser($primaryBranchId, 7);

        // Role 8 = Branch Staff (NOT Branch Manager role 7), linked to kitchen via branch_user (extra_branches)
        $currentUser = $this->createUser($primaryBranchId, 8, $otherUser->id);
        DB::table('branches')->where('id', $kitchenBranchId)->update(['manager_id' => $currentUser->id]);
        DB::table('branch_user')->insert([
            'branch_id' => $kitchenBranchId,
            'user_id' => $currentUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Orders that SHOULD be visible:
        $kitchenNormalByOtherId = $this->createOrderWithProduct($kitchenBranchId, $otherUser->id, $normalProductId, $unitId);
        $ownOrderOnInactiveBranchId = $this->createOrderWithProduct($inactiveBranchId, $currentUser->id, $normalProductId, $unitId);
        $otherBranchMfgOrderId = $this->createOrderWithProduct($primaryBranchId, $otherUser->id, $mfgProductId, $unitId);

        // Orders that SHOULD NOT be visible:
        $otherBranchNormalOrderId = $this->createOrderWithProduct($primaryBranchId, $otherUser->id, $normalProductId, $unitId);
        $otherKitchenCustomOrderId = $this->createOrderWithProduct($primaryBranchId, $otherUser->id, $otherKitchenCustomProductId, $unitId);

        $ids = $this->fetchOrderIds($currentUser);

        $this->assertContains($kitchenNormalByOtherId, $ids);
        $this->assertContains($ownOrderOnInactiveBranchId, $ids);
        $this->assertContains($otherBranchMfgOrderId, $ids);
        $this->assertNotContains($otherBranchNormalOrderId, $ids);
        $this->assertNotContains($otherKitchenCustomOrderId, $ids);
    }

    public function test_regular_branch_staff_without_central_kitchen_still_only_sees_owner_orders(): void
    {
        $unitId = $this->createUnit();
        $normalCatId = $this->createCategory(false);
        $normalProductId = $this->createProduct($normalCatId);

        $branchId = $this->createBranch(Branch::TYPE_BRANCH, true, false);
        $otherBranchId = $this->createBranch(Branch::TYPE_BRANCH, true, false);

        $ownerUser = $this->createUser($branchId, 7);
        $otherUser = $this->createUser($otherBranchId, 7);
        $staffUser = $this->createUser($branchId, 8, $ownerUser->id);

        $ownerOrderId = $this->createOrderWithProduct($branchId, $ownerUser->id, $normalProductId, $unitId);
        $otherOrderId = $this->createOrderWithProduct($otherBranchId, $otherUser->id, $normalProductId, $unitId);

        $ids = $this->fetchOrderIds($staffUser);

        $this->assertContains($ownerOrderId, $ids);
        $this->assertNotContains($otherOrderId, $ids);
    }

    public function test_store_manager_with_extra_central_kitchen_still_excludes_pending_approval(): void
    {
        $unitId = $this->createUnit();
        $normalCatId = $this->createCategory(false);
        $normalProductId = $this->createProduct($normalCatId);

        $kitchenBranchId = $this->createBranch(Branch::TYPE_CENTRAL_KITCHEN, true, false);
        $storeManager = $this->createUser(null, 5);
        DB::table('branch_user')->insert([
            'branch_id' => $kitchenBranchId,
            'user_id' => $storeManager->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pendingOrderId = $this->createOrderWithProduct($kitchenBranchId, $storeManager->id, $normalProductId, $unitId, Order::PENDING_APPROVAL);
        $orderedOrderId = $this->createOrderWithProduct($kitchenBranchId, $storeManager->id, $normalProductId, $unitId, Order::ORDERED);

        $ids = $this->fetchOrderIds($storeManager);

        $this->assertNotContains($pendingOrderId, $ids);
        $this->assertContains($orderedOrderId, $ids);
    }

    public function test_user_with_null_primary_branch_and_extra_central_kitchen_sees_own_and_kitchen_orders(): void
    {
        $unitId = $this->createUnit();
        $normalCatId = $this->createCategory(false);
        $normalProductId = $this->createProduct($normalCatId);

        $kitchenBranchId = $this->createBranch(Branch::TYPE_CENTRAL_KITCHEN, true, false);
        $otherBranchId = $this->createBranch(Branch::TYPE_BRANCH, true, false);

        // User with null primary branch_id and role 8 (not Branch Manager)
        $currentUser = $this->createUser(null, 8);
        DB::table('branch_user')->insert([
            'branch_id' => $kitchenBranchId,
            'user_id' => $currentUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $kitchenOrderId = $this->createOrderWithProduct($kitchenBranchId, $currentUser->id, $normalProductId, $unitId);
        $ownOtherBranchOrderId = $this->createOrderWithProduct($otherBranchId, $currentUser->id, $normalProductId, $unitId);

        $ids = $this->fetchOrderIds($currentUser, ['customer_id' => $currentUser->id]);

        $this->assertContains($kitchenOrderId, $ids);
        $this->assertContains($ownOtherBranchOrderId, $ids);
    }

    public function test_regular_branch_manager_without_central_kitchen_only_sees_own_branch_orders(): void
    {
        $unitId = $this->createUnit();
        $normalCatId = $this->createCategory(false);
        $normalProductId = $this->createProduct($normalCatId);

        $branchId = $this->createBranch(Branch::TYPE_BRANCH, true, false);
        $otherBranchId = $this->createBranch(Branch::TYPE_BRANCH, true, false);

        $branchManager = $this->createUser($branchId, 7);
        $otherUser = $this->createUser($otherBranchId, 7);

        $ownBranchOrderId = $this->createOrderWithProduct($branchId, $branchManager->id, $normalProductId, $unitId);
        $otherBranchOrderId = $this->createOrderWithProduct($otherBranchId, $otherUser->id, $normalProductId, $unitId);

        $ids = $this->fetchOrderIds($branchManager);

        $this->assertContains($ownBranchOrderId, $ids);
        $this->assertNotContains($otherBranchOrderId, $ids);
    }

    public function test_http_route_with_auth_api_middleware_and_reseller_total_paid(): void
    {
        $unitId = $this->createUnit();
        $normalCatId = $this->createCategory(false);
        $normalProductId = $this->createProduct($normalCatId);

        $resellerBranchId = $this->createBranch(Branch::TYPE_RESELLER, true, false);
        $kitchenBranchId = $this->createBranch(Branch::TYPE_CENTRAL_KITCHEN, true, false);

        // Role 9 = Accountant (non-Branch Manager) linked via extra_branches
        $currentUser = $this->createUser($resellerBranchId, 9);
        DB::table('branch_user')->insert([
            'branch_id' => $kitchenBranchId,
            'user_id' => $currentUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resellerOwnOrderId = $this->createOrderWithProduct($resellerBranchId, $currentUser->id, $normalProductId, $unitId);
        $kitchenOrderId = $this->createOrderWithProduct($kitchenBranchId, $currentUser->id, $normalProductId, $unitId);

        DB::table('order_paid_amounts')->insert([
            'order_id' => $resellerOwnOrderId,
            'amount' => 125.50,
            'paid_at' => now(),
            'created_by' => $currentUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($currentUser, 'api')
            ->getJson('/api/testGetOrders?per_page=200');

        $response->assertOk();
        $payload = $response->json();

        $ids = collect($payload['data'])->pluck('id')->all();
        $this->assertContains($resellerOwnOrderId, $ids);
        $this->assertContains($kitchenOrderId, $ids);
        $this->assertSame($payload['total'], count($payload['data']));
        $this->assertEquals(125.50, $payload['total_paid']);

        $resellerRow = collect($payload['data'])->firstWhere('id', $resellerOwnOrderId);
        $this->assertEquals(125.50, $resellerRow['total_paid']);
    }

    public function test_show_orders_enabled_with_no_other_active_branches_still_returns_own_and_kitchen_orders(): void
    {
        $unitId = $this->createUnit();
        $normalCatId = $this->createCategory(false);
        $normalProductId = $this->createProduct($normalCatId);

        // Deactivate all pre-existing branches within transaction so count($branchIds) === 0
        DB::table('branches')->update(['active' => 0]);

        $inactiveBranchId = $this->createBranch(Branch::TYPE_BRANCH, false, false);
        $kitchenBranchId = $this->createBranch(Branch::TYPE_CENTRAL_KITCHEN, true, true);
        $otherUser = $this->createUser($inactiveBranchId, 7);

        $currentUser = $this->createUser(null, 8);
        DB::table('branch_user')->insert([
            'branch_id' => $kitchenBranchId,
            'user_id' => $currentUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $kitchenOrderId = $this->createOrderWithProduct($kitchenBranchId, $otherUser->id, $normalProductId, $unitId);
        $ownInactiveBranchOrderId = $this->createOrderWithProduct($inactiveBranchId, $currentUser->id, $normalProductId, $unitId);
        $otherInactiveBranchOrderId = $this->createOrderWithProduct($inactiveBranchId, $otherUser->id, $normalProductId, $unitId);

        $ids = $this->fetchOrderIds($currentUser);

        $this->assertContains($kitchenOrderId, $ids);
        $this->assertContains($ownInactiveBranchOrderId, $ids);
        $this->assertNotContains($otherInactiveBranchOrderId, $ids);
    }

    public function test_show_orders_enabled_includes_own_kitchen_custom_category_and_own_order_with_other_kitchen_category(): void
    {
        $unitId = $this->createUnit();
        $ownKitchenCatId = $this->createCategory(true);
        $otherKitchenCatId = $this->createCategory(true);

        $ownKitchenProdId = $this->createProduct($ownKitchenCatId);
        $otherKitchenProdId = $this->createProduct($otherKitchenCatId);

        $normalBranchId = $this->createBranch(Branch::TYPE_BRANCH, true, false);
        $ownKitchenId = $this->createBranch(Branch::TYPE_CENTRAL_KITCHEN, true, true);
        $otherKitchenId = $this->createBranch(Branch::TYPE_CENTRAL_KITCHEN, true, true);

        DB::table('branch_category')->insert([
            ['branch_id' => $ownKitchenId, 'category_id' => $ownKitchenCatId],
            ['branch_id' => $otherKitchenId, 'category_id' => $otherKitchenCatId],
        ]);

        $otherUser = $this->createUser($normalBranchId, 7);
        $currentUser = $this->createUser($normalBranchId, 8);
        DB::table('branch_user')->insert([
            'branch_id' => $ownKitchenId,
            'user_id' => $currentUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherBranchOwnKitchenCatOrderId = $this->createOrderWithProduct($normalBranchId, $otherUser->id, $ownKitchenProdId, $unitId);
        $ownOrderWithOtherKitchenCatId = $this->createOrderWithProduct($normalBranchId, $currentUser->id, $otherKitchenProdId, $unitId);
        $kitchenOrderWithOtherKitchenCatId = $this->createOrderWithProduct($ownKitchenId, $otherUser->id, $otherKitchenProdId, $unitId);
        $otherBranchOtherKitchenCatOrderId = $this->createOrderWithProduct($normalBranchId, $otherUser->id, $otherKitchenProdId, $unitId);

        $ids = $this->fetchOrderIds($currentUser);

        $this->assertContains($otherBranchOwnKitchenCatOrderId, $ids);
        $this->assertContains($ownOrderWithOtherKitchenCatId, $ids);
        $this->assertContains($kitchenOrderWithOtherKitchenCatId, $ids);
        $this->assertNotContains($otherBranchOtherKitchenCatOrderId, $ids);
    }

    private function createOrderWithProducts(int $branchId, int $customerId, array $productIds, int $unitId, string $status = Order::ORDERED): int
    {
        $orderId = DB::table('orders')->insertGetId([
            'branch_id' => $branchId,
            'customer_id' => $customerId,
            'status' => $status,
            'active' => 1,
            'type' => Order::TYPE_NORMAL,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($productIds as $productId) {
            DB::table('orders_details')->insert([
                'order_id' => $orderId,
                'product_id' => $productId,
                'unit_id' => $unitId,
                'quantity' => 1,
                'available_quantity' => 1,
                'price' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $orderId;
    }

    public function test_non_branch_manager_additional_branch_manager_without_central_kitchen_sees_managed_branch_and_own_orders(): void
    {
        $unitId = $this->createUnit();
        $normalCatId = $this->createCategory(false);
        $normalProductId = $this->createProduct($normalCatId);

        $primaryBranchId = $this->createBranch(Branch::TYPE_BRANCH, true, false);
        $managedExtraBranchId = $this->createBranch(Branch::TYPE_BRANCH, true, false);
        $otherBranchId = $this->createBranch(Branch::TYPE_BRANCH, true, false);

        $ownerUser = $this->createUser($primaryBranchId, 7);
        $otherUser = $this->createUser($otherBranchId, 7);

        // Role 8 = Branch Staff (NOT Branch Manager role 7), manager_id on an extra non-kitchen branch
        $currentUser = $this->createUser($primaryBranchId, 8, $ownerUser->id);
        DB::table('branches')->where('id', $managedExtraBranchId)->update(['manager_id' => $currentUser->id]);
        DB::table('branch_user')->insert([
            'branch_id' => $managedExtraBranchId,
            'user_id' => $currentUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $managedBranchByOtherOrderId = $this->createOrderWithProduct($managedExtraBranchId, $otherUser->id, $normalProductId, $unitId);
        $managedBranchOwnOrderId = $this->createOrderWithProduct($managedExtraBranchId, $currentUser->id, $normalProductId, $unitId);
        $primaryOwnOrderId = $this->createOrderWithProduct($primaryBranchId, $currentUser->id, $normalProductId, $unitId);
        $primaryOwnerOrderId = $this->createOrderWithProduct($primaryBranchId, $ownerUser->id, $normalProductId, $unitId);
        $otherBranchOrderId = $this->createOrderWithProduct($otherBranchId, $otherUser->id, $normalProductId, $unitId);

        $ids = $this->fetchOrderIds($currentUser);

        $this->assertContains($managedBranchByOtherOrderId, $ids);
        $this->assertContains($managedBranchOwnOrderId, $ids);
        $this->assertContains($primaryOwnOrderId, $ids);
        $this->assertNotContains($primaryOwnerOrderId, $ids);
        $this->assertNotContains($otherBranchOrderId, $ids);
    }

    public function test_without_global_scopes_bypasses_stuff_scope_when_branch_boots_after_authentication(): void
    {
        $unitId = $this->createUnit();
        $ownKitchenCatId = $this->createCategory(true);
        $otherKitchenCatId = $this->createCategory(true);

        $ownKitchenProdId = $this->createProduct($ownKitchenCatId);
        $otherKitchenProdId = $this->createProduct($otherKitchenCatId);

        $normalBranchId = $this->createBranch(Branch::TYPE_BRANCH, true, false);
        $ownKitchenId = $this->createBranch(Branch::TYPE_CENTRAL_KITCHEN, true, true);
        $otherKitchenId = $this->createBranch(Branch::TYPE_CENTRAL_KITCHEN, true, true);

        DB::table('branch_category')->insert([
            ['branch_id' => $ownKitchenId, 'category_id' => $ownKitchenCatId],
            ['branch_id' => $otherKitchenId, 'category_id' => $otherKitchenCatId],
        ]);

        $otherUser = $this->createUser($normalBranchId, 7);
        $currentUser = $this->createUser($normalBranchId, 8);
        DB::table('branch_user')->insert([
            'branch_id' => $ownKitchenId,
            'user_id' => $currentUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherKitchenOnlyOrderId = $this->createOrderWithProduct($normalBranchId, $otherUser->id, $otherKitchenProdId, $unitId);
        $mixedOrderId = $this->createOrderWithProducts($normalBranchId, $otherUser->id, [$ownKitchenProdId, $otherKitchenProdId], $unitId);

        try {
            $this->actingAs($currentUser, 'api');
            auth()->setUser($currentUser->fresh('roles', 'branches', 'branch'));

            // Force Branch::booted() to run while Role 8 user is authenticated, activating stuff_scope
            Branch::clearBootedModels();

            // Confirm stuff_scope actually hides $otherKitchenId from scoped Branch::centralKitchens()
            $scopedKitchenIds = Branch::centralKitchens()->pluck('id')->all();
            $this->assertContains($ownKitchenId, $scopedKitchenIds);
            $this->assertNotContains($otherKitchenId, $scopedKitchenIds);

            $request = Request::create('/api/testGetOrders', 'GET', ['per_page' => 200]);
            $orderIds = collect(TestController4::getOrders($request)->getData(true)['data'])->pluck('id')->all();

            $this->assertNotContains($otherKitchenOnlyOrderId, $orderIds);
            $this->assertContains($mixedOrderId, $orderIds);

            $detailsResponse = $this->getJson("/api/testGetOrdersDetails/{$mixedOrderId}");
            $detailsResponse->assertOk();
            $detailProductIds = collect($detailsResponse->json())->pluck('product_id')->all();

            $this->assertContains($ownKitchenProdId, $detailProductIds);
            $this->assertNotContains($otherKitchenProdId, $detailProductIds);
        } finally {
            auth()->forgetGuards();
            Branch::clearBootedModels();
        }
    }

    public function test_get_orders_details_for_extra_central_kitchen_non_branch_manager(): void
    {
        $unitId = $this->createUnit();
        $normalCatId = $this->createCategory(false);
        $ownKitchenCatId = $this->createCategory(true);
        $otherKitchenCatId = $this->createCategory(true);

        $normalProdId = $this->createProduct($normalCatId);
        $ownKitchenProdId = $this->createProduct($ownKitchenCatId);
        $otherKitchenProdId = $this->createProduct($otherKitchenCatId);

        $normalBranchId = $this->createBranch(Branch::TYPE_BRANCH, true, false);
        $ownKitchenId = $this->createBranch(Branch::TYPE_CENTRAL_KITCHEN, true, true);
        $otherKitchenId = $this->createBranch(Branch::TYPE_CENTRAL_KITCHEN, true, true);

        DB::table('branch_category')->insert([
            ['branch_id' => $ownKitchenId, 'category_id' => $ownKitchenCatId],
            ['branch_id' => $otherKitchenId, 'category_id' => $otherKitchenCatId],
        ]);

        $otherUser = $this->createUser($normalBranchId, 7);
        $currentUser = $this->createUser($normalBranchId, 8);
        DB::table('branches')->where('id', $ownKitchenId)->update(['manager_id' => $currentUser->id]);
        DB::table('branch_user')->insert([
            'branch_id' => $ownKitchenId,
            'user_id' => $currentUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 1) Own order on another branch with normal + other-kitchen category products -> both visible
        $ownOrderId = $this->createOrderWithProducts($normalBranchId, $currentUser->id, [$normalProdId, $otherKitchenProdId], $unitId);

        // 2) Order on own kitchen by another user with normal + other-kitchen category products -> both visible
        $kitchenOrderByOtherId = $this->createOrderWithProducts($ownKitchenId, $otherUser->id, [$normalProdId, $otherKitchenProdId], $unitId);

        // 3) Order on another branch by another user with normal + own-kitchen + other-kitchen -> only own-kitchen manufacturing visible
        $otherBranchOrderId = $this->createOrderWithProducts($normalBranchId, $otherUser->id, [$normalProdId, $ownKitchenProdId, $otherKitchenProdId], $unitId);

        $ownDetails = $this->actingAs($currentUser, 'api')
            ->getJson("/api/testGetOrdersDetails/{$ownOrderId}")
            ->assertOk()
            ->json();
        $this->assertEqualsCanonicalizing([$normalProdId, $otherKitchenProdId], collect($ownDetails)->pluck('product_id')->all());

        $kitchenDetails = $this->actingAs($currentUser, 'api')
            ->getJson("/api/testGetOrdersDetails/{$kitchenOrderByOtherId}")
            ->assertOk()
            ->json();
        $this->assertEqualsCanonicalizing([$normalProdId, $otherKitchenProdId], collect($kitchenDetails)->pluck('product_id')->all());

        $otherBranchDetails = $this->actingAs($currentUser, 'api')
            ->getJson("/api/testGetOrdersDetails/{$otherBranchOrderId}")
            ->assertOk()
            ->json();
        $this->assertSame([$ownKitchenProdId], collect($otherBranchDetails)->pluck('product_id')->all());
    }

    public function test_get_orders_details_for_additional_branch_manager_without_central_kitchen(): void
    {
        $unitId = $this->createUnit();
        $normalCatId = $this->createCategory(false);
        $normalProdId = $this->createProduct($normalCatId);

        $primaryBranchId = $this->createBranch(Branch::TYPE_BRANCH, true, false);
        $managedExtraBranchId = $this->createBranch(Branch::TYPE_BRANCH, true, false);

        $otherUser = $this->createUser($managedExtraBranchId, 7);
        $currentUser = $this->createUser($primaryBranchId, 8);
        DB::table('branches')->where('id', $managedExtraBranchId)->update(['manager_id' => $currentUser->id]);
        DB::table('branch_user')->insert([
            'branch_id' => $managedExtraBranchId,
            'user_id' => $currentUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $managedOrderId = $this->createOrderWithProduct($managedExtraBranchId, $otherUser->id, $normalProdId, $unitId);

        $details = $this->actingAs($currentUser, 'api')
            ->getJson("/api/testGetOrdersDetails/{$managedOrderId}")
            ->assertOk()
            ->json();

        $this->assertSame([$normalProdId], collect($details)->pluck('product_id')->all());
    }

    public function test_soft_deleted_central_kitchen_categories_are_not_excluded_under_without_global_scopes(): void
    {
        $unitId = $this->createUnit();
        $deletedKitchenCatId = $this->createCategory(true);
        $deletedKitchenProdId = $this->createProduct($deletedKitchenCatId);

        $normalBranchId = $this->createBranch(Branch::TYPE_BRANCH, true, false);
        $ownKitchenId = $this->createBranch(Branch::TYPE_CENTRAL_KITCHEN, true, true);
        $deletedKitchenId = $this->createBranch(Branch::TYPE_CENTRAL_KITCHEN, true, true);

        DB::table('branches')->where('id', $deletedKitchenId)->update(['deleted_at' => now()]);
        DB::table('branch_category')->insert([
            'branch_id' => $deletedKitchenId,
            'category_id' => $deletedKitchenCatId,
        ]);

        $otherUser = $this->createUser($normalBranchId, 7);
        $currentUser = $this->createUser($normalBranchId, 8);
        DB::table('branch_user')->insert([
            'branch_id' => $ownKitchenId,
            'user_id' => $currentUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderId = $this->createOrderWithProduct($normalBranchId, $otherUser->id, $deletedKitchenProdId, $unitId);

        try {
            $this->actingAs($currentUser, 'api');
            auth()->setUser($currentUser->fresh('roles', 'branches', 'branch'));
            Branch::clearBootedModels();

            $request = Request::create('/api/testGetOrders', 'GET', ['per_page' => 200]);
            $orderIds = collect(TestController4::getOrders($request)->getData(true)['data'])->pluck('id')->all();

            $this->assertContains($orderId, $orderIds);

            $details = $this->getJson("/api/testGetOrdersDetails/{$orderId}")
                ->assertOk()
                ->json();
            $this->assertSame([$deletedKitchenProdId], collect($details)->pluck('product_id')->all());
        } finally {
            auth()->forgetGuards();
            Branch::clearBootedModels();
        }
    }
}
