# Money Class - Usage Examples

## Basic Usage

### Creating Money Instances

```php
use App\ValueObjects\Money;

// Method 1: Using currency code
$money = Money::make(100, 'USD');

// Method 2: Using Currency model
$currency = Currency::find(1);
$money = Money::fromCurrency(100, $currency);

// Method 3: Create in base currency
$money = Money::makeInBaseCurrency(1000);
```

## Formatting Methods

### 1. Format with Symbol

```php
$money = Money::make(1500, 'YER');
echo $money->format(); // "﷼1,500.00"

$money = Money::make(100, 'USD');
echo $money->format(); // "$100.00"

$money = Money::make(500, 'SAR');
echo $money->format(0); // "ر.س500"
```

### 2. Format with Currency Code

```php
$money = Money::make(1500, 'YER');
echo $money->formatWithCode(); // "1,500.00 YER"

$money = Money::make(100, 'USD');
echo $money->formatWithCode(4); // "100.0000 USD"
```

### 3. Format with Currency Name

```php
$money = Money::make(100, 'USD');
echo $money->formatWithName(); // "100.00 US Dollar - دولار أمريكي"
```

### 4. Display Format (for UI/Tables)

```php
$money = Money::make(1500, 'YER');
echo $money->display(); // "﷼ 1,500.00"
```

### 5. Get Decimal Value

```php
$money = Money::make(100.5678, 'USD');
echo $money->toDecimal(); // "100.5678"
echo $money->toDecimal(2); // "100.57"
```

## Currency Conversion

### Convert to Another Currency

```php
// Convert 100 USD to YER
$usd = Money::make(100, 'USD');
$yer = $usd->convertTo('YER');
echo $yer->format(); // "﷼53,500.00" (100 * 535)

// Convert 140 SAR to USD
$sar = Money::make(140, 'SAR');
$usd = $sar->convertTo('USD');
echo $usd->format(); // "$36.45" (140 * 140 / 535)
```

### Convert to Base Currency

```php
$usd = Money::make(100, 'USD');
$base = $usd->toBaseCurrency();
echo $base->format(); // "﷼53,500.00"

// Format directly in base currency
echo $usd->formatInBaseCurrency(); // "﷼53,500.00"
```

### Get Exchange Rate

```php
$usd = Money::make(100, 'USD');
$rate = $usd->getExchangeRate('YER'); // 535.0
$rate = $usd->getExchangeRate('SAR'); // 3.821
```

## Arithmetic Operations

### Addition

```php
$money1 = Money::make(100, 'USD');
$money2 = Money::make(50, 'USD');
$total = $money1->add($money2);
echo $total->format(); // "$150.00"

// Error: Cannot add different currencies
$usd = Money::make(100, 'USD');
$yer = Money::make(1000, 'YER');
// $usd->add($yer); // Throws Exception

// Solution: Convert first
$total = $usd->add($yer->convertTo('USD'));
```

### Subtraction

```php
$money1 = Money::make(100, 'USD');
$money2 = Money::make(30, 'USD');
$remaining = $money1->subtract($money2);
echo $remaining->format(); // "$70.00"
```

### Multiplication

```php
$price = Money::make(50, 'USD');
$total = $price->multiply(3); // 3 items
echo $total->format(); // "$150.00"
```

### Division

```php
$total = Money::make(100, 'USD');
$perPerson = $total->divide(4); // 4 people
echo $perPerson->format(); // "$25.00"
```

## Comparison Operations

### Compare

```php
$money1 = Money::make(100, 'USD');
$money2 = Money::make(50, 'USD');

$result = $money1->compare($money2); // Returns 1 (greater)

// Compares in base currency automatically
$usd = Money::make(1, 'USD'); // 535 YER
$yer = Money::make(500, 'YER');
$result = $usd->compare($yer); // Returns 1 (535 > 500)
```

### Equality Check

```php
$money1 = Money::make(100, 'USD');
$money2 = Money::make(100, 'USD');

if ($money1->equals($money2)) {
    echo "Equal!";
}
```

### Greater/Less Than

```php
$price = Money::make(100, 'USD');
$budget = Money::make(150, 'USD');

if ($price->lessThan($budget)) {
    echo "Within budget!";
}

if ($budget->greaterThan($price)) {
    echo "Can afford it!";
}
```

## Getter Methods

```php
$money = Money::make(100, 'USD');

// Get raw amount
$amount = $money->getAmount(); // 100.0

// Get currency object
$currency = $money->getCurrency(); // Currency model

// Get currency code
$code = $money->getCurrencyCode(); // "USD"

// Get symbol
$symbol = $money->getSymbol(); // "$"

// Check if base currency
$isBase = $money->isBaseCurrency(); // false
```

## Practical Examples

### Example 1: E-commerce Product Price

```php
// Product price in USD
$productPrice = Money::make(29.99, 'USD');

// Display to customer in their local currency (YER)
echo "Price: " . $productPrice->convertTo('YER')->display();
// Output: "Price: ﷼ 16,044.65"

// Show in multiple currencies
echo "USD: " . $productPrice->format() . "\n";
echo "YER: " . $productPrice->convertTo('YER')->format() . "\n";
echo "SAR: " . $productPrice->convertTo('SAR')->format();
```

### Example 2: Invoice Total Calculation

```php
// Line items
$item1 = Money::make(100, 'USD');
$item2 = Money::make(50, 'USD');
$item3 = Money::make(75, 'USD');

// Calculate subtotal
$subtotal = $item1->add($item2)->add($item3);
echo "Subtotal: " . $subtotal->format(); // "$225.00"

// Apply tax (15%)
$tax = $subtotal->multiply(0.15);
echo "Tax: " . $tax->format(); // "$33.75"

// Total
$total = $subtotal->add($tax);
echo "Total: " . $total->format(); // "$258.75"

// Show in base currency
echo "Total (YER): " . $total->formatInBaseCurrency(); // "﷼138,431.25"
```

### Example 3: Salary Payment

```php
// Employee salary in YER
$salary = Money::make(200000, 'YER');

// Deductions
$insurance = Money::make(10000, 'YER');
$tax = $salary->multiply(0.05); // 5% tax

// Net salary
$netSalary = $salary->subtract($insurance)->subtract($tax);

echo "Gross Salary: " . $salary->format() . "\n";
echo "Insurance: " . $insurance->format() . "\n";
echo "Tax (5%): " . $tax->format() . "\n";
echo "Net Salary: " . $netSalary->format();
```

### Example 4: Currency Exchange

```php
// Customer has 1000 SAR, wants to know USD equivalent
$sar = Money::make(1000, 'SAR');
$usd = $sar->convertTo('USD');

echo "You have: " . $sar->formatWithCode() . "\n";
echo "Equivalent: " . $usd->formatWithCode() . "\n";
echo "Exchange rate: 1 SAR = " . $sar->getExchangeRate('USD') . " USD";
```

### Example 5: Budget Tracking

```php
$budget = Money::make(5000, 'USD');
$spent = Money::make(3200, 'USD');

$remaining = $budget->subtract($spent);
$percentageUsed = ($spent->getAmount() / $budget->getAmount()) * 100;

echo "Budget: " . $budget->format() . "\n";
echo "Spent: " . $spent->format() . "\n";
echo "Remaining: " . $remaining->format() . "\n";
echo "Used: " . number_format($percentageUsed, 1) . "%";

if ($remaining->lessThan(Money::make(1000, 'USD'))) {
    echo "\n⚠️ Warning: Low budget!";
}
```

## Data Export

### Convert to Array

```php
$money = Money::make(100, 'USD');
$array = $money->toArray();

// Returns:
[
    'amount' => 100.0,
    'currency_code' => 'USD',
    'currency_symbol' => '$',
    'formatted' => '$100.00'
]
```

### Convert to JSON

```php
$money = Money::make(100, 'USD');
$json = $money->toJson();

// Output: {"amount":100,"currency_code":"USD","currency_symbol":"$","formatted":"$100.00"}
```

### String Representation

```php
$money = Money::make(100, 'USD');
echo $money; // "$100.00" (uses __toString())
```

## Best Practices

### ✅ DO:

```php
// Always use Money class for currency amounts
$price = Money::make(100, 'USD');

// Convert before arithmetic operations
$total = $usd->convertTo('YER')->add($yer);

// Use formatting methods for display
echo $price->format();

// Use comparison methods
if ($price->greaterThan($budget)) { }
```

### ❌ DON'T:

```php
// Don't use raw floats
$price = 100.50; // Bad

// Don't add different currencies directly
$usd->add($yer); // Throws exception

// Don't compare with ==
if ($money1 == $money2) { } // Bad
// Use equals() instead
if ($money1->equals($money2)) { } // Good
```

## Integration with Models

```php
// In your Model (e.g., Product.php)
class Product extends Model
{
    public function getPriceAttribute()
    {
        return Money::make($this->attributes['price'], $this->currency_code);
    }

    public function getFormattedPriceAttribute()
    {
        return $this->price->format();
    }
}

// Usage
$product = Product::find(1);
echo $product->formatted_price; // "$29.99"
echo $product->price->convertTo('YER')->format(); // "﷼16,044.65"
```
