<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FastFoodAccountingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing accounts for testing
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Account::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $data = [
            [
                'account_code' => '1',
                'account_name' => 'الأصول',
                'account_type' => Account::TYPE_ASSET,
                'is_parent' => true,
                'children' => [
                    [
                        'account_code' => '11',
                        'account_name' => 'الأصول المتداولة',
                        'account_type' => Account::TYPE_ASSET,
                        'is_parent' => true,
                        'children' => [
                            [
                                'account_code' => '111',
                                'account_name' => 'النقدية وما في حكمها',
                                'account_type' => Account::TYPE_ASSET,
                                'is_parent' => true,
                                'children' => [
                                    ['account_code' => '1111', 'account_name' => 'الصندوق الرئيسي', 'account_type' => Account::TYPE_ASSET, 'is_parent' => false],
                                    ['account_code' => '1112', 'account_name' => 'صندوق مبيعات فرع 1', 'account_type' => Account::TYPE_ASSET, 'is_parent' => false],
                                    ['account_code' => '1113', 'account_name' => 'بنك التضامن الإسلامي', 'account_type' => Account::TYPE_ASSET, 'is_parent' => false],
                                ]
                            ],
                            [
                                'account_code' => '112',
                                'account_name' => 'المخزون',
                                'account_type' => Account::TYPE_ASSET,
                                'is_parent' => true,
                                'children' => [
                                    ['account_code' => '1121', 'account_name' => 'مخزن المواد الخام', 'account_type' => Account::TYPE_ASSET, 'is_parent' => false],
                                    ['account_code' => '1122', 'account_name' => 'مخزن مواد التعبئة', 'account_type' => Account::TYPE_ASSET, 'is_parent' => false],
                                ]
                            ],
                        ]
                    ],
                    [
                        'account_code' => '12',
                        'account_name' => 'الأصول الثابتة',
                        'account_type' => Account::TYPE_ASSET,
                        'is_parent' => true,
                        'children' => [
                            ['account_code' => '121', 'account_name' => 'معدات المطبخ', 'account_type' => Account::TYPE_ASSET, 'is_parent' => false],
                            ['account_code' => '122', 'account_name' => 'الأثاث والتجهيزات', 'account_type' => Account::TYPE_ASSET, 'is_parent' => false],
                        ]
                    ],
                ]
            ],
            [
                'account_code' => '2',
                'account_name' => 'الخصوم',
                'account_type' => Account::TYPE_LIABILITY,
                'is_parent' => true,
                'children' => [
                    ['account_code' => '21', 'account_name' => 'الموردون', 'account_type' => Account::TYPE_LIABILITY, 'is_parent' => false],
                    ['account_code' => '22', 'account_name' => 'مصاريف مستحقة', 'account_type' => Account::TYPE_LIABILITY, 'is_parent' => false],
                ]
            ],
            [
                'account_code' => '3',
                'account_name' => 'حقوق الملكية',
                'account_type' => Account::TYPE_EQUITY,
                'is_parent' => true,
                'children' => [
                    ['account_code' => '31', 'account_name' => 'رأس المال', 'account_type' => Account::TYPE_EQUITY, 'is_parent' => false],
                    ['account_code' => '32', 'account_name' => 'الأرباح المحتجزة', 'account_type' => Account::TYPE_EQUITY, 'is_parent' => false],
                ]
            ],
            [
                'account_code' => '4',
                'account_name' => 'الإيرادات',
                'account_type' => Account::TYPE_REVENUE,
                'is_parent' => true,
                'children' => [
                    ['account_code' => '41', 'account_name' => 'مبيعات الوجبات', 'account_type' => Account::TYPE_REVENUE, 'is_parent' => false],
                    ['account_code' => '42', 'account_name' => 'إيرادات التوصيل', 'account_type' => Account::TYPE_REVENUE, 'is_parent' => false],
                ]
            ],
            [
                'account_code' => '5',
                'account_name' => 'المصروفات',
                'account_type' => Account::TYPE_EXPENSE,
                'is_parent' => true,
                'children' => [
                    ['account_code' => '51', 'account_name' => 'تكلفة المبيعات', 'account_type' => Account::TYPE_EXPENSE, 'is_parent' => false],
                    ['account_code' => '52', 'account_name' => 'رواتب وأجور', 'account_type' => Account::TYPE_EXPENSE, 'is_parent' => false],
                    ['account_code' => '53', 'account_name' => 'إيجار المطعم', 'account_type' => Account::TYPE_EXPENSE, 'is_parent' => false],
                ]
            ],
        ];

        $this->seedAccounts($data);
    }

    protected function seedAccounts(array $accounts, $parentId = null): void
    {
        foreach ($accounts as $accountData) {
            $children = $accountData['children'] ?? [];
            unset($accountData['children']);

            $accountData['parent_id'] = $parentId;
            $accountData['is_active'] = true;
            $accountData['allow_manual_entries'] = true;

            $account = Account::create($accountData);

            if (!empty($children)) {
                $this->seedAccounts($children, $account->id);
            }
        }
    }
}
