# Serialization & Mutation

All methods are available on `DataTransferObject`, `ValueObject`, and `SingleValueObject` unless noted.

---

## Construction

### ::fromArray(array $data): static

Hydrates from an associative array. Keys map to property names (or remapped names when `#[InputKeyTo]` is set).

```php
$dto = CreateUserDto::fromArray(['name' => 'Alice', 'email' => 'alice@example.com']);
```

### ::fromJson(string $data): static

Hydrates from a JSON object string. Rejects non-object JSON (arrays like `[1,2,3]`).

```php
$dto = CreateUserDto::fromJson('{"name":"Alice","email":"alice@example.com"}');
```

### ::from($value): static  *(SVO only)*

Single-value construction for `SingleValueObject`.

```php
$email = Email::from('alice@example.com');
```

---

## Serialization

### toArray(KeyCase|bool $keyCase = false): array

Converts to an associative array. Three output key formats:

```php
$dto->toArray();                    // property names as-is
$dto->toArray(true);                // respect #[OutputKeyTo] definitions
$dto->toArray(KeyCase::Snake);      // force all keys to snake_case
$dto->toArray(KeyCase::Camel);      // force all keys to camelCase
```

Null handling:
- Properties with `#[SkipOnNull]` are omitted when `null`
- Properties with `#[KeepOnNull]` always appear, even with class-level `#[SkipOnNull]`

SVOs serialize to their scalar `$value` when nested inside another object's `toArray()`.

### toJson(KeyCase|bool $keyCase = false): string

Delegates to `toArray()` then `json_encode()`. Same `$keyCase` options apply.

```php
$dto->toJson();                     // {"name":"Alice","email":"alice@example.com"}
$dto->toJson(KeyCase::Snake);       // {"first_name":"Alice","last_name":"Smith"}
```

---

## Immutable Mutation

### with(string|array|object $data, string $separator = '.'): static

Returns a **new instance** with the specified properties replaced. The original is unchanged.

```php
$original = CreateUserDto::fromArray(['name' => 'Alice', 'email' => 'alice@example.com']);

$updated = $original->with(['name' => 'Bob']);
// $original->name === 'Alice'  (unchanged)
// $updated->name  === 'Bob'

// From JSON string
$updated = $original->with('{"name":"Bob"}');

// From object
$updated = $original->with((object) ['name' => 'Bob']);
```

### Deep path mutation

Use dot-notation to update nested properties:

```php
readonly class OrderDto extends DataTransferObject
{
    public string     $reference;
    public AddressDto $address;
}

$order = OrderDto::fromArray([
    'reference' => 'ORD-001',
    'address'   => ['street' => '123 Main St', 'city' => 'Taipei'],
]);

$updated = $order->with(['address.city' => 'Kaohsiung']);
// $updated->address->city === 'Kaohsiung'

// Bracket notation for arrays
$updated = $order->with(['items[0].qty' => 3]);

// Custom separator
$updated = $order->with(['address/city' => 'Kaohsiung'], separator: '/');
```

---

## Equality

### equals(static $value): bool

Deep structural equality check. Requires exact class match.

```php
$a = CreateUserDto::fromArray(['name' => 'Alice', 'email' => 'a@b.com']);
$b = CreateUserDto::fromArray(['name' => 'Alice', 'email' => 'a@b.com']);
$c = CreateUserDto::fromArray(['name' => 'Bob',   'email' => 'b@b.com']);

$a->equals($b);  // true  — same class, same values
$a->equals($c);  // false — different name
```

For SVOs, compares the wrapped `$value` directly.

Throws `InvalidCompareTargetException` if comparing different classes.

---

## Global Configuration

```php
// Enable strict mode globally (all classes reject unknown input keys)
ImmutableBase::strict(true);

// Enable debug logging (logs redundant input keys to a file)
ImmutableBase::debug('/path/to/log/dir');
ImmutableBase::debug(null);  // disable

// Pre-load reflection cache (call in bootstrap for production performance)
ImmutableBase::loadCache();
```

---

## SVO-specific Ergonomics

`SingleValueObject` adds scalar-like behaviour on top of the standard methods:

```php
$email = Email::from('alice@example.com');

(string) $email;          // "alice@example.com"  — __toString
$email();                 // "alice@example.com"  — __invoke
json_encode($email);      // "alice@example.com"  — JsonSerializable (not {"value":...})
$email->value;            // "alice@example.com"  — direct property
$email->toArray();        // ['value' => 'alice@example.com']
$email->toJson();         // '"alice@example.com"'
```

---

## Exceptions Reference

### Construction / Mutation (thrown during `::fromArray()`, `::fromJson()`, `::from()`, `->with()`)

| Exception | Thrown when |
|---|---|
| `RequiredValueException` | Non-nullable property is absent or `null` in input |
| `InvalidValueException` | Value type does not match the declared property type |
| `InvalidEnumValueException` | Enum property receives a value matching no case |
| `InvalidJsonException` | `fromJson()` / `with()` receives malformed JSON |
| `ValidationChainException` | `validate()` returns `false` (VO / SVO only) |
| `StrictViolationException` | Input has keys not declared as properties (`#[Strict]` or global strict mode) |
| `InvalidArrayOfItemException` | An element of an `#[ArrayOf]` array cannot be resolved to the declared type |

### Comparison

| Exception | Thrown when |
|---|---|
| `InvalidCompareTargetException` | `equals()` called with a different class |

### Class Definition (thrown on first instantiation when the class is defined incorrectly)

| Exception | Cause |
|---|---|
| `InvalidArrayOfUsageException` | `#[ArrayOf]` placed on a non-`array` property |
| `InvalidArrayOfTargetException` | Invalid target type passed to `#[ArrayOf]` |
| `InvalidPropertyTypeException` | Forbidden property type declared |
| `InvalidVisibilityException` | Non-public property declared |
| `InvalidSpecException` | Empty or invalid `#[Spec]` value |
| `InvalidWithPathException` | Deep path in `with()` targets a non-traversable property |

All exceptions extend `ImmutableBaseException`. RuntimeException subclasses cover construction/mutation; LogicException subclasses cover class definition errors.
