# DataTransferObject

Carries structured data between layers with no domain validation. Construction-time type checking is automatic — if a value doesn't match the declared property type, an exception is thrown immediately.

## Structure

```php
<?php

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class CreateUserDto extends DataTransferObject
{
    public string  $name;
    public string  $email;
    public string  $password;
    public ?string $role;        // nullable → optional in input
}
```

## Construction — always ::fromArray()

```php
// From validated request data (keys must match property names)
$dto = CreateUserDto::fromArray($request->validated());

// From explicit array
$dto = CreateUserDto::fromArray([
    'name'     => 'Alice',
    'email'    => 'alice@example.com',
    'password' => 'secret',
    'role'     => null,
]);

// From JSON string
$dto = CreateUserDto::fromJson('{"name":"Alice","email":"alice@example.com","password":"secret"}');
```

## Named Constructors (recommended for domain mapping)

When the input source uses different key names or needs transformation, add a static factory:

```php
readonly class UserResultDto extends DataTransferObject
{
    public int    $id;
    public string $name;
    public string $email;
    public string $role;

    public static function fromModel(User $user): self
    {
        return self::fromArray([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role->value,  // Enum → scalar
        ]);
    }
}
```

## Nested DTOs

Nested objects are hydrated automatically when the input is an associative array:

```php
readonly class OrderDto extends DataTransferObject
{
    public string    $reference;
    public AddressDto $shippingAddress;    // nested DTO — auto-hydrated
}

// Both of these work:
OrderDto::fromArray([
    'reference'       => 'ORD-001',
    'shippingAddress' => ['street' => '123 Main St', 'city' => 'Taipei'],
]);

OrderDto::fromArray([
    'reference'       => 'ORD-001',
    'shippingAddress' => AddressDto::fromArray(['street' => '123 Main St', 'city' => 'Taipei']),
]);
```

## Typed Array Properties

Use `#[ArrayOf]` — see `rules/attributes.md` for full details.

```php
use ReallifeKip\ImmutableBase\Attributes\ArrayOf;

readonly class OrderDto extends DataTransferObject
{
    #[ArrayOf(OrderItemDto::class)]
    public array $items;   // OrderItemDto[] — each element auto-hydrated
}
```

## Default Values

Two ways to provide defaults:

```php
// Option A: #[Defaults] attribute on the property
use ReallifeKip\ImmutableBase\Attributes\Defaults;

readonly class CreateUserDto extends DataTransferObject
{
    public string $name;
    #[Defaults('user')]
    public string $role;   // defaults to 'user' when absent from input
}

// Option B: override defaultValues()
readonly class CreateUserDto extends DataTransferObject
{
    public string $name;
    public string $role;

    public static function defaultValues(): array
    {
        return ['role' => 'user'];
    }
}
```

## Enum Properties

Enum properties accept either the enum instance, its case name (string), or its backed value:

```php
readonly class CreateUserDto extends DataTransferObject
{
    public string   $name;
    public UserRole $role;   // BackedEnum
}

// All three work:
CreateUserDto::fromArray(['name' => 'Alice', 'role' => UserRole::Admin]);
CreateUserDto::fromArray(['name' => 'Alice', 'role' => 'Admin']);   // case name
CreateUserDto::fromArray(['name' => 'Alice', 'role' => 1]);         // backed value
```

## Rules

### Extend DataTransferObject, not ImmutableBase directly

```php
// Bad
readonly class CreateUserDto extends ImmutableBase {}

// Good
readonly class CreateUserDto extends DataTransferObject {}
```

### Class must be readonly

```php
// Bad — mutable, reflection hydration still works but defeats immutability
class CreateUserDto extends DataTransferObject
{
    public string $name;
}

// Good
readonly class CreateUserDto extends DataTransferObject
{
    public string $name;
}
```

### Properties at class level — no constructor promotion

The constructor is `protected` and takes `array $data`. Promotion syntax is incompatible.

```php
// Bad — will not work
readonly class CreateUserDto extends DataTransferObject
{
    public function __construct(public string $name) {}
}

// Good
readonly class CreateUserDto extends DataTransferObject
{
    public string $name;
}
```

### Never instantiate with new

```php
// Bad — constructor is protected
$dto = new CreateUserDto(['name' => 'Alice']);

// Good
$dto = CreateUserDto::fromArray(['name' => 'Alice']);
```

### No business logic in DTOs

DTOs carry data only. Transformations and decisions belong in Services or ValueObjects.
