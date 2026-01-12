<?php

namespace App\ValueObjects;

use App\Models\Currency;
use InvalidArgumentException;

/**
 * Money Value Object
 * 
 * Professional money/currency handler for amounts and conversions
 * 
 * Usage Examples:
 * - Money::make(100, 'USD')->format() => "$100.00"
 * - Money::make(100, 'SAR')->formatWithCode() => "100.00 SAR"
 * - Money::make(100, 'USD')->convertTo('YER')->format() => "﷼53,500.00"
 */
class Money
{
    private float $amount;
    private Currency $currency;

    /**
     * Create a new Money instance
     */
    public function __construct(float $amount, Currency $currency)
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }

        $this->amount = $amount;
        $this->currency = $currency;
    }

    /**
     * Static factory method - Create Money from amount and currency code
     * 
     * @param float $amount
     * @param string $currencyCode (e.g., 'USD', 'SAR', 'YER')
     * @return static
     */
    public static function make(float $amount, string $currencyCode): static
    {
        $currency = Currency::where('currency_code', $currencyCode)->first();

        if (!$currency) {
            throw new InvalidArgumentException("Currency {$currencyCode} not found");
        }

        return new static($amount, $currency);
    }

    /**
     * Create Money from amount and Currency model
     */
    public static function fromCurrency(float $amount, Currency $currency): static
    {
        return new static($amount, $currency);
    }

    /**
     * Create Money in the base currency
     */
    public static function makeInBaseCurrency(float $amount): static
    {
        $baseCurrency = Currency::where('is_base', true)->first();

        if (!$baseCurrency) {
            throw new InvalidArgumentException('No base currency defined in the system');
        }

        return new static($amount, $baseCurrency);
    }

    /**
     * Format money with currency symbol
     * 
     * @param int $decimals Number of decimal places (default: 2)
     * @return string Example: "$100.00" or "﷼1,500.00"
     */
    public function format(int $decimals = 2): string
    {
        $formatted = number_format($this->amount, $decimals);
        return $this->currency->symbol .' ' . $formatted;
    }

    /**
     * Format money with currency code
     * 
     * @param int $decimals Number of decimal places (default: 2)
     * @return string Example: "100.00 USD" or "1,500.00 YER"
     */
    public function formatWithCode(int $decimals = 2): string
    {
        $formatted = number_format($this->amount, $decimals);
        return $formatted . ' ' . $this->currency->currency_code;
    }

    /**
     * Format money with full currency name
     * 
     * @param int $decimals Number of decimal places (default: 2)
     * @return string Example: "100.00 US Dollar" or "1,500.00 Yemeni Rial"
     */
    public function formatWithName(int $decimals = 2): string
    {
        $formatted = number_format($this->amount, $decimals);
        return $formatted . ' ' . $this->currency->currency_name;
    }

    /**
     * Convert to base currency and format
     * 
     * @param int $decimals Number of decimal places (default: 2)
     * @return string Formatted amount in base currency
     */
    public function formatInBaseCurrency(int $decimals = 2): string
    {
        return $this->toBaseCurrency()->format($decimals);
    }

    /**
     * Format for display in tables/UI
     * Uses symbol if available, otherwise uses code
     * 
     * @param int $decimals Number of decimal places (default: 2)
     * @return string
     */
    public function display(int $decimals = 2): string
    {
        $formatted = number_format($this->amount, $decimals);
        $symbol = $this->currency->symbol ?: $this->currency->currency_code;
        return $symbol . ' ' . $formatted;
    }

    /**
     * Get raw decimal amount without formatting
     * 
     * @param int $decimals Number of decimal places (default: 4)
     * @return string
     */
    public function toDecimal(int $decimals = 4): string
    {
        return number_format($this->amount, $decimals, '.', '');
    }

    /**
     * Convert to another currency
     * 
     * @param string $targetCurrencyCode Target currency code
     * @return static New Money instance in target currency
     */
    public function convertTo(string $targetCurrencyCode): static
    {
        $targetCurrency = Currency::where('currency_code', $targetCurrencyCode)->first();

        if (!$targetCurrency) {
            throw new InvalidArgumentException("Target currency {$targetCurrencyCode} not found");
        }

        // First convert to base currency
        $amountInBase = $this->amount / $this->currency->exchange_rate;

        // Then convert from base to target currency
        $convertedAmount = $amountInBase * $targetCurrency->exchange_rate;

        return new static($convertedAmount, $targetCurrency);
    }

    /**
     * Convert to base currency
     * 
     * @return static New Money instance in base currency
     */
    public function toBaseCurrency(): static
    {
        $baseCurrency = Currency::where('is_base', true)->first();

        if (!$baseCurrency) {
            throw new InvalidArgumentException('No base currency defined');
        }

        // If already in base currency, return clone
        if ($this->currency->is_base) {
            return new static($this->amount, $this->currency);
        }

        // Convert to base
        $amountInBase = $this->amount / $this->currency->exchange_rate;

        return new static($amountInBase, $baseCurrency);
    }

    /**
     * Get exchange rate between current currency and target currency
     * 
     * @param string $targetCurrencyCode
     * @return float
     */
    public function getExchangeRate(string $targetCurrencyCode): float
    {
        $targetCurrency = Currency::where('currency_code', $targetCurrencyCode)->first();

        if (!$targetCurrency) {
            throw new InvalidArgumentException("Currency {$targetCurrencyCode} not found");
        }

        // Calculate cross rate via base currency
        return $targetCurrency->exchange_rate / $this->currency->exchange_rate;
    }

    /**
     * Get raw amount
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Get currency object
     */
    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * Get currency code
     */
    public function getCurrencyCode(): string
    {
        return $this->currency->currency_code;
    }

    /**
     * Get currency symbol
     */
    public function getSymbol(): string
    {
        return $this->currency->symbol;
    }

    /**
     * Check if this money is in the base currency
     */
    public function isBaseCurrency(): bool
    {
        return $this->currency->is_base;
    }

    /**
     * Add another Money amount (must be same currency)
     */
    public function add(Money $other): static
    {
        if ($this->currency->id !== $other->currency->id) {
            throw new InvalidArgumentException('Cannot add money in different currencies. Convert first.');
        }

        return new static($this->amount + $other->amount, $this->currency);
    }

    /**
     * Subtract another Money amount (must be same currency)
     */
    public function subtract(Money $other): static
    {
        if ($this->currency->id !== $other->currency->id) {
            throw new InvalidArgumentException('Cannot subtract money in different currencies. Convert first.');
        }

        $result = $this->amount - $other->amount;

        if ($result < 0) {
            throw new InvalidArgumentException('Result cannot be negative');
        }

        return new static($result, $this->currency);
    }

    /**
     * Multiply by a factor
     */
    public function multiply(float $factor): static
    {
        return new static($this->amount * $factor, $this->currency);
    }

    /**
     * Divide by a divisor
     */
    public function divide(float $divisor): static
    {
        if ($divisor == 0) {
            throw new InvalidArgumentException('Cannot divide by zero');
        }

        return new static($this->amount / $divisor, $this->currency);
    }

    /**
     * Compare with another Money amount
     * Returns: -1 if less, 0 if equal, 1 if greater
     */
    public function compare(Money $other): int
    {
        // Convert both to base currency for fair comparison
        $thisInBase = $this->toBaseCurrency()->getAmount();
        $otherInBase = $other->toBaseCurrency()->getAmount();

        if ($thisInBase < $otherInBase) {
            return -1;
        } elseif ($thisInBase > $otherInBase) {
            return 1;
        }

        return 0;
    }

    /**
     * Check if equal to another Money amount
     */
    public function equals(Money $other): bool
    {
        return $this->compare($other) === 0;
    }

    /**
     * Check if greater than another Money amount
     */
    public function greaterThan(Money $other): bool
    {
        return $this->compare($other) === 1;
    }

    /**
     * Check if less than another Money amount
     */
    public function lessThan(Money $other): bool
    {
        return $this->compare($other) === -1;
    }

    /**
     * Convert to string (default format)
     */
    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency_code' => $this->currency->currency_code,
            'currency_symbol' => $this->currency->symbol,
            'formatted' => $this->format(),
        ];
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
