# Upgrading from 4.x to 5.0

5.0 renames two attributes, removes APIs that were internal or deprecated in 4.x, and ships behaviour fixes that reject input 4.x accepted or report errors differently. The PHP requirement is unchanged (`^8.4`), and a cache written by the 4.x `ib-cacher` keeps working.

Most projects only need step 1. Steps 2–4 list what to search for; the rest describes behaviour changes in the order they are most likely to matter.

## 1. Rename `#[InputKeyTo]` / `#[OutputKeyTo]`

| 4.x | 5.0 |
|---|---|
| `#[InputKeyTo(KeyCase::…)]` | `#[InputCaseTo(KeyCase::…)]` |
| `#[OutputKeyTo(KeyCase::…)]` | `#[OutputCaseTo(KeyCase::…)]` |

Behaviour is unchanged. The old names are removed, not aliased. A class or property that still carries one fails at scan time with `RemovedAttributeException` naming the replacement, rather than having its conversion skipped without notice (PHP never resolves an attribute that ImmutableBase does not look up). Rename them all at once:

```bash
grep -rlE 'InputKeyTo|OutputKeyTo' src tests | xargs sed -i -e 's/InputKeyTo/InputCaseTo/g' -e 's/OutputKeyTo/OutputCaseTo/g'
# macOS: sed -i '' -e …
```

The new names say what the attributes convert (the key's case) and keep them apart from the new exact-name attributes `#[InputKey]` / `#[OutputKey]`.

A cache written by the 4.x `ib-cacher` still holds the key cases computed from the old attributes, so classes served from it keep working until the cache is regenerated; regenerating reports any name you missed.

## 2. Removed APIs

- **`ImmutableBase::state()`** — was `@internal` and deprecated. Test suites that reset or seed caches through it can use `ReallifeKip\ImmutableBase\Internal\Metadata::state()`, which returns the same handle but is internal and unsupported. Application code should not need either.
- **`ImmutableBaseException::$depth` / `::$paths`** — deprecated since the error path moved onto each exception; nothing read or wrote them. Remove any resets of them.
- **`InvalidKeyCaseException` now extends `DefinitionException`**, like every other definition error, instead of `\InvalidArgumentException`. A `catch (\InvalidArgumentException)` around class definitions no longer catches it; catch `DefinitionException` (or `ImmutableBaseException`).

## 3. `TypeError` is no longer thrown for invalid input

Every invalid input now raises an `ImmutableBaseException` subclass. Search for `catch (TypeError` / `catch (\TypeError` around `fromArray()`, `fromJson()`, `from()` and `with()`; those branches no longer run. Cases that used to escape as `TypeError`:

- `SVO::from()` with the wrong scalar type, `null` or an object → `InvalidValueException` / `RequiredValueException`.
- `#[ArrayOf]` given a non-array, or an enum element given a bool / float / null / array → `InvalidValueException` / `InvalidArrayOfItemException`.
- A backed enum given a value of the other backing type → `InvalidEnumValueException`.

Union resolution changed with it:

- A `null` member no longer matches arbitrary arrays: `A|B|null` given an array that is neither `A` nor `B` now fails instead of passing the array through.
- `string|false` (and other `true` / `false` literal members) now accept that literal; standalone `true` / `false` types reject everything else.
- An enum constant that is not a case (`const LIMIT = 10`) no longer resolves as a case. Case aliases (`const DEFAULT = self::Low`) still do.
- A union or multi-type `#[ArrayOf]` member that rejects the input for any reason (its own `#[Strict]`, malformed JSON) now falls through to the next member instead of aborting.

## 4. Strict mode applies to `with()`

Under `#[Strict]` or `ImmutableBase::strict(true)` (unless `#[Lax]`), `with()` now throws `StrictViolationException` for keys that name no property, as construction already did. Outside strict mode such keys are still ignored. Look for `with($request->all())`-style calls on strict classes.

## 5. `with()` resolves values exactly as `fromArray()`

- Text that looks like JSON (`"[draft] hi"`, `'{"a":1}'`) is stored as text in `string` and SVO properties instead of being decoded.
- A JSON string given to an `#[ArrayOf]` property yields typed items, not plain arrays.
- A JSON string given to a plain `array` property is still decoded, as in 4.x.

## 6. `equals()`

- Arrays holding `null` compare correctly (`[null]` equals `[null]`).
- `#[ArrayOf]` of enums compares instead of throwing.
- Elements of different ImmutableBase classes, or an object against a scalar, compare unequal instead of throwing.
- **A foreign object (e.g. `DateTimeImmutable`) in a `mixed` property throws `InvalidCompareTargetException`.** 4.x read `->name` from it and could report two different dates as equal. The same instance on both sides counts as equal.

## 7. `toArray()` / `toJson()` with foreign objects

A foreign object in a `mixed` or plain `array` property used to crash with "Call to undefined method …::toArray()". A `JsonSerializable` object is now serialized through `jsonSerialize()`; any other object throws `InvalidSerializeTargetException`.

## 8. Error messages

The `Class > $property > …` prefix no longer picks up stale segments from an earlier failure (after a non-ImmutableBase exception escaped `validate()`, or after a union member failed). If you parse messages, expect the prefix to describe only the failing path. Some `#[ArrayOf]` element failures also report a different exception class:

- A malformed JSON string for a DTO / VO element → `InvalidJsonException` (was `InvalidArrayOfItemException`), matching the same string given to a property.
- An object for an SVO element → `InvalidArrayOfItemException` (was the SVO's `InvalidValueException`).
- An array for an enum element → `InvalidArrayOfItemException` (was an "undefined method" error).

## 9. `ib-writer` output

- **TypeScript:** a nullable property is declared `name: T | null`, matching what `toJson()` emits. Only a `#[SkipOnNull]` property without `#[KeepOnNull]` stays optional (`name?: T`). Front-end code compiled against 4.x declarations may need explicit `null` handling.
- **TypeScript:** string enum values are escaped, and enums used only through `#[ArrayOf]` are declared, so the file compiles.
- **Markdown / Mermaid:** inherited properties, `#[ArrayOf]` element types and union relations are shown; boolean defaults read `true` / `false`; a property with a default is not marked required.
- `--format` / `--output` run it without prompts (CI, composer scripts).

## 10. Cache

A cache written by the 4.x `ib-cacher` is used as-is. An entry whose class has since changed property names, types or nullability is ignored and the class is scanned by reflection. Changes to attributes alone are still not detected: regenerate the cache on deploy, as before. Classes in files with several namespaces, or next to `new readonly class` / `new #[Attr] class` expressions, are now discovered correctly by both `ib-cacher` and `ib-writer`.

## New in 5.0

- **`#[InputKey('…')]` / `#[OutputKey('…')]`** map a property to an exact key, for keys no `KeyCase` can produce (`'2fa'`, `'user.id'`). They take precedence over `#[InputCaseTo]` / `#[OutputCaseTo]`.
- Under strict mode, a key consumed by a property-level `#[InputCaseTo]` is no longer reported as undeclared.
