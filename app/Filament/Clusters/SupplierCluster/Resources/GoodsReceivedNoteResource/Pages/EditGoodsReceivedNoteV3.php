<?php

namespace App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource\Pages;

use App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource;
use App\Filament\Resources\PurchaseInvoiceResource;
use App\Models\GoodsReceivedNote;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceDetail;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Unit;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class EditGoodsReceivedNoteV3 extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;
    public array $formData = [];

    public ?GoodsReceivedNote $record;
    protected static string $resource = PurchaseInvoiceResource::class;
    public function getTitle(): string | Htmlable
    {
        return 'Create Supplier Invoice';
    }
    public function mount(): void
    {

        $this->formData = [
            'invoice_no' => null,
            'date' => now()->format('Y-m-d'),
            'store_id' => $this->record->store_id,
            'supplier_id' => $this?->record?->supplier_id,
            'payment_method_id' => $this?->record?->payment_method_id,
            'units' => $this->record->grnDetails->map(function ($detail) {
                return [
                    'product_id' => $detail->product_id,
                    'unit_id' => $detail->unit_id,
                    'quantity' => $detail->quantity,
                    'package_size' => $detail->package_size,
                    'price' => 0,
                    'total_price' => 0,
                ];
            })->toArray(),
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            Fieldset::make()->columns(6)->schema([

                TextInput::make('invoice_no')
                    ->label('Invoice No')->columnSpan(2)
                    ->statePath('formData.invoice_no'),

                DatePicker::make('date')
                    ->label('Date')
                    ->required()
                    ->statePath('formData.date'),

                Select::make('store_id')
                    ->label('Store')
                    ->disabled()->columnSpan(1)
                    ->options(Store::pluck('name', 'id'))
                    ->statePath('formData.store_id'),

                Select::make('supplier_id')->columnSpan(1)
                    ->label('Supplier')->searchable()
                    ->options(Supplier::pluck('name', 'id'))
                    ->statePath('formData.supplier_id'),
                Select::make('payment_method_id')
                    ->label('Payment Method')
                    ->options(PaymentMethod::active()->get()->pluck('name', 'id'))
                    ->searchable()
                    
                    ->statePath('formData.payment_method_id')
            ]),

            Repeater::make('units')
                ->addable(false)
                ->label('')->deletable(false)
                ->columns(7)->minItems(1)
                ->schema([
                    Select::make('product_id')->label('Product')
                        ->options(function () {
                            return $this->record->grnDetails->pluck('product_id')->unique()->mapWithKeys(function ($productId) {
                                $product = Product::find($productId);
                                return [$product->id => "{$product->name}"];
                            });
                        })->disabled()->dehydrated()->columnSpan(2),
                    Select::make('unit_id')->label('Unit')
                        ->options(Unit::active()->get()->pluck('name', 'id'))->disabled()->dehydrated(),
                    TextInput::make('quantity')
                        ->numeric()->disabled(),
                    TextInput::make('package_size')->numeric()->disabled(),
                    TextInput::make('price')->numeric()->label('Price')->required()
                        ->live(onBlur: true)
                        ->minValue(1)
                        ->rule('gte:1')
                        ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {
                            $set('total_price', ((float) $state) * ((float)$get('quantity')));
                        }),
                    TextInput::make('total_price')->numeric()->label('Total')->disabled(),
                ])
                ->defaultItems(1)
                ->statePath('formData.units'), // هذا هو الأهم
        ];
    }


    public function createInvoice(): void
    {
        $data = $this->formData;

        try {
            DB::transaction(function () use ($data) {
                $this->validatePrice($data);
                $invoice = PurchaseInvoice::create([
                    'invoice_no' => $data['invoice_no'],
                    'date' => $data['date'],
                    'store_id' => $data['store_id'],
                    'supplier_id' => $data['supplier_id'],
                    'has_grn' => true,
                    'grn_id' => $this->record->id,
                    'payment_method_id' => $data['payment_method_id']
                ]);

                foreach ($data['units'] as $item) {
                    $item['total_price'] = (float) $item['quantity'] * (float) $item['price'];
                    $invoice->purchaseInvoiceDetails()->create($item);
                }

                $this->record->update([
                    'status' => GoodsReceivedNote::STATUS_APPROVED,
                    'purchase_invoice_id' => $invoice->id,
                    'approved_by' => auth()->id()
                ]);
            });

            Notification::make()
                ->title('Success')
                ->body('Purchase Invoice Created Successfully')
                ->success()
                ->send();
            DB::commit();
            $this->redirect(PurchaseInvoiceResource::getUrl('index'));
        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Error')
                ->body('Failed to create Purchase Invoice: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Custom price validation to ensure price >= 1
     */
    protected function validatePrice(array $data)
    {
        foreach ($data['units'] as $item) {
            if ((float) $item['price'] < 1) {
                throw new \Exception("Price Cannot be Zero");
            }
        }
    }

    protected static string $view = 'filament.pages.edit-goods-received-note-v3';
}
