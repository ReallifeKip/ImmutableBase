---
name: immutable-base
description: "Apply this skill whenever working with reallifekip/immutable-base — creating or modifying DataTransferObject, ValueObject, or SingleValueObject classes; using attributes like ArrayOf, SkipOnNull, Defaults, InputKeyTo, OutputKeyTo, Strict, Spec; or calling toArray(), toJson(), with(), equals(), fromArray(), fromJson()."
license: MIT
metadata:
  author: Zhang-mason
---

# immutable-base

Strict immutable DTOs, VOs, and SVOs for PHP 8.4+ with construction-time type validation, deep path mutation, and automatic validation chaining.

## Three Base Classes

| Class | Use when | Construction |
|---|---|---|
| `DataTransferObject` | Carrying structured data between layers, no domain rules | `::fromArray()` |
| `ValueObject` | Multi-property domain concept with invariants | `::fromArray()` |
| `SingleValueObject` | Single scalar with domain validation (email, phone, money) | `::from()` |

## Quick Reference

- **DTO guide** → `rules/data-transfer-object.md`
- **VO / SVO guide** → `rules/value-object.md`
- **All attributes** → `rules/attributes.md`
- **Serialization & mutation** → `rules/serialization.md`

## Key Rules (apply always)

- Properties declared at **class level**, not constructor promotion
- Instantiate via **named constructors** (`::fromArray()` / `::from()`), never `new`
- All properties must be **public** (readonly enforced by `readonly class`)
- Use `#[ArrayOf(Class::class)]` for typed array properties — never raw `array` without it
- `DataTransferObject` — no `validate()` needed; `ValueObject` / `SingleValueObject` — override `validate(): bool`

## How to Apply

1. Identify which base class fits (DTO / VO / SVO)
2. Read the relevant rule file
3. Check `rules/attributes.md` for any needed modifiers
