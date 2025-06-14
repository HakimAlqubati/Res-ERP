<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Services\BulkPricingAdjustmentService;
use Filament\Actions\Action;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Get; // Import for reactive forms
use Livewire\Component as Livewire; // Import for afterStateUpdated $set

class BulkPriceUpdate extends Page implements HasForms
{
    use InteractsWithForms;

    // --- Page Configuration ---
    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationGroup = 'Tools';
    protected static ?string $navigationLabel = 'Bulk Price Update';
    protected static ?string $title = 'Bulk Historical Price Update';

    protected static string $view = 'filament.pages.bulk-price-update';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    /**
     * Define the form schema.
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Update Criteria')->columns(3)->schema([
                    Select::make('category_id')
                        ->label('Select Category (Optional)')
                        ->options(Category::pluck('name', 'id'))
                        ->searchable()
                        ->live() // يجعل هذا الحقل تفاعليًا
                        // ->afterStateUpdated() : هذا هو الجزء الجديد الذي يقوم بالتحديد التلقائي
                        ->afterStateUpdated(function (callable $set, ?string $state) {
                            if (is_null($state)) {
                                // إذا تم إلغاء اختيار الفئة، قم بإفراغ حقل المنتجات
                                $set('product_ids', []);
                                return;
                            }
                            // جلب كل معرفات المنتجات للفئة المحددة
                            $productIds = Product::where('category_id', $state)->pluck('id')->all();
                            // تعيين حقل المنتجات ليحتوي على كل المعرفات التي تم جلبها، مما يؤدي إلى تحديدها
                            $set('product_ids', $productIds);
                        })
                        ->placeholder('Select a category to filter and select products'),

                    Select::make('unit_id')
                        ->label('Select Unit')
                        ->options(Unit::pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->placeholder('Select the unit of measure to update'),

                    TextInput::make('new_price')
                        ->label('New Price')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->prefix('USD')
                        ->helperText('Enter the new price to be applied to all historical records.'),

                    Select::make('product_ids')
                        ->label('Products (Optional)')
                        ->multiple()
                        ->options(function (Get $get) {
                            $categoryId = $get('category_id');
                            if (!$categoryId) {
                                return Product::pluck('name', 'id');
                            }
                            return Product::where('category_id', $categoryId)->pluck('name', 'id');
                        })
                        ->searchable()
                        ->columnSpanFull()
                        ->helperText('If you select specific products, only they will be updated. If left empty, all products in the selected category will be updated.'),
                ])
            ])
            ->statePath('data');
    }

    /**
     * Define the form action button.
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('updatePrices')
                ->label('Start Update Process')
                ->color('danger')
                ->icon('heroicon-o-arrow-path')
                ->submit('updatePrices')
                ->requiresConfirmation()
                ->modalHeading('Confirm Price Update')
                ->modalDescription('Warning: This action will change historical prices and cannot be undone. Are you sure you want to proceed?')
                ->modalSubmitActionLabel('Yes, update now'),
        ];
    }

    /**
     * The method that gets executed when the action button is clicked.
     */
    public function updatePrices(): void
    {
        $formData = $this->form->getState();

        if (empty($formData['category_id']) && empty($formData['product_ids'])) {
            Notification::make()
                ->title('Missing Information')
                ->body('You must select either a category or one or more specific products to update.')
                ->danger()
                ->send();
            return;
        }

        try {
            $service = app(BulkPricingAdjustmentService::class);

            $report = $service->updateAllHistoricalPrices(
                $formData['category_id'] ?? null,
                $formData['product_ids'] ?? null,
                $formData['unit_id'],
                (float)$formData['new_price']
            );

            if (isset($report['message'])) {
                Notification::make()
                    ->title('Operation Note')
                    ->body($report['message'])
                    ->warning()
                    ->send();
                return;
            }

            $reportSummary = collect($report)->map(function ($count, $table) {
                return "<li><strong>{$table}:</strong> Updated {$count} records</li>";
            })->implode('');

            Notification::make()
                ->title('Bulk Update Successful')
                ->body("<ul>{$reportSummary}</ul>")
                ->success()
                ->persistent()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Update Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();

            Log::error('Bulk Price Update Failed: ' . $e->getMessage());
        }
    }
}
