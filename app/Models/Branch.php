<?php

namespace App\Models;

use App\Models\Branch\Traits\BranchAttributes;
use App\Models\Branch\Traits\BranchBootEvents;
use App\Models\Branch\Traits\BranchConstants;
use App\Models\Branch\Traits\BranchRelations;
use App\Models\Branch\Traits\BranchScopes;
use App\Models\Branch\Traits\BranchAggregates;
use App\Traits\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Branch extends Model implements HasMedia, Auditable
{
    use HasFactory,
        SoftDeletes,
        // DynamicConnection,
        InteractsWithMedia,
        \OwenIt\Auditing\Auditable,
        BranchScope;

    // 🧩 اجمع كل الـTraits هنا
    use BranchConstants,
        BranchRelations,
        BranchScopes,
        BranchAttributes,
        BranchAggregates,
        BranchBootEvents;

    protected $fillable = [
        // ⚠️ فكّر بإزالة 'id' إن لم تكن تحتاج إدخاله يدويًا
        'id',
        'name',
        'address',
        'manager_id',
        'active',
        'store_id',
        'manager_abel_show_orders',
        'type',
        'start_date',
        'end_date',
        'municipality_license_issue_date',
        'municipality_license_end_date',
        'municipality_license_notes',
        'more_description',
        'is_hidden',
    ];

    protected $auditInclude = [
        'id',
        'name',
        'address',
        'manager_id',
        'active',
        'store_id',
        'manager_abel_show_orders',
        'type',
        'start_date',
        'end_date',
        'municipality_license_issue_date',
        'municipality_license_end_date',
        'municipality_license_notes',
        'more_description',
        'is_hidden',
    ];

    protected $casts = [
        'active'     => 'boolean',
        'is_hidden'  => 'boolean',
        'start_date' => 'date:Y-m-d',
        'end_date'   => 'date:Y-m-d',
        'municipality_license_issue_date' => 'date:Y-m-d',
        'municipality_license_end_date'   => 'date:Y-m-d',

    ];

    protected $appends = [
        'customized_categories',
        'orders_count',
        'reseller_balance',
        'total_paid',
        'total_sales',
        'total_orders_amount',
        'is_kitchen',
        'status_label',
        'is_expired',
        'chef_assistants_names',
        'chef_assistants_emails',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('municipality_license')->singleFile();
    }

    public function toArray(): array
    {
        $data = parent::toArray();

        $data['areas'] = $this->areas->makeHidden(['created_at', 'updated_at']);
        // توافق خلفي (إن كان مستهلك API قديم يعتمد هذا الاسم)
        $data['is_central_kitchen']    = (int) $this->is_kitchen;
        $data['customized_categories'] = $this->customized_categories;
        $data['is_expired'] = $this->is_expired;
        // ✅ فرض الوسم مع الاسم عند كون الفرع منتهيًا
        if ($this->is_expired) {
            // استخدم ترجمة إن أحببت: __('lang.expired')
            $suffix = 'Expired';
            // تجنب التكرار لو تم إضافته سابقًا لأي سبب
            if (! str_ends_with($data['name'], "($suffix)")) {
                $data['name'] = trim($data['name'] . " ($suffix)");
            }
        }
        return $data;
    }
}
