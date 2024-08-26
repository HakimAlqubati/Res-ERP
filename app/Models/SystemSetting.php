<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $table = 'system_settings';

    protected $fillable = [
        'website_name',
        'currency_symbol',
        'calculating_orders_price_method',
        'completed_order_if_not_qty',
        'limit_days_orders',
        'enable_user_orders_to_store',
    ];
}
