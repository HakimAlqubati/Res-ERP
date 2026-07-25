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
use App\Filament\Traits\HasPriceChangeWarning;

class CreateGoodsReceivedNote extends CreateRecord
{
    use HasPriceChangeWarning;

    protected static string $resource = GoodsReceivedNoteResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeCreate(): void
    {
        $this->checkPriceChanges();
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
