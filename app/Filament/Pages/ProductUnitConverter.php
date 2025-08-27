<?php

namespace App\Filament\Pages;

use App\Models\UnitPrice;
use Filament\Actions\Action;
use Exception;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Models\Unit;
use App\Services\ProductUnitConversionService;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class ProductUnitConverter extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static string | \UnitEnum | null $navigationGroup = 'Inventory Management';
    protected static ?string $title = 'Product Unit Converter';
    protected string $view = 'filament.pages.product-unit-converter';

    // Form properties
    public ?int $selectedProductId = null;
    public ?int $fromUnitId = null;
    public ?int $toUnitId = null;
    public ?float $fromPackageSize = null;
    public ?float $toPackageSize = null;

    // Remove the explicit type declaration here, or make it nullable.
    // We'll initialize it via a getter.
    private ?ProductUnitConversionService $conversionServiceInstance = null; // Changed to private and nullable

    // The mount method can now simply fill the form.
    public function mount(): void
    {
        $this->form->fill();
    }

    // This is the getter method that will ensure the service is initialized.
    protected function getConversionService(): ProductUnitConversionService
    {
        if (is_null($this->conversionServiceInstance)) {
            $this->conversionServiceInstance = app(ProductUnitConversionService::class);
        }
        return $this->conversionServiceInstance;
    }

    protected function getFormModel(): string
    {
        return Product::class;
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('selectedProductId')
                ->label('Product')
                ->options(
                    Product::all()->mapWithKeys(function ($product) {
                        return [$product->id => "{$product->name} - ({$product->code})"];
                    })->toArray()
                )
                ->searchable(['name', 'code'])
                ->required()
                ->helperText('Search for the product by name or code.')
                ->live()
                ->afterStateUpdated(function (callable $set) {
                    $set('fromUnitId', null);
                    $set('toUnitId', null);
                    $set('fromPackageSize', null);
                    $set('toPackageSize', null);
                }),

            Select::make('fromUnitId')
                ->label('From Unit')
                ->options(function (callable $get) {
                    $productId = $get('selectedProductId');
                    if (!$productId) {
                        return [];
                    }
                    return UnitPrice::where('product_id', $productId)
                        ->whereNull('unit_prices.deleted_at')
                        ->join('units', 'unit_prices.unit_id', '=', 'units.id')
                        ->pluck('units.name', 'units.id')
                        ->toArray();
                })
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(function (callable $set) {
                    $set('fromPackageSize', null);
                }),

            TextInput::make('fromPackageSize')
                ->label('From Package Size')
                ->numeric()
                ->step(0.01)
                ->placeholder('e.g., 5 for 5 KG bag')
                ->required(),

            Select::make('toUnitId')
                ->label('To Unit')
                ->options(function (callable $get) {
                    $fromUnitId = $get('fromUnitId');
                    $query = Unit::query();
                    // if ($fromUnitId) {
                    //     $query->where('id', '!=', $fromUnitId);
                    // }
                    return $query->pluck('name', 'id')->toArray();
                })
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(function (callable $set) {
                    $set('toPackageSize', null);
                }),

            TextInput::make('toPackageSize')
                ->label('To Package Size')
                ->numeric()
                ->step(0.01)
                ->placeholder('e.g., 1 for 1 KG')
                ->required(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('convert')
                ->label('Apply Conversion')
                ->submit('submitConversion')
                ->color('primary')
                ->icon('heroicon-o-check-circle'),
        ];
    }

    public function submitConversion(): void
    {
        try {
            $data = $this->form->getState();

            $data['fromPackageSize'] = (float) $data['fromPackageSize'];
            $data['toPackageSize'] = (float) $data['toPackageSize'];

            if ($data['fromUnitId'] === $data['toUnitId'] && $data['fromPackageSize'] === $data['toPackageSize']) {
                throw ValidationException::withMessages([
                    'fromUnitId' => 'Cannot convert to the same unit with the same package size.',
                    'toUnitId' => 'Cannot convert to the same unit with the same package size.',
                ]);
            }

            // Use the getter method to access the service
            $this->getConversionService()->migrateProductUnitAndPackageSize(
                $data['selectedProductId'],
                $data['fromUnitId'],
                $data['toUnitId'],
                $data['fromPackageSize'],
                $data['toPackageSize']
            );

            Notification::make()
                ->title('Conversion Applied Successfully')
                ->body("Product unit for ID '{$data['selectedProductId']}' converted from Unit '{$data['fromUnitId']}' (Pkg: {$data['fromPackageSize']}) to Unit '{$data['toUnitId']}' (Pkg: {$data['toPackageSize']}).")
                ->success()
                ->send();

            $this->form->fill(); // Reset the form

        } catch (ValidationException $e) {
            Notification::make()
                ->title('Validation Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->title('An Error Occurred')
                ->body('Failed to apply conversion: ' . $e->getMessage())
                ->danger()
                ->send();

            Log::error('Product unit conversion error: ' . $e->getMessage(), $data);
        }
    }
}