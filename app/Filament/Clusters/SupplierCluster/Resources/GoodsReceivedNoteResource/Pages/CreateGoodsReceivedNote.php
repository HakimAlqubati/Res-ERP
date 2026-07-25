<?php

namespace App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource\Pages;

use App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource;
use App\Models\DocumentAnalysisAttempt;
use App\Models\Product;
use App\Models\Unit;
use App\Modules\Stock\PriceValidation\Services\PriceChecker;
use Filament\Actions;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class CreateGoodsReceivedNote extends CreateRecord
{
    protected static string $resource = GoodsReceivedNoteResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public bool $isPriceConfirmed = false;

    protected function beforeCreate(): void
    {
        if ($this->isPriceConfirmed) {
            return;
        }

        $warnings = $this->getPriceWarnings();
        if (! empty($warnings)) {
            $this->mountAction('confirmPriceChange');
            $this->halt();
        }
    }

    public function confirmPriceChangeAction(): Actions\Action
    {
        return Actions\Action::make('confirmPriceChange')
            ->requiresConfirmation()
            ->modalHeading('Price Change Warning')
            ->slideOver(true)
            ->color('warning')
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->modalIcon(Heroicon::ChartBarSquare)
            ->modalWidth(Width::SevenExtraLarge)
            ->modalDescription('Some items have a significant price change compared to the last purchase. Please review them before saving.')
             ->schema([
                Repeater::make('warnings')
                    ->hiddenLabel()
                    ->schema([
                        TextInput::make('product_name')->label('Product')->columnSpan(2),
                        TextInput::make('unit_name')->label('Unit')->columnSpan(1),
                        TextInput::make('old_price')->label('Old Price')->columnSpan(1),
                        TextInput::make('new_price')->label('New Price')->columnSpan(1)->extraInputAttributes(['style' => 'color: red !important; -webkit-text-fill-color: red !important; font-weight: bold;']),
                        TextInput::make('change_percent')->label('Change %')->columnSpan(1)->extraInputAttributes(['style' => 'color: red !important; -webkit-text-fill-color: red !important; font-weight: bold;']),
                    ])
                    ->columns(6)
                    ->disabled()
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->default(function () {
                        $warnings = $this->getPriceWarnings();
                        $data = [];
                        foreach ($warnings as $warning) {
                            $product = Product::find($warning->productId);
                            $unit = Unit::find($warning->unitId);
                            $sign = $warning->changePercent > 0 ? '+' : '';
                            $data[] = [
                                'product_name' => $product?->name ?? 'Unknown',
                                'unit_name' => $unit?->name ?? 'Unknown',
                                'old_price' => number_format($warning->normalizedLastPrice, 2),
                                'new_price' => number_format($warning->normalizedNewPrice, 2),
                                'change_percent' => $sign.$warning->changePercent.'%',
                            ];
                        }

                        return $data;
                    }),
            ])
            ->modalSubmitActionLabel('Confirm & Save')
            ->action(function () {
                $this->isPriceConfirmed = true;
                $this->create(); // Resume creation
            });
    }

    protected function getPriceWarnings(): array
    {
        $details = $this->data['grnDetails'] ?? [];
        if (empty($details)) {
            return [];
        }

        $results = PriceChecker::checkMany(array_values($details));

        return array_filter($results, fn ($result) => $result->requiresWarning());
    }

    protected function afterCreate(): void
    {
        if (isset($this->data['document_analysis_attempt_id']) && $this->data['document_analysis_attempt_id']) {
            DocumentAnalysisAttempt::where('id', $this->data['document_analysis_attempt_id'])->update([
                'documentable_id' => $this->record->id,
                'documentable_type' => get_class($this->record),
            ]);
        }
    }
}
