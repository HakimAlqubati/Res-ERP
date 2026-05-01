<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Concerns\HasExtraInputAttributes;

class PhoneInput extends Field
{
    use HasPlaceholder;
    use HasExtraInputAttributes;

    protected string $view = 'filament.forms.components.phone-input';

    protected array $onlyCountries = [];
    protected string $defaultCountry = 'sa';

    // Array to store custom validation rules per country
    protected array $countryValidations = [];

    // Inject the validation rule automatically when the field is set up
    // Inject the validation rule automatically when the field is set up
    protected function setUp(): void
    {
        parent::setUp();

        $this->rule(function () {
            return function (string $attribute, $value, Closure $fail) {
                if (empty($value)) return;

                $validations = $this->getCountryValidations();
                if (empty($validations)) return;

                $matchedCountry = false;

                foreach ($validations as $country => $rules) {
                    // 🌟 هنا السحر: تحويل الشرط إلى مصفوفة ليدعم عدة بدايات
                    $prefixes = (array) ($rules['starts_with'] ?? []);
                    $length = $rules['length'] ?? null;

                    foreach ($prefixes as $prefix) {
                        // التحقق مما إذا كان الرقم يبدأ بأحد المفاتيح المحددة
                        if (str_starts_with($value, $prefix)) {
                            $matchedCountry = true;

                            // التحقق من الطول
                            if ($length) {
                                $lengths = (array) $length;
                                if (!in_array(strlen($value), $lengths)) {
                                    $expected = implode(' or ', $lengths);
                                    $fail("Invalid length. Expected {$expected} characters.");
                                }
                            }
                            break 2; // إيقاف البحث فوراً بمجرد تطابق كود الدولة
                        }
                    }
                }

                // إذا لم يتطابق الرقم مع أي من البدايات المسموح بها
                if (!$matchedCountry) {
                    $fail('Invalid phone number or unsupported telecom operator.');
                }
            };
        });
    }

    // Set allowed countries
    public function onlyCountries(array $countries): static
    {
        $this->onlyCountries = $countries;
        return $this;
    }

    // Set default country
    public function defaultCountry(string $country): static
    {
        $this->defaultCountry = strtolower($country);
        return $this;
    }

    // Set validation rules from the Resource
    public function countryValidations(array $validations): static
    {
        $this->countryValidations = $validations;
        return $this;
    }

    // Getters
    public function getOnlyCountries(): array
    {
        return $this->onlyCountries;
    }

    public function getDefaultCountry(): string
    {
        return $this->defaultCountry;
    }

    public function getCountryValidations(): array
    {
        return $this->countryValidations;
    }
}
