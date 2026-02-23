# ImmutableBase

> 🌐 Available in other languages: [繁體中文](./README_TW.md)

![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square)
![PHP Version Support](https://img.shields.io/packagist/php-v/reallifekip/immutable-base.svg?style=flat-square)
![Packagist Version](https://img.shields.io/packagist/v/reallifekip/immutable-base.svg?style=flat-square)

[![FOSSA Status](https://app.fossa.com/api/projects/custom%2B57865%2Fgithub.com%2FReallifeKip%2FImmutableBase.svg?type=small)](https://app.fossa.com/projects/custom%2B57865%2Fgithub.com%2FReallifeKip%2FImmutableBase?ref=badge_small)
![Coverage](https://img.shields.io/codecov/c/github/ReallifeKip/ImmutableBase?style=flat-square&logo=codecov&color=289e6d)

[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=ReallifeKip_ImmutableBase&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=ReallifeKip_ImmutableBase)
[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=ReallifeKip_ImmutableBase&metric=bugs)](https://sonarcloud.io/summary/new_code?id=ReallifeKip_ImmutableBase)
[![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=ReallifeKip_ImmutableBase&metric=code_smells)](https://sonarcloud.io/summary/new_code?id=ReallifeKip_ImmutableBase)
[![Duplicated Lines (%)](https://sonarcloud.io/api/project_badges/measure?project=ReallifeKip_ImmutableBase&metric=duplicated_lines_density)](https://sonarcloud.io/summary/new_code?id=ReallifeKip_ImmutableBase)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=ReallifeKip_ImmutableBase&metric=reliability_rating)](https://sonarcloud.io/summary/new_code?id=ReallifeKip_ImmutableBase)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=ReallifeKip_ImmutableBase&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=ReallifeKip_ImmutableBase)
[![Technical Debt](https://sonarcloud.io/api/project_badges/measure?project=ReallifeKip_ImmutableBase&metric=sqale_index)](https://sonarcloud.io/summary/new_code?id=ReallifeKip_ImmutableBase)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=ReallifeKip_ImmutableBase&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=ReallifeKip_ImmutableBase)
[![Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=ReallifeKip_ImmutableBase&metric=vulnerabilities)](https://sonarcloud.io/summary/new_code?id=ReallifeKip_ImmutableBase)

![CI](https://img.shields.io/github/actions/workflow/status/ReallifeKip/ImmutableBase/ci.yml?style=flat-square&logo=github&color=289e6d&label=CI)
![Downloads](https://img.shields.io/packagist/dt/reallifekip/immutable-base.svg?style=flat-square&color=289e6d&label=📦%20downloads&logoColor=white)

A PHP library for building **immutable data objects** with strict type validation, designed for **DTOs (Data Transfer Objects)**, **VOs (Value Objects)**, and **SVOs (Single Value Objects)**.

Focuses on **immutability**, **type safety**, and **deep structural operations** - including nested construction, dot, path mutation, and recursive equality comparison.

---

## Why ImmutableBase?

### 🚀 Efficient Automatic Construction
```php
// 🥳 ImmutableBase requires no boilerplate constructors. Pass an array or JSON to construct, with no ordering constraints on input keys.
readonly class Order extends DataTransferObject
{
    public string $date;
    public string $time;
}
Order::fromArray($data); // $data can be an array or JSON

// 🫤 The conventional approach requires writing constructors manually, cannot directly accept external array or JSON data for construction.
class Order extends DataTransferObject
{
    public function __construct(
        public readonly string $date,
        public readonly string $time
    ){}
}
new Order('2026-01-01', '00:00:00', ...); // Cannot directly accept external array or JSON data, and risks argument misordering if parameter names are not explicitly specified
```

### 🔧 Flexible Deep Path Updates
Update deeply nested properties by path - no Russian nesting dolls.
```php
// 🥳 ImmutableBase is flexible and precise.
$order->with(['items.0.count' => 1]); // Target a specific array index and update count directly

// 🫤 The conventional approach is verbose and cannot preserve other elements in the original array.
$order->with([
    'items' => [
        [
            // ...
            'count' => 1
            // ...
        ]
    ]
])
```

### 🔎 Intuitive Error Tracing
```php
// 🥳 ImmutableBase pinpoints the exact error location.
SomeException: Order > $profile > 0 > $count > {error message}

// 🫤 The conventional approach only provides vague or hard-to-trace messages.
SomeException: {error message}
```

### ⚡ Lightning-Fast Startup

🥳 ImmutableBase can scan and generate a metadata cache file `cache.php` via `php cacher`, maximizing startup performance.
🫤 The conventional approach may lack any caching mechanism, paying the cost of reflection on every request.

### 🔗 Automatic and Controllable Validation Chain

🥳 ImmutableBase's `ValueObject` and `SingleValueObject` support an optional `validate(): bool` method. During construction, the entire inheritance chain is automatically traversed top-down for validation. Apply `#[ValidateFromSelf]` to reverse the direction.
🫤 The conventional approach rarely offers an automatic validation chain - validation logic must be manually wired in constructors.

### 📃 Documentation as Code, Code as Documentation

🥳 ImmutableBase can scan all subclasses in your project via `php writer`, generating Mermaid class diagrams and Markdown property tables to keep documentation in sync with code.
🫤 The conventional approach cannot guarantee consistency between code and documentation.

### 🆓 Highly Compatible, Lightweight, Zero Dependencies

🥳 ImmutableBase requires **no additional dependencies and is not tied to any framework** when used without documentation generation, caching, or testing.
🫤 The conventional approach, when coupled to a specific package or framework, is difficult to decouple quickly.

### 📦 Controllable Data Output

```php
// 🥳 ImmutableBase uses `#[KeepOnNull]` and `#[SkipOnNull]` to precisely control whether null properties appear in output - no manual filtering needed.
#[SkipOnNull]
readonly class User extends ValueObject
{
    #[KeepOnNull]
    public ?string $name;
    public ?int $age;
}
User::fromArray([])->toArray(); // ["name" => null]

// 🫤 The conventional approach typically requires manually filtering out null values.
readonly class User extends ValueObject
{
    public ?string $name;
    public ?int $age;
}

$user = new User();
$data = get_object_vars($user);
$data['name'] ??= null;
```
### ⭐ TypeScript-Like Type Narrowing

```php
// 🥳 ImmutableBase constrains SingleValueObject to declare $value, but allows flexible type definitions. (Achieved via interface + hooked property with zero reflection overhead)
readonly class ValidAge extends SingleValueObject
{
    public int $value; // Semantically correct type matching the object's purpose
}

// 🫤 The conventional approach locks the type in the parent class with no way to customize it. Parents typically declare mixed or overly broad union types, making SVO design difficult.
class ValidAge extends SingleValueObject
{
    public string $value; // Type locked by parent - cannot be changed, semantically mismatched
}
```

---

## Installation

```bash
composer require reallifekip/immutable-base
```

Requires PHP 8.4+.

---

## Quick Example

```php
use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use ReallifeKip\ImmutableBase\Objects\ValueObject;
use ReallifeKip\ImmutableBase\Objects\SingleValueObject;

readonly class ValidAge extends SingleValueObject
{
    public int $value;

    public function validate(): bool
    {
        return $this->value >= 18;
    }
}

readonly class User extends ValueObject
{
    public string $name;
    public ValidAge $age;

    public function validate(): bool
    {
        return mb_strlen($this->name) >= 2;
    }
}

readonly class SignUpUsersDTO extends DataTransferObject
{
    #[ArrayOf(User::class)]
    public array $users;
    public int $userCount;
}

$signUp = SignUpUsersDTO::fromArray([
    'users' => [
        ['name' => 'ReallifeKip', 'age' => 18],           // array
        '{"name": "Bob", "age": 19}',                     // JSON string
        User::fromArray(['name' => 'Carl', 'age' => 20]), // instance via fromArray
        User::fromJson('{"name": "Dave", "age": 21}'),    // instance via fromJson
    ],
    'userCount' => 4,
]);
```

---

## Testing

```bash
# Unit tests
vendor/bin/phpunit tests

# Benchmarks
vendor/bin/phpbench run
```

---

## Object Types

### DataTransferObject (DTO)

A pure data structure for transport and interchange. Even if a `validate(): bool` method is defined, it will not be invoked during construction.

```php
use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class SignUpUsersDTO extends DataTransferObject
{
    #[ArrayOf(User::class)]
    public array $users;
    public int $userCount;
}
```

### ValueObject (VO)

A semantically meaningful data structure that supports automatic validation during construction via a `validate(): bool` method.

```php
use ReallifeKip\ImmutableBase\Objects\ValueObject;

readonly class User extends ValueObject
{
    public string $name;
    public ValidAge $age;

    public function validate(): bool
    {
        return mb_strlen($this->name) >= 2;
    }
}
```

### SingleValueObject (SVO)

A semantically meaningful single value that supports automatic validation during construction via a `validate(): bool` method. The methods `validate()`, `from()`, `jsonSerialize()`, `__toString()`, and `__invoke()` all operate exclusively on the `$value` property.

```php
use ReallifeKip\ImmutableBase\Objects\SingleValueObject;

readonly class ValidAge extends SingleValueObject
{
    public int $value;

    public function validate(): bool
    {
        return $this->value >= 18;
    }
}
```

```php
$age = ValidAge::from(18);

echo $age;          // 18 (via __toString, only available when $value is a string)
echo $age();        // 18 (via __invoke)
echo $age->value;   // 18
```

---

## API

### Construction - `fromArray()`, `fromJson()`

Input keys that do not match declared properties are silently ignored (unless [strict mode](#strict---strict-mode) is enabled).

```php
$user = User::fromArray(['name' => 'Kip', 'age' => 18]);
$user = User::fromJson('{"name": "Kip", "age": 18}');
```

### Construction - `from()` (SVO only)

```php
$age = ValidAge::from(18);
```

### Serialization - `toArray()`, `toJson()`

```php
$user->toArray();  // ['name' => 'ReallifeKip', 'age' => 18]
$user->toJson();   // {"name":"ReallifeKip","age":18}
```

### Mutation - `with()`

Updates specified properties and returns a **new instance**. The original object is never modified. Accepts an array, object, or JSON string.

```php
$newUser = $user->with(['name' => 'Kip']);
$newUser = $user->with('{"name": "Kip"}');
$newUser = $user->with((object) ['name' => 'Kip']);
```

**Deep path syntax** - update nested properties via dot notation, bracket notation, or a custom separator:

```php
// Dot notation
$newSignUp = $signUp->with(['users.0.name' => 'Kip']);
// Bracket notation
$newSignUp = $signUp->with(['users[0].name' => 'Kip']);
// Custom separator
$newSignUp = $signUp->with(['users/0/name' => 'Kip'], '/');
```

**SVO with()** - replaces the wrapped value directly:

```php
$newAge = $age->with(20);
```

### Comparison - `equals()`

Deep structural equality comparison. Works on all ImmutableBase subclasses. The comparison target must match in data, structure, and class. Nested ImmutableBase objects and arrays are compared recursively.

```php
$a = User::fromArray(['name' => 'Kip', 'age' => 18]);
$b = User::fromArray(['name' => 'Kip', 'age' => 18]);
$c = User::fromArray(['name' => 'Kip', 'age' => 20]);

$a->equals($b);  // true - same data, different instances
$a->equals($c);  // false - age differs
```

For SVO subclasses, the wrapped `$value` is compared directly:

```php
$age1 = ValidAge::from(18);
$age2 = ValidAge::from(18);
$age3 = ValidAge::from(20);

$age1->equals($age2);  // true
$age1->equals($age3);  // false
```

---

## Attributes

### `#[ArrayOf]` - Typed Array

Marks an array property as a typed collection of ImmutableBase instances. Each element is automatically instantiated from arrays, JSON strings, or pre-built objects. The target class must be a subclass of DTO, VO, or SVO.

```php
use ReallifeKip\ImmutableBase\Attributes\ArrayOf;

readonly class SignUpUsersDTO extends DataTransferObject
{
    #[ArrayOf(User::class)]
    public array $users;
    public int $userCount;
}
```

### `#[Strict]` - Strict Mode

Rejects input keys that do not correspond to declared properties.

```php
use ReallifeKip\ImmutableBase\Attributes\Strict;

#[Strict]
readonly class User extends ValueObject
{
    public string $name;
    public ValidAge $age;
    // ...
}

User::fromArray(['name' => 'Kip', 'age' => 18, 'extra' => '...']);
// StrictViolationException: Disallowed 'extra' for User.
```

### `#[Lax]` - Lax Mode

Exempts a class from strict mode enforcement, accepting input keys not declared as properties. Takes precedence over both `#[Strict]` and `ImmutableBase::strict()`.

```php
use ReallifeKip\ImmutableBase\Attributes\Lax;

#[Lax]
readonly class User extends ValueObject
{
    public string $name;
    public ValidAge $age;
    // ...
}

User::fromArray(['name' => 'Kip', 'age' => 18, 'extra' => '...']); // constructs normally
```

### `#[SkipOnNull]` / `#[KeepOnNull]`

`#[SkipOnNull]` excludes null-valued properties from `toArray()` and `toJson()` output. Can be applied at class level (affects all properties) or property level (affects a single property).
`#[KeepOnNull]` can only be applied at property level, overriding `#[SkipOnNull]` to retain the property in output even when null.
Without `#[SkipOnNull]`, `toArray()` and `toJson()` include null-valued properties by default.

```php
use ReallifeKip\ImmutableBase\Attributes\SkipOnNull;
use ReallifeKip\ImmutableBase\Attributes\KeepOnNull;

#[SkipOnNull]
readonly class UserDTO extends DataTransferObject
{
    #[KeepOnNull]
    public ?string $name;      // retained in output even when null
    public ValidAge|null $age; // excluded from output when null
}

UserDTO::fromArray([])->toArray();
// ['name' => null] (age excluded, name retained via KeepOnNull)
```

### `#[Spec]` - Validation Chain Info

An optional message for VO and SVO classes. When `validate()` returns false, this message is included in the `ValidationChainException`. Consumers can retrieve it via `$exception->getSpec()`.

```php
use ReallifeKip\ImmutableBase\Attributes\Spec;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\ValidationChainException;

#[Spec('Age must be at least 18')]
readonly class ValidAge extends SingleValueObject
{
    public int $value;

    public function validate(): bool
    {
        return $this->value >= 18;
    }
}

try {
    ValidAge::from(10);
} catch (ValidationChainException $e) {
    echo $e->getSpec(); // Age must be at least 18
}
```

### `#[ValidateFromSelf]` - Validation Chain Reversal

By default, the VO and SVO validation chain walks from the top of the inheritance chain down to the current class. With `#[ValidateFromSelf]` applied, the chain is reversed to start from the current class and walk upward.

---

## Configuration

### `ImmutableBase::strict(bool $on)`

Global strict mode. When enabled, the effect is equivalent to applying `#[Strict]` to all ImmutableBase subclasses.

```php
ImmutableBase::strict(true);
```

### `ImmutableBase::debug(?string $path)`

Enables debug logging. Redundant keys in input data are logged to `{$path}/ImmutableBaseDebugLog.log`, including timestamps, stack traces, and input content. Pass `null` to disable.

```php
ImmutableBase::debug(__DIR__); // enable debug logging
ImmutableBase::debug(null);    // disable debug logging
```

### `ImmutableBase::loadCache(string $path)`

Loads pre-generated property metadata cache produced by `cacher`, bypassing runtime reflection scanning to speed up initialization.

```php
ImmutableBase::loadCache(__DIR__ . '/cache.php');
```

---

## CLI Tools

### `cacher` - Metadata Cache Generator

Scans all ImmutableBase subclasses in the specified directory and generates a serialized metadata cache file `cache.php`, eliminating reflection overhead at startup. The cache is loaded via `ImmutableBase::loadCache()`.

```bash
php cacher
```

### `writer` - Documentation Generator

Generates documentation for all ImmutableBase subclasses in the project. Supports Mermaid class diagrams and Markdown property tables.

```bash
php writer
```

---

## Error Handling

All exceptions extend `ImmutableBaseException` and are categorized into two base types and three themes. Nested construction errors include the full property path in the message, e.g. `OrderDTO > $customer > $email > {error message}`.

### LogicException - Design Errors

#### DefinitionException - Definition Errors

Thrown when class structure or attribute configuration is incorrect. These are programming errors, typically triggered during reflection scanning on first instantiation.

`InvalidPropertyTypeException` - A property declares an unsupported type (e.g. `iterable`, `object`, non-ImmutableBase/non-Enum classes).

`InvalidVisibilityException` - A property is not declared as `public`.

`InvalidArrayOfTargetException` - The `#[ArrayOf]` target class is not a subclass of DTO, VO, or SVO.

`InvalidArrayOfUsageException` - `#[ArrayOf]` is applied to a property whose type is not `array`.

`InvalidSpecException` - `#[Spec]` is used without an argument or with an empty argument.

`InvalidCompareTargetException` - The `equals()` comparison target is not the same class, or an array contains a non-ImmutableBase object that cannot be compared.

`InvalidWithPathException` - A `with()` deep path targets a scalar property that cannot be traversed further.

`DebugLogDirectoryInvalidException` - The path specified in `ImmutableBase::debug()` does not exist, is not writable, or is not a directory.

### RuntimeException - Runtime Errors

#### InitializationException - Initialization Errors

Thrown during construction (`fromArray`, `fromJson`) or mutation (`with`) when input data does not satisfy declared type constraints.

`RequiredValueException` - A non-nullable property received null or is missing from the input data.

`InvalidValueException` - The value's type does not match the declared property type.

`InvalidEnumValueException` - The value cannot be resolved to any case of the target Enum; both name lookup and `tryFrom()` failed.

`InvalidJsonException` - JSON string decoding failed.

#### ValidationException - Validation Errors

Thrown on domain validation failure or structural constraint violation.

`ValidationChainException` - A VO or SVO's `validate()` returned false. If the class has a `#[Spec]` attribute, the custom message can be retrieved via `$exception->getSpec()`.

`StrictViolationException` - Under strict mode, input data contains keys not declared as properties.

`InvalidArrayOfItemException` - An element in an `#[ArrayOf]` array cannot be resolved as an instance of the target class.

---

## Deprecated

### Attributes

`#[DataTransferObject]`, `#[ValueObject]`, `#[Entity]`

---

## Notes

1. All properties in subclasses must be `public readonly` (enforced at scan time).
2. Forbidden property types: `iterable`, `object`, non-ImmutableBase/non-Enum classes such as `DateTime`, `Closure`.
3. Enum properties accept case names (`"HIGH"`) or backed values (`3`). The resolved property value is always an Enum instance.
4. `mixed` type is supported, but values will not be validated.

---

## License

This package is released under the [MIT License](https://opensource.org/license/mit).

---

## Maintainer

Developed and maintained by [Kip](mailto:bill402099@gmail.com). Suitable for all PHP projects.

---

Feedback and contributions are welcome - please open an Issue or submit a PR.
