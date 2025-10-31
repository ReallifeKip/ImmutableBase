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

An abstract base class designed for **immutable objects**, suitable for **DTOs (Data Transfer Objects)** and **VOs (Value Objects)** where data is initialized once and cannot be changed.

Focuses on **immutability** and **type safety**, with APIs that make it easy to construct immutable objects.

## Overview

1. Build objects via static constructors; ImmutableBase scans incoming keys/values and returns an instance.
2. If a value’s type does not match the declared property type, an exception is thrown with detailed class/property info.
3. Supports all PHP built-in types, Enums, instances, and union types.
4. For properties typed as subclasses of ImmutableBase, arrays/objects matching the declared structure are automatically instantiated.

## Installation

```bash
composer require reallifekip/immutable-base
```

## Example

```php
use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

class UserDTO extends DataTransferObject
{
    public readonly string $name;
    public readonly int $age;
}

class UserListDTO extends DataTransferObject
{
    #[ArrayOf(UserDTO::class)]
    public readonly array $users;
}

$userList = UserListDTO::fromArray([
    'users' => [
        ['name' => 'Alice', 'age' => 18],
        '{"name": "Bob", "age": 19}',
        UserDTO::fromArray(['name' => 'Carl', 'age' => 20]),
        UserDTO::fromJson('{"name": "Dave", "age": 21}')
    ]
]);
print_r($userList);
```

## Testing

### Unit tests

```bash
vendor/bin/phpunit tests
```

### Benchmarks

```bash
vendor/bin/phpbench run
```

## Object Types

### Data Transfer Object

```php
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

final class UserDTO extends DataTransferObject
{
    public readonly string $name;
    public readonly int $age;
}
```

### Value Object

```php
use ReallifeKip\ImmutableBase\Objects\ValueObject;
final class Money extends ValueObject
{
    private readonly int $value;
}
```

## API

### Constructing — `fromArray()`, `fromJson()`

> When scanning input, if a key is not a declared property of the class, it is ignored and will not exist on the resulting instance.

```php
$user = User::fromArray([
    'name' => 'Kip',
    'age' => 18
]);
```

```php
$user = Money::fromJson('{"value": 1000}');
```

### Exporting — `toArray()`, `toJson()`

```php
// ['name' => 'Kip', 'age' => 18]
$user->toArray();
```

```php
// {"name":"Kip","age":18}
$user->toJson();
```

### Updating — `with()`

> ⚠️ This does **not** mutate the original object. A new instance is returned with partial updates, by design. For the underlying rationale, see [Objects and references](https://www.php.net/manual/en/language.oop5.references.php).<br>
> ⚠️ When `with()` targets a `#[ArrayOf]` property, the array is **rebuilt**.<br>
> Keys that are not declared properties are ignored and will not appear on the new instance.

```php
// Update a scalar property
$newUser = $user->with([
    'name' => 'someone'
]);

// Partial update of a nested object
$userWithNewAddress = $user->with([
    'profile' => [
        'address' => 'Taipei City'
    ]
]);
```

## API for SingleValueObject only

### Constructing - `from()`
```php
$email = Gmail::from('bill402099@gmail.com');
```

### Comparing - `equals()`
Compares the current object with another instance of the same class.
If the provided value is not an instance of the same class, an exception will be thrown.
This method returns true only when both objects contain an identical $value.
```php
$email2 = Gmail::from('bill402099@gmail.com');
$email3 = Gmail::from('bill402099-2@gmail.com');
$email4 = Hotmail::from('bill402099@gmail.com');

$email->equals($email2); // true
$email->equals($email3); // false
$email->equals($email4); // Exception thrown
```

## Architecture: Attributes

### `#[DataTransferObject]`

> ⚠️ Will be deprecated in **v4.0.0**. See [Architecture: Inheritance](#architecture-inheritance) for the new approach.

All properties must be `public readonly`. Intended for cross-layer data transport.

```php
use ReallifeKip\ImmutableBase\DataTransferObject;
use ReallifeKip\ImmutableBase\ImmutableBase;

#[DataTransferObject]
class UserDTO extends ImmutableBase
{
    public readonly string $name;
    public readonly int $age;
    public readonly string $email;
}
```

### `#[ValueObject]`

> ⚠️ Will be deprecated in **v4.0.0**. See [Architecture: Inheritance](#architecture-inheritance).

All properties must be `private`. Intended for value objects in DDD.

```php
use ReallifeKip\ImmutableBase\ValueObject;
use ReallifeKip\ImmutableBase\ImmutableBase;

#[ValueObject]
class Money extends ImmutableBase
{
    private int $amount;
    private string $currency;

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}
```

### `#[Entity]`

> ⚠️ Will be deprecated in **v4.0.0**. See [Architecture: Inheritance](#architecture-inheritance).

All properties must be `private`. Intended for entities in DDD.

```php
use ReallifeKip\ImmutableBase\Entity;

#[Entity]
class User extends ImmutableBase
{
    private string $id;
    private string $email;
    private string $name;

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
```

### `#[ArrayOf]` — Array auto-instantiation

> ⚠️ When `with()` targets a `#[ArrayOf]` property, the array is **rebuilt**.

Marks an array property as an **array of instances**. Incoming data for that array will be converted into instances of the specified class.
Accepts `JSON strings`, `arrays`, or **already-instantiated objects** that match the required structure.

```php
use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

class UserListDTO extends DataTransferObject
{
    #[ArrayOf(UserDTO::class)]
    public readonly array $users;
}

$userList = UserListDTO::fromArray([
    'users' => [
        // All four forms are accepted
        ['name' => 'Alice', 'age' => 18],
        '{"name": "Bob", "age": 19}',
        UserDTO::fromArray(['name' => 'Carl', 'age' => 20]),
        UserDTO::fromJson('{"name": "Dave", "age": 21}')
    ]
]);
```

## Architecture: Inheritance

### `DataTransferObject`

All properties must be `public readonly`. Intended for cross-layer data transport.

```php
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

class UserDTO extends DataTransferObject
{
    public readonly string $name;
    public readonly int $age;
    public readonly string $email;
}
```

### `ValueObject`

All properties must be `private readonly`. Intended for value objects in DDD.

```php
use ReallifeKip\ImmutableBase\Objects\ValueObject;

class Money extends ValueObject
{
    private int $value;
    public function getValue(): int
    {
        return $this->value;
    }
}
```

### `SingleValueObject`

Designed **exclusively for single-value inheritance** — do **not** extend it for multi-property value objects.
Classes extending `SingleValueObject` are **required** to declare exactly one property:
`private readonly {type} $value`.

```php
use ReallifeKip\ImmutableBase\Objects\SingleValueObject;

class Email extends SingleValueObject
{
    protected readonly string $value;
    public function validate(): bool
    {
        return str_contains($this->value, '@');
    }
}

class Gmail extends Email
{
    public function validate(): bool
    {
        return str_contains($this->value, 'gmail.com');
    }
}
```

> The `validate()` method defines validation rules automatically executed during initialization.
> If the class hierarchy includes multiple levels of inheritance, all `validate()` methods in the chain will be called upward until every one passes before construction completes.

### Output

```php
$email = Email::from('bill402099@gmail.com');

// bill402099@gmail.com ⚠️ Works only if $value is a string
echo $email;
// bill402099@gmail.com
echo $email();
// bill402099@gmail.com
echo $email->value;
```

All of the above expressions produce the same result.

## Notes

1. **Property types** must be explicitly declared; `mixed` is not allowed.
2. **Enums**: when a property is typed as an Enum, construction validates incoming data against the Enum’s cases/values and the resulting property is the **Enum instance**. Use `string` types if you want raw text values.

## License

This package is released under the [MIT License](https://opensource.org/license/mit).

## Maintainer

Developed and maintained by [Kip](mailto:bill402099@gmail.com). Suitable for implementing immutable DTOs/VOs in Laravel, DDD, and Hexagonal Architecture.

---

Feedback and contributions are welcome — please open an Issue or submit a PR.
