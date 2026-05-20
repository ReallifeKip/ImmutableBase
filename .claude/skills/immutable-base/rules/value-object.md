# ValueObject & SingleValueObject

Value objects represent domain concepts with invariants. Unlike DTOs, they run `validate()` on construction and throw `ValidationChainException` if the object would be invalid.

## Choosing the Right Class

| | `ValueObject` | `SingleValueObject` |
|---|---|---|
| Properties | Multiple | Exactly one (`$value`) |
| Construction | `::fromArray()` | `::from($scalar)` |
| Use for | Multi-field domain concepts (Money, Address, DateRange) | Single scalar with rules (Email, Phone, Percentage) |
| `validate()` | Override in subclass | Override in subclass |

---

## ValueObject (multi-property)

```php
<?php

use ReallifeKip\ImmutableBase\Objects\ValueObject;

readonly class Money extends ValueObject
{
    public int    $amount;    // stored in cents
    public string $currency;

    public function validate(): bool
    {
        return $this->amount >= 0
            && in_array($this->currency, ['TWD', 'USD', 'EUR'], true);
    }
}

// Construction — same as DataTransferObject
$price = Money::fromArray(['amount' => 1000, 'currency' => 'TWD']);

// Invalid → throws ValidationChainException immediately
$price = Money::fromArray(['amount' => -1, 'currency' => 'TWD']);
```

### Validation message with #[Spec]

```php
use ReallifeKip\ImmutableBase\Attributes\Spec;

#[Spec('amount must be non-negative and currency must be TWD, USD, or EUR')]
readonly class Money extends ValueObject
{
    public int    $amount;
    public string $currency;

    public function validate(): bool
    {
        return $this->amount >= 0
            && in_array($this->currency, ['TWD', 'USD', 'EUR'], true);
    }
}
```

### Inheritance — validation chains up the hierarchy

Each class in the chain runs its own `validate()`. All must pass.

```php
readonly class PositiveMoney extends Money
{
    public function validate(): bool
    {
        return $this->amount > 0;   // also runs Money::validate() from parent
    }
}
```

To run child validation first (instead of parent-first), use `#[ValidateFromSelf]`:

```php
use ReallifeKip\ImmutableBase\Attributes\ValidateFromSelf;

#[ValidateFromSelf]
readonly class PositiveMoney extends Money
{
    public function validate(): bool
    {
        return $this->amount > 0;
    }
}
```

---

## SingleValueObject (single scalar)

Wraps exactly one scalar (`string|int|float|bool`) in `$value`. Construction via `::from()`.

```php
<?php

use ReallifeKip\ImmutableBase\Objects\SingleValueObject;

readonly class Email extends SingleValueObject
{
    public string $value;   // always named $value

    public function validate(): bool
    {
        return filter_var($this->value, FILTER_VALIDATE_EMAIL) !== false;
    }
}

$email = Email::from('alice@example.com');  // valid
$email = Email::from('not-an-email');       // throws ValidationChainException

// Scalar ergonomics
echo $email;            // "alice@example.com"  (__toString)
echo $email();          // "alice@example.com"  (__invoke)
json_encode($email);    // "alice@example.com"  (JsonSerializable)
$email->value;          // "alice@example.com"  (direct access)
```

### Spec message on SVO

```php
use ReallifeKip\ImmutableBase\Attributes\Spec;

#[Spec('Must be a valid email address')]
readonly class Email extends SingleValueObject
{
    public string $value;

    public function validate(): bool
    {
        return filter_var($this->value, FILTER_VALIDATE_EMAIL) !== false;
    }
}
```

### SVO as a property of DTO/VO

SVOs inside a DTO/VO accept either the SVO instance or its raw scalar:

```php
readonly class CreateUserDto extends DataTransferObject
{
    public string $name;
    public Email  $email;    // SVO property
}

// Both work:
CreateUserDto::fromArray(['name' => 'Alice', 'email' => Email::from('alice@example.com')]);
CreateUserDto::fromArray(['name' => 'Alice', 'email' => 'alice@example.com']);  // raw scalar
```

---

## Common SVO Examples

```php
readonly class Percentage extends SingleValueObject
{
    public float $value;

    public function validate(): bool
    {
        return $this->value >= 0.0 && $this->value <= 100.0;
    }
}

readonly class PhoneNumber extends SingleValueObject
{
    public string $value;

    public function validate(): bool
    {
        return (bool) preg_match('/^\+?[0-9]{7,15}$/', $this->value);
    }
}

readonly class PositiveInt extends SingleValueObject
{
    public int $value;

    public function validate(): bool
    {
        return $this->value > 0;
    }
}
```

---

## Rules

### validate() must return bool — never throw inside it

```php
// Bad — throwing inside validate() bypasses the ValidationChainException contract
public function validate(): bool
{
    if ($this->amount < 0) {
        throw new \InvalidArgumentException('negative');
    }
    return true;
}

// Good
public function validate(): bool
{
    return $this->amount >= 0;
}
```

### SVO $value must be a scalar type

```php
// Bad — SVO cannot hold complex types
readonly class OrderId extends SingleValueObject
{
    public array $value;   // invalid — will throw at scan time
}

// Good
readonly class OrderId extends SingleValueObject
{
    public int $value;
}
```

### Do not call validate() manually

Construction via `::fromArray()` / `::from()` triggers validation automatically.
