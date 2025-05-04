<?php

namespace App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource\Pages;

use App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource;
use App\Models\GoodsReceivedNote;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceDetail;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditGoodsReceivedNoteV2 extends EditRecord implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static string $resource = GoodsReceivedNoteResource::class;

    public ?array $data = [];

    public function getFormStatePath(): string
    {
        return 'data';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('Approve')
                ->label('Approve')
                ->requiresConfirmation()
                ->action(fn() => $this->approve()),
        ];
    }

    public function mount($record): void
    {
        parent::mount($record);

        $this->form->fill([
            'items' => $this->record->grnDetails->map(function ($detail) {
                return [
                    'product_name' => $detail->product->name,
                    'unit_name' => $detail->unit?->name,
                    'quantity' => $detail->quantity,
                    'price' => $detail->price ?? 0,
                    'total_price' => $detail->quantity * ($detail->price ?? 0),
                    'product_id' => $detail->product_id,
                    'unit_id' => $detail->unit_id,
                    'package_size' => $detail->package_size,
                    'waste_stock_percentage' => $detail->waste_stock_percentage,
                ];
            })->toArray(),
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Card::make([
                Repeater::make('items')
                    ->label('Items for Approval')
                    ->schema([
                        TextInput::make('product_name')->disabled(),
                        TextInput::make('unit_name')->disabled(),
                        TextInput::make('quantity')->numeric()->disabled(),
                        TextInput::make('price')
                            ->numeric()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $set('total_price', ((float) $state) * ((float) $get('quantity')));
                            })
                            ->required(),
                        TextInput::make('total_price')
                            ->numeric()
                            ->disabled(),
                    ])->columns(5)
            ])
        ];
    }

    public function approve(): void
    {
        // DB::transaction(function () {
        $invoice = PurchaseInvoice::create([
            'date' => now(),
            'supplier_id' => $this->record->supplier_id,
            'description' => 'Auto-created from GRN #' . $this->record->grn_number,
            'store_id' => $this->record->store_id,
            'invoice_no' => 'INV-' . now()->timestamp,
        ]);

        foreach ($this->data['items'] as $item) {
            PurchaseInvoiceDetail::create([
                'purchase_invoice_id' => $invoice->id,
                'product_id' => $item['product_id'],
                'unit_id' => $item['unit_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'package_size' => $item['package_size'],
                'waste_stock_percentage' => $item['waste_stock_percentage'],
            ]);
        }

        $this->record->update([
            'status' => GoodsReceivedNote::STATUS_APPROVED,
            'purchase_invoice_id' => $invoice->id,
        ]);
        // });

        Notification::make()
            ->title('GRN Approved and Purchase Invoice Created')
            ->success()
            ->send();

        $this->redirect(GoodsReceivedNoteResource::getUrl());
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
    protected function getFormActions(): array
    {
        return [];
    }
}
