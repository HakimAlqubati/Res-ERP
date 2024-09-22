<?php

use App\Models\Order;
use App\Models\Store;
use App\Models\SystemSetting;
use App\Models\UnitPrice;
use App\Models\User;
use App\Models\UserType;

function getName()
{
    return 'Eng. Hakeem';
}

/**
 * to format money
 */
function formatMoney($amount, $currency = '$')
{
    return $currency . ' ' . number_format($amount, 2);
}

/**
 * to return current role
 */
function getCurrentRole()
{
    $roleId = 0;
    if (count(auth()->user()?->roles) > 0) {
        $roleId = auth()->user()?->roles[0]?->id;
    }
    return $roleId;
}

/**
 * to check if current user is super admin
 */

function isSuperAdmin()
{
    $currentRole = getCurrentRole();
    if ($currentRole == 1) {
        return true;
    }
    return false;
}
/**
 * to get branch id
 */
function getBranchId()
{
    return auth()->user()?->branch?->id;
}

/**
 * to add filament request select
 */
function __filament_request_select($key, $default = null)
{
    if (request()->isMethod('post')) {
        $qu = request()->all();
        $data = data_get($qu, "serverMemo.data.tableFilters." . $key . ".value");

        if (data_get($qu, "updates.0.payload.name") == "tableFilters.$key.value") {
            $data = data_get($qu, "updates.0.payload.value", $data);
        }

        if (data_get($qu, "updates.0.payload.params.0") == "tableFilters.$key.value") {
            $data = data_get($qu, "updates.0.payload.params.1", $data);
        }

        if (is_array($data)) {
            return $default;
        }

        return $data ?? $default;
    } else {
        $qu = request()->query();
        $data = data_get($qu, "tableFilters." . $key . ".value", $default);
        if (is_array($data)) {
            return $default;
        }
        return $data;
    }
}

function __filament_request_select_multiple($key, $default = null, $multiple = false, $type = null)
{
    if (!empty($type)) {
        $valueType = $type;
        $multiple = true;
    } else {
        $valueType = $multiple ? 'values' : 'value';
    }

    if (request()->isMethod('post')) {
        $qu = request()->all();
        $data = data_get_recursive($qu, "serverMemo.data.tableFilters." . $key . ".$valueType");

        if (data_get_recursive($qu, "updates.0.payload.name") == "tableFilters.$key.$valueType") {
            $data = data_get_recursive($qu, "updates.0.payload.$valueType", $data);
        }

        if (data_get_recursive($qu, "updates.0.payload.params.0") == "tableFilters.$key.$valueType") {
            $data = data_get_recursive($qu, "updates.0.payload.params.1", $data);
        }

        if ($multiple) {
            return is_array($data) ? $data : $default;
        }

        return $data ?? $default;
    } else {
        $qu = request()->query();
        $data = data_get($qu, "tableFilters." . $key . ".$valueType", $default);
        if (is_array($data) && !$multiple) {
            return $default;
        }
        return $data;
    }
}

function data_get_recursive($target, $key, $default = null)
{
    if (is_null($key)) {
        return $target;
    }

    $key = is_array($key) ? $key : explode('.', $key);

    foreach ($key as $i => $segment) {
        unset($key[$i]);

        if (is_null($segment)) {
            return $target;
        }

        if ($segment === '*') {
            if ($target instanceof Collection) {
                $target = $target->all();
            } elseif (!is_array($target)) {
                return value($default);
            }

            $result = [];

            foreach ($target as $item) {
                $value = data_get_recursive($item, $key);

                if (is_array($value)) {
                    $result = array_merge($result, $value);
                } else {
                    $result[] = $value;
                }
            }

            return in_array('*', $key) ? Arr::collapse($result) : $result;
        }

        if (Arr::accessible($target) && Arr::exists($target, $segment)) {
            $target = $target[$segment];
        } elseif (is_object($target) && isset($target->{$segment})) {
            $target = $target->{$segment};
        } else {
            return value($default);
        }
    }

    return $target;
}

/**
 * to add filament request date filter
 */
function __filament_request_key($key, $default = null)
{
    if (request()->isMethod('post')) {
        $qu = request()->all();
        $data = data_get($qu, "serverMemo.data.tableFilters." . $key);

        if (data_get($qu, "updates.0.payload.params.0") == "tableFilters.$key") {
            $data = data_get($qu, "updates.0.payload.params.1", $data);
        }

        if (is_array($data)) {
            return $default;
        }

        return $data ?? $default;
    } else {
        $qu = request()->query();
        $data = data_get($qu, "tableFilters." . $key, $default);

        if (is_array($data)) {
            return $default;
        }
        return $data;
    }
}

/**
 * get admins to notify [Super admin - Manager] roles
 */

function getAdminsToNotify()
{
    $adminIds = [];
    $adminIds = User::whereHas("roles", function ($q) {
        $q->whereIn("id", [1, 3]);
    })->select('id', 'name')->get()->pluck('id')->toArray();
    $recipients = User::whereIn('id', $adminIds)->get(['id', 'name']);
    return $recipients;
}

/**
 * get default store
 */
function getDefaultStore()
{
    $defaultStoreId = Store::where('default_store', 1)->where('active', 1)->select('id')->first()?->id;
    if (is_null($defaultStoreId)) {
        $defaultStoreId = 0;
    }
    return $defaultStoreId;
}

/**
 * to get default currency
 */
function getDefaultCurrency()
{
    $defaultCurrency = 'RM';
    $systemSettingsCurrency = SystemSetting::select('currency_symbol')->first();
    if ($systemSettingsCurrency) {
        $defaultCurrency = $systemSettingsCurrency->currency_symbol;
    }
    return $defaultCurrency;
}

/**
 * to get method of calculating prices of orders
 */
function getCalculatingPriceOfOrdersMethod()
{
    $defaultMethod = 'from_unit_prices';

    $systemSettingsCalculatingMethod = SystemSetting::select('calculating_orders_price_method')->first()->calculating_orders_price_method;

    if ($systemSettingsCalculatingMethod != null && ($systemSettingsCalculatingMethod != $defaultMethod)) {
        $defaultMethod = $systemSettingsCalculatingMethod;
    }
    return $defaultMethod;
}

/**
 * get price from unit price by product_id & unit_id
 */
function getUnitPrice($product_id, $unit_id)
{
    return UnitPrice::where(
        'product_id',
        $product_id
    )->where('unit_id', $unit_id)?->first()?->price;
}

/**
 * function to check if user has pending approval order when submit order
 */
function checkIfUserHasPendingForApprovalOrder($branchId)
{
    $order = Order::where('status', Order::PENDING_APPROVAL)
        ->where('branch_id', $branchId)
        ->where('active', 1)
        ->first();

    return $order ? $order->id : null;
}

/**
 * function to return no last days to return orders in mobile
 */
function getLimitDaysOrders()
{
    $limitDays = SystemSetting::select('limit_days_orders')?->first()?->limit_days_orders;
    if ($limitDays) {
        return $limitDays;
    }
    return 30; // 30 days as default
}

/**
 * function to return default user orders status
 */
function getEnableUserOrdersToStore()
{
    return SystemSetting::select('enable_user_orders_to_store')?->first()?->enable_user_orders_to_store;
}

/**
 * to return days as static with array
 */
function getDays()
{
    return [
        'Monday' => 'Monday',
        'Tuesday' => 'Tuesday',
        'Wednesday' => 'Wednesday',
        'Thursday' => 'Thursday',
        'Friday' => 'Friday',
        'Saturday' => 'Saturday',
        'Sunday' => 'Sunday',
    ];
}

/**
 * to get user types as pluck(name,id)
 */
function getUserTypes(){
    return UserType::select('name','id')->get()->pluck('name','id');
}
/**
 * to get roles based on user_type_id
 */
function getRolesByTypeId($id)
{
    $user_types = UserType::find($id)?->role_ids;
    return $user_types;

}
