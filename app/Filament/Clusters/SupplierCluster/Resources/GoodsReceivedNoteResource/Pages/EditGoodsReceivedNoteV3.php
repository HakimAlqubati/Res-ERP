<?php

namespace App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource\Pages;

use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Set;
use Exception;
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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class EditGoodsReceivedNoteV3 extends Page implements HasForms
{
    use InteractsWithForms;
    public array $formData = [];

    public ?GoodsReceivedNote $record;
    protected static string $resource = PurchaseInvoiceResource::class;
    public function getTitle(): string | Htmlable
    {
        return 'Review and Approve';
    }
    public function mount(): void
    {

        $this->formData = [
            'invoice_no' => null,
            'date' => $this->record->grn_date ? $this->record->grn_date->format('Y-m-d') :  now()->format('Y-m-d'),
            'store_id' => $this->record->store_id,
            'supplier_id' => $this?->record?->supplier_id,
            'payment_method_id' => $this?->record?->payment_method_id,
            'units' => $this->record->grnDetails->map(function ($detail) {
                return [
                    'detail_id' => $detail->id,
                    'product_id' => $detail->product_id,
                    'unit_id' => $detail->unit_id,
                    'quantity' => $detail->quantity,
                    'package_size' => $detail->package_size,
                    'price' => $detail->price ?? 0,
                    'total_price' => ($detail->quantity ?? 0) * ($detail->price ?? 0),
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
                    // ->required()
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

                    ->statePath('formData.payment_method_id'),
                Textarea::make('description')->label(__('lang.notes'))
                    ->placeholder('Enter notes')
                    ->columnSpanFull()
                    ->required()
                    ->default("purchase invoice from GRN {$this->record->grn_number}")
                    ->statePath('formData.description')
            ]),

            Repeater::make('units')
                ->addable(false)
                ->label('')->deletable(false)
                ->columns(7)->minItems(1)
                ->schema([
                    Forms\Components\Hidden::make('detail_id'),
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
                    TextInput::make('price')->numeric()->label('Unit Price')->required()
                        ->live(onBlur: true)
                        ->minValue(1)
                        ->rule('gte:1')
                        ->afterStateUpdated(function (Set $set, $state, $get) {
                            $set('total_price', ((float) $state) * ((float)$get('quantity')));
                        }),
                    TextInput::make('total_price')->numeric()->label('Total')->disabled(),
                ])
                ->defaultItems(1)
                ->statePath('formData.units'), // هذا هو الأهم
        ];
    }


    public function approve(): void
    {
        $data = $this->formData;

        try {
            DB::transaction(function () use ($data) {
                $this->validatePrice($data);
                if (!empty($data['invoice_no'])) {
                    $this->validateInvoiceNo($data['invoice_no']);
                }
                
                // Update GRN detail prices based on form input
                foreach ($data['units'] as $item) {
                    if (isset($item['detail_id'])) {
                        \App\Models\GoodsReceivedNoteDetail::where('id', $item['detail_id'])
                            ->update(['price' => $item['price']]);
                    }
                }

                $invoice = PurchaseInvoice::create([
                    'invoice_no' => $data['invoice_no'],
                    'date' => $data['date'],
                    'store_id' => $data['store_id'],
                    'supplier_id' => $data['supplier_id'],
                    'description' => $data['description'] ?? "purchase invoice from GRN {$this->record->grn_number}",
                    'has_grn' => true,
                    'grn_id' => $this->record->id,
                    'payment_method_id' => $data['payment_method_id']
                ]);
                $this->record->update([
                    'status' => GoodsReceivedNote::STATUS_APPROVED,
                    'is_purchase_invoice_created' => true,
                    'purchase_invoice_id' => $invoice->id,
                    'approved_by' => auth()->id(),
                    'approve_date' => now(),
                    'notes' => $data['description'],
                ]);

                foreach ($data['units'] as $item) {
                    $item['total_price'] = (float) $item['quantity'] * (float) $item['price'];
                    $invoice->purchaseInvoiceDetails()->create($item);
                }
            });

            Notification::make()
                ->title('Success')
                ->body('Goods Received Note Approved and Purchase Invoice Created Successfully')
                ->success()
                ->send();
            DB::commit();
            $this->redirect(GoodsReceivedNoteResource::getUrl('index'));
        } catch (Exception $e) {
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
            if ((float) $item['price'] <= 0) {
                throw new Exception("Price Cannot be Zero");
            }
        }
    }
    protected function validateInvoiceNo(string $invoiceNo)
    {
        if (PurchaseInvoice::where('invoice_no', $invoiceNo)->exists()) {
            throw new Exception("Invoice number already exists!");
        }
    }

    public function rejectAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('reject')
            ->label('Reject')
            ->color('danger')
            ->requiresConfirmation()
            ->form([
                \Filament\Forms\Components\Textarea::make('rejected_reason')
                    ->label('Reject Reason')
                    ->required(),
            ])
            ->action(function (array $data) {
                try {
                    DB::transaction(function () use ($data) {
                        $this->record->update([
                            'status' => GoodsReceivedNote::STATUS_REJECTED,
                            'rejected_reason' => $data['rejected_reason'],
                            'rejected_date' => now(),
                        ]);
                    });

                    Notification::make()
                        ->title('Success')
                        ->body('Goods Received Note Rejected Successfully')
                        ->success()
                        ->send();
                    $this->redirect(GoodsReceivedNoteResource::getUrl('index'));
                } catch (Exception $e) {
                    Notification::make()
                        ->title('Error')
                        ->body('Failed to reject GRN: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected string $view = 'filament.pages.edit-goods-received-note-v3';
}
