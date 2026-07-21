<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hr_approval_policies', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_approval_policies', 'branch_ids')) {
                $table->json('branch_ids')->nullable()->after('branch_id');
            }
        });

        // Migrate data
        \Illuminate\Support\Facades\DB::table('hr_approval_policies')
            ->whereNotNull('branch_id')
            ->orderBy('id')
            ->chunk(100, function ($policies) {
                foreach ($policies as $policy) {
                    \Illuminate\Support\Facades\DB::table('hr_approval_policies')
                        ->where('id', $policy->id)
                        ->update(['branch_ids' => json_encode([$policy->branch_id])]);
                }
            });

        Schema::table('hr_approval_policies', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_approval_policies', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('branch_ids');
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
        });

        // Migrate data back (only taking the first branch_id from the JSON array)
        \Illuminate\Support\Facades\DB::table('hr_approval_policies')
            ->whereNotNull('branch_ids')
            ->orderBy('id')
            ->chunk(100, function ($policies) {
                foreach ($policies as $policy) {
                    $branchIds = json_decode($policy->branch_ids, true);
                    $firstBranchId = is_array($branchIds) && count($branchIds) > 0 ? $branchIds[0] : null;
                    
                    if ($firstBranchId) {
                        \Illuminate\Support\Facades\DB::table('hr_approval_policies')
                            ->where('id', $policy->id)
                            ->update(['branch_id' => $firstBranchId]);
                    }
                }
            });

        Schema::table('hr_approval_policies', function (Blueprint $table) {
            $table->dropColumn('branch_ids');
        });
    }
};
