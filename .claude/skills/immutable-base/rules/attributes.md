# Attributes

All attributes live in `ReallifeKip\ImmutableBase\Attributes\`.

---

## #[ArrayOf] — typed array elements

**Target:** property  
**Applies to:** DTO, VO  
**Requirement:** property type must be exactly `array`

Declares that every element of an `array` property must be a specific type. Elements are auto-hydrated on construction.

```php
use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Enums\Native;

readonly class OrderDto extends DataTransferObject
{
    #[ArrayOf(OrderItemDto::class)]      // ImmutableBase subclass
    public array $items;

    #[ArrayOf(Native::string)]           // primitive: Native::string, ::int, ::float, ::bool
    public array $tags;
}

// Items can be arrays (auto-hydrated) or already-constructed instances:
OrderDto::fromArray([
    'items' => [
        ['sku' => 'ABC', 'qty' => 2],          // auto-hydrated to OrderItemDto
        OrderItemDto::fromArray(['sku' => 'XYZ', 'qty' => 1]),  // passthrough
    ],
    'tags' => ['urgent', 'fragile'],
]);
```

---

## #[Defaults] — property fallback value

**Target:** property  
**Applies to:** DTO, VO  
**Priority:** lowest (explicit input → `defaultValues()` → `#[Defaults]`)

```php
use ReallifeKip\ImmutableBase\Attributes\Defaults;

readonly class CreateUserDto extends DataTransferObject
{
    public string $name;
    #[Defaults('user')]
    public string $role;       // 'user' when 'role' key absent from input
    #[Defaults(false)]
    public bool   $isActive;
}
```

Note: `#[Defaults]` is ignored when the key is explicitly present with `null`.

---

## #[SkipOnNull] — omit null values from serialization

**Target:** class or property  
**Applies to:** DTO, VO

When applied at **class level**, all nullable properties are omitted from `toArray()` / `toJson()` output when their value is `null`.  
When applied at **property level**, only that property is omitted.

```php
use ReallifeKip\ImmutableBase\Attributes\SkipOnNull;
use ReallifeKip\ImmutableBase\Attributes\KeepOnNull;

#[SkipOnNull]
readonly class UserDto extends DataTransferObject
{
    public string  $name;
    public ?string $nickname;   // omitted when null
    #[KeepOnNull]
    public ?string $bio;        // always included, even when null
}

UserDto::fromArray(['name' => 'Alice', 'nickname' => null, 'bio' => null])->toArray();
// ['name' => 'Alice', 'bio' => null]
```

---

## #[KeepOnNull] — force-keep null despite SkipOnNull

**Target:** property  
**Applies to:** DTO, VO  
**Requires:** class-level `#[SkipOnNull]` to be meaningful

Overrides class-level `#[SkipOnNull]` for a specific property, ensuring it always appears in `toArray()` / `toJson()` output even when `null`.

---

## #[InputKeyTo] — remap input key case on construction

**Target:** class or property  
**Applies to:** DTO, VO

Converts input array keys to the specified `KeyCase` before property matching. Useful when consuming snake_case APIs into camelCase properties.

```php
use ReallifeKip\ImmutableBase\Attributes\InputKeyTo;
use ReallifeKip\ImmutableBase\Enums\KeyCase;

#[InputKeyTo(KeyCase::Camel)]      // class-level: all keys converted
readonly class UserDto extends DataTransferObject
{
    public string $firstName;
    public string $lastName;
}

// snake_case input works automatically:
UserDto::fromArray(['first_name' => 'Alice', 'last_name' => 'Smith']);
```

Property-level `#[InputKeyTo]` overrides the class-level setting for that specific property.

### Available KeyCase values

| Case | Example |
|---|---|
| `KeyCase::Snake` | `nick_name` |
| `KeyCase::Camel` | `nickName` |
| `KeyCase::Pascal` | `NickName` |
| `KeyCase::Kebab` | `nick-name` |
| `KeyCase::Macro` | `NICK_NAME` |
| `KeyCase::PascalSnake` | `Nick_Name` |
| `KeyCase::Train` | `Nick-Name` |
| `KeyCase::CamelKebab` | `nick-Name` |

---

## #[OutputKeyTo] — remap output key case for serialization

**Target:** class or property  
**Applies to:** DTO, VO

Converts property names to the specified `KeyCase` in `toArray()` / `toJson()` output.

```php
use ReallifeKip\ImmutableBase\Attributes\OutputKeyTo;
use ReallifeKip\ImmutableBase\Enums\KeyCase;

#[OutputKeyTo(KeyCase::Snake)]     // class-level: all keys snake_case in output
readonly class UserDto extends DataTransferObject
{
    public string $firstName;
    public string $lastName;
}

UserDto::fromArray(['firstName' => 'Alice', 'lastName' => 'Smith'])->toArray();
// ['first_name' => 'Alice', 'last_name' => 'Smith']
```

---

## #[Strict] — reject unknown input keys

**Target:** class  
**Applies to:** DTO, VO

Throws `StrictViolationException` if the input array contains keys not declared as properties.

```php
use ReallifeKip\ImmutableBase\Attributes\Strict;

#[Strict]
readonly class CreateUserDto extends DataTransferObject
{
    public string $name;
    public string $email;
}

CreateUserDto::fromArray(['name' => 'Alice', 'email' => 'a@b.com', 'extra' => 'x']);
// StrictViolationException: unknown keys ['extra']
```

Global strict mode (without per-class attribute): `ImmutableBase::strict(true)`.

---

## #[Lax] — opt out of global strict mode

**Target:** class  
**Applies to:** DTO, VO

When `ImmutableBase::strict(true)` is enabled globally, `#[Lax]` exempts a specific class from strict key checking.

```php
use ReallifeKip\ImmutableBase\Attributes\Lax;

ImmutableBase::strict(true);

#[Lax]
readonly class FlexibleDto extends DataTransferObject
{
    public string $name;
    // accepts extra keys without throwing
}
```

---

## #[Spec] — domain validation message

**Target:** class  
**Applies to:** VO, SVO only (not DTO)

Attaches a domain message to the `ValidationChainException` thrown when `validate()` returns `false`. Use as an error code, i18n key, or human-readable description.

```php
use ReallifeKip\ImmutableBase\Attributes\Spec;

#[Spec('email.invalid')]
readonly class Email extends SingleValueObject
{
    public string $value;

    public function validate(): bool
    {
        return filter_var($this->value, FILTER_VALIDATE_EMAIL) !== false;
    }
}
```

---

## #[ValidateFromSelf] — child-first validation order

**Target:** class  
**Applies to:** VO, SVO

By default, the validation chain runs **parent → child**. `#[ValidateFromSelf]` reverses it to **child → parent**, so the most specific rule is enforced first.

```php
use ReallifeKip\ImmutableBase\Attributes\ValidateFromSelf;

#[ValidateFromSelf]
readonly class PositiveMoney extends Money
{
    public function validate(): bool
    {
        return $this->amount > 0;   // checked before Money::validate()
    }
}
```
