<?php
namespace App\Filament\Resources\Base;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use App\Models\Order;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use App\Services\MultiProductsInventoryService;
use Exception;
use Throwable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ReturnedOrderResource\Pages\ListReturnedOrders;
use App\Filament\Resources\ReturnedOrderResource\Pages\CreateReturnedOrder;
use App\Filament\Resources\ReturnedOrderResource\Pages\EditReturnedOrder;
use App\Filament\Resources\ReturnedOrderResource\Pages\ViewReturnedOrder;  
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ReturnedOrder;
use App\Models\Store;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class BaseReturnedOrderResource extends Resource
{
  
}