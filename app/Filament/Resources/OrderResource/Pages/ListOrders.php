<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Imports\OrdersImport;
use App\Models\Order;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return __('lang.orders');
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('importOrders')
                ->label('Import Orders')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('file')
                        ->label('Upload Excel File')
                        ->required()
                        ->disk('public')
                        ->directory('order_imports')

                    // ->rules(['.xls', '.xlsx'])
                ])
                ->color('success')
                ->action(function (array $data) {
                    $filePath = 'public/' . $data['file'];
                    $import = new OrdersImport();

                    try {
                        \Maatwebsite\Excel\Facades\Excel::import($import, $filePath);


                        $count = $import->getSuccessfulImportsCount();
                        if ($count > 0) {
                            showSuccessNotifiMessage("✅ Imported {$count} orders successfully.");
                        } else {
                            showWarningNotifiMessage("⚠️ No orders were added. Please check your file format.");
                        }
                    } catch (\Throwable $e) {
                        showWarningNotifiMessage('❌ Failed to import orders: ' . $e->getMessage());
                    }
                })->hidden()
        ];
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 30, 50];
    }


    /**
     * Define tabs for all delivery statuses.
     */
    public function getTabs(): array
    {
        $statuses = Order::getStatusLabels();

        // "All Orders" tab
        $tabs = [
            'all' => Tab::make(__('All Orders'))
                ->modifyQueryUsing(fn(Builder $query) => $query) // No filtering
                ->icon('heroicon-o-circle-stack')
                ->badge(Order::query()->count())
                ->badgeColor('gray'),
        ];

        // Add status-based tabs
        foreach ($statuses as $status => $label) {
            $tabs[$status] = Tab::make($label)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', $status))
                ->icon(Order::getStatusIcon($status))
                ->badge(Order::query()->where('status', $status)->count())
                ->badgeColor(Order::getBadgeColor($status));
        }

        return $tabs;
    }
}
