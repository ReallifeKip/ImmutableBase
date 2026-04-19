# CHANGELOG

## [4.3.0] - 2026-04-19

### Added

- **`#[InputKeyTo(KeyCase::X)]`** — Input key case conversion. Apply at class level to remap all incoming array keys before hydration, or at property level to override for a specific property. Conversion splits on camelCase/PascalCase boundaries, underscores, hyphens, and whitespace, then rejoins in the target case.
- **`#[OutputKeyTo(KeyCase::X)]`** — Output key case conversion. Apply at class level to remap all serialized keys during `toArray(true)` / `toJson(true)`, or at property level to override for a specific property.
- **`KeyCase` enum** — 8 naming conventions: `Snake` (`nick_name`), `PascalSnake` (`Nick_Name`), `Macro` (`NICK_NAME`), `Camel` (`nickName`), `Pascal` (`NickName`), `Kebab` (`nick-name`), `CamelKebab` (`nick-Name`), `Train` (`Nick-Name`).
- **`InvalidKeyCaseException`** — Thrown at definition time when `#[InputKeyTo]` or `#[OutputKeyTo]` receives a value that is not a `KeyCase` enum instance (e.g. a plain string).

## [4.2.2] - 2026-03-14

### Fixed

- Mark `DataTransferObject::__construct` as `final` to prevent
  unintended override in subclasses, aligning implementation with
  original design intent
- Fix `@desc` annotation handling in ib-writer Markdown mode output:
  replaced `isset()` check with strict empty string comparison,
  preventing blank description lines from being written

## [v4.2.1] - 2026-03-12

### Refactored

- Internal Structure Optimization: Extracted typescript namespace rendering logic into a standalone renderNamespaces (formerly namespacesHandler) method to reduce cognitive complexity in contentGenerate.
- Naming Alignment: Renamed internal methods to strictly follow the render* naming convention for better clarity and maintainability.
- Control Flow Refactoring: Replaced several if-else blocks with match expressions to reduce Cyclomatic Complexity and align with Sonar quality standards.

### Fixed
- Sonar Compliance: Added explicit documentation and justifications for empty methods to resolve "Methods should not be empty" code smells.

## [v4.2.0] - 2026-03-12

### Added

- **`Native` enum — Primitive typed arrays via `#[ArrayOf]`.** `#[ArrayOf]` now accepts `Native::string`, `Native::int`, `Native::float`, and `Native::bool` to declare arrays of validated PHP scalar values without wrapping them in a SingleValueObject.
- **`ib-writer` TypeScript output.** `vendor/bin/ib-writer` now supports `.ts` generation, producing `declare namespace` blocks with interfaces for DTO/VO, type aliases for SVO, and enum/union types for referenced PHP enums.

### Changed

- **Standalone `null` property type is now forbidden.** Declaring a property typed as `null` alone throws `InvalidPropertyTypeException` at scan time. Use `?Type` or `Type|null` for nullable properties.
- **`InvalidPropertyTypeException` message updated.** Now explicitly lists allowed types and provides nullable usage guidance.
- **`InvalidArrayOfItemException` message updated.** Now distinguishes between ImmutableBase class resolution failures and primitive type mismatches.

## [v4.1.1] - 2025-03-07

### Fixed

#### ib-writer
- Property tables now include a `default` column displaying default
  values from `#[Defaults]` attributes and `defaultValues()` overrides
- Enum types referenced as property types now generate their own
  documentation blocks with case listings and backing values
- BackedEnum defaults display the backing value; UnitEnum defaults
  display the case name; dynamic defaults from `defaultValues()`
  display as `(dynamic)`

## [v4.1.0] - 2026-03-07

### Added

- **`defaultValues()` — Static default value declaration.** Override `defaultValues(): array` to declare fallback values for properties absent from input data. Keys must match declared property names; unmatched keys are silently ignored. Supports any type valid for the target property, including subclasses of ImmutableBase and Enum.
- **`#[Defaults(value)]` — Attribute-based default value.** Apply `#[Defaults(value)]` to individual properties for constant-expression defaults. Constrained by PHP attribute syntax to scalar values, arrays, and class constants.
- **Default value resolution priority.** During construction (`fromArray` / `fromJson`), property values are resolved in the following order:
  1. Explicit input value (including explicit `null`)
  2. `defaultValues()[$propertyName]`
  3. `#[Defaults(value)]` attribute value
  4. `null` (if nullable) or `RequiredValueException`
- **Explicit `null` is respected.** When a key is present in the input with a `null` value, it is treated as an intentional assignment — default values are not applied.
- **Cache-aware default values.** `ib-cacher` serializes cacheable default values (scalars, arrays) into the cache file. Non-serializable defaults (objects, Closures) are excluded from the cache with a `[Notice]` warning and resolved at runtime via `defaultValues()` on every construction.
- **SVO `defaultValues()` sealed.** `SingleValueObject::defaultValues()` is declared `final` and returns an empty array. SVOs require explicit values via `from()` by design.

### Changed

- **`__construct()` uses `array_key_exists()` for default resolution.** Replaces `isset()` to correctly distinguish between "key absent" (apply default) and "key present with `null`" (respect explicit null).

## [v4.0.0] - 2026-03-01

### Breaking Changes

- **Architecture: Attribute annotation replaced by class inheritance.** Objects are now defined by extending `DataTransferObject`, `ValueObject`, or `SingleValueObject` instead of annotating with `#[DataTransferObject]`, `#[ValueObject]`, or `#[Entity]`.
- **Entity removed.** The `Entity` object type has been removed entirely.
- **All properties must be `public`.** In v3, `ValueObject` and `Entity` properties were `private` with getter methods. All properties now require `public` visibility, enforced at scan time. Classes should be declared as `readonly class`, which handles immutability at the PHP level.
- **Exception system rebuilt.** All v3 exceptions have been removed and replaced with a structured hierarchy under `ImmutableBaseException`, categorized into `LogicException` > `DefinitionException` (design errors) and `RuntimeException` > `InitializationException` (input type violations) / `ValidationException` (domain constraint violations). See [README](./README.md) for details.
- **`object`, `iterable`, and non-IB/non-Enum class types forbidden.** Properties typed as `object`, `iterable`, or unsupported classes (e.g. `DateTime`, `Closure`) now throw `InvalidPropertyTypeException` at scan time.

### Added

- **`SingleValueObject` (SVO).** New object type for semantically meaningful single values. Provides `from()`, `__toString()`, `__invoke()`, and `jsonSerialize()`. Child classes can freely define the type of `$value` via interface + hooked property design.
- **`equals()`.** Deep structural equality comparison for all ImmutableBase subclasses, with recursive comparison of nested objects and arrays.
- **`#[Strict]` / `#[Lax]`.** Control whether undeclared input keys are rejected or accepted.
- **`#[SkipOnNull]` / `#[KeepOnNull]`.** Control whether null-valued properties appear in `toArray()` / `toJson()` output.
- **`#[Spec]`.** Attach a domain-specific validation message to VO/SVO, retrievable via `ValidationChainException::getSpec()`.
- **`#[ValidateFromSelf]`.** Reverse the validation chain direction to bottom-up (default is top-down).
- **Automatic validation chain.** VO and SVO automatically traverse the entire inheritance hierarchy during construction. Each class in the chain is invoked if it defines `validate(): bool`, but defining it is optional -- classes without it are simply skipped without breaking the chain.
- **Hierarchical error path tracing.** Nested construction errors include the full property path in the exception message (e.g. `OrderDTO > $customer > $email > {error message}`).
- **`ImmutableBase::strict()`.** Global strict mode.
- **`ImmutableBase::debug()`.** Debug logging for redundant input keys.
- **`ImmutableBase::loadCache()`.** Load pre-generated metadata cache to bypass runtime reflection.
- **CLI: `ib-cacher`.** Metadata cache generator. Supports `--scan-dir` for targeted scanning and `--clear` for cache removal.
- **CLI: `ib-writer`.** Documentation generator producing Mermaid class diagrams and Markdown property tables.
- **Benchmark suite.** Dedicated benchmarks for `with()` and hydration covering flat scalar updates, dot-notation deep paths, bracket notation, chained calls, and batch operations.

### Changed

- **`with()` selective resolution.** Only changed properties are resolved; unchanged properties are carried over by reference, yielding a 44–68% performance improvement depending on nesting depth.
- **`with()` deep path syntax now supports bracket notation** (`items[0].sku`) and custom separators, in addition to existing dot notation.

### Deprecated

- `#[DataTransferObject]` attribute — use `extends DataTransferObject`.
- `#[ValueObject]` attribute — use `extends ValueObject`.
- `#[Entity]` attribute — removed entirely.

## [v3.1.3] - 2025-12-14

### Changed

- Refactored core logic in `src/ImmutableBase.php` to introduce static caching for reflection properties and modes, enhancing performance for repeated operations.
- Optimized `walkProperties()` method to cache property lists per class, significantly reducing reflection overhead on subsequent calls.
- Enhanced property initialization by extracting logic into dedicated `analyzeClass()` method for better code organization and maintainability.
- Improved `toArray()` implementation with new `analyzeClassForToArray()` callback method, providing cleaner separation of concerns.
- Updated enum resolution with dedicated `analyzeEnum()` method, improving readability and error handling for enum value assignments.
- Enhanced PHPDoc documentation and method descriptions throughout `ImmutableBase.php` for better developer experience and API clarity.

## [v3.1.2] - 2025-11-24

### Fixed

- Fixed a bug where Immutable Objects implementing a custom constructor skipped the normal initialization flow, causing toArray() to consistently throw errors. The initialization step is now guaranteed before property traversal, ensuring toArray() works correctly even with custom constructors.

## [v3.1.1] - 2025-11-05

### Fixed

- Prevented fatal error caused by invalid enum value assignment in property resolution.

## [v3.2.0-alpha.1] - 2025-10-31

* Added HasValidate interface defining the validate method.
* Introduced abstract classes SingleValueObject, extending ValueObject and implementing HasValidate.
* Changed the visibility of the constructInitialize method to final protected.
* Enhanced walkProperties for better stability by ensuring reflection reinitializes when missing.
* Added toJson method to support JSON encoding.

## [v3.1.0] - 2025-10-29

### ⚠️Note

> Starting from this release, the CHANGELOG will be maintained in English only.<br>
> 自本版本後僅提供英文版 CHANGELOG，不再提供中文版內容。

### Added

- Added `fromArray()` and `fromJson()` as new object construction entry points.
  Direct instantiation via `new` is no longer recommended.
- Added **Traditional Chinese documentation** `README_TW.md`, providing bilingual documentation and badges.
- Added **class-based object APIs**: `ReallifeKip\ImmutableBase\Objects\{DataTransferObject, ValueObject, Entity}`, allowing direct inheritance via `extends`.
- Added **Attributes namespace**: `ReallifeKip\ImmutableBase\Attributes\{DataTransferObject, ValueObject, Entity, ArrayOf}`.
- Added a set of **granular exception classes** (main ones listed):
  `ImmutableBaseException`, `RuntimeException`, `LogicException`,
  `InvalidTypeException`, `InvalidJsonException`,
  `InvalidArrayException`, `InvalidArrayItemException`, `InvalidArrayValueException`,
  `InvalidArrayOfClassException`, `InvalidPropertyVisibilityException`,
  `AttributeException`, `InheritanceException`, `NonNullablePropertyException`.
- `with()` and `ArrayOf` now support automatic instantiation of array elements into specified classes.
  They accept **JSON strings, plain arrays, or existing instances** as input.
- Completed PHPDoc for the `toArrayOrValue()` core method.

### Changed

- Refactored and simplified parts of the core logic in `src/ImmutableBase.php`.
- Extracted **Attributes** and **Objects** into separate namespaces to improve project structure and code readability.
- `with()` now supports modifying nested properties that are themselves subclasses of `ImmutableBase`, without requiring re-instantiation.
- Updated `composer.json` to include relevant keywords for better package discovery.

### Deprecated

- Direct instantiation (e.g., `new Example()`) **will be removed in v4.0.0**.
  Please use `Example::fromArray()` or `Example::fromJson()` instead.
- `#[DataTransferObject]`, `#[ValueObject]`, and `#[Entity]` **will be deprecated in v4.0.0**.
  Use the **class-based API** (`Objects\*`) going forward.

## [v3.0.3] - 2025-10-17

- 修復 PHP 8.2 取得反射屬性名稱失敗 bug

## [v3.0.2] - 2025-10-17

- 重構核心邏輯優化性能
- 提高代碼可讀性

## [v3.0.1] - 2025-10-16

- 新增測試情境
- 簡化底層代碼

## [v3.0.0] - 2025-10-13

### ⚠️ 重大變更

- 最低 PHP 支援版本提升從 8.0 提高至 8.1

### 🐞 修正

- 修復 with 寫入 null 時 valueDecide 仍返回原始值的問題

### ✨ 功能改進

- 移除 construct Enum 檢查邏輯，移至 valueDecide
- 優化 walkProperties 物件判斷條件
- 新增多層繼承屬性鏈巡覽與宣告層級過濾機制
- 擴展 Enum 支援度：現允許以 Enum 實例、key、value 方式傳入

### 📜 測試

- 新增 defaultBench，涵蓋 Basic、Advanced、Enum 相關性能場景
- 新增 phpbench 設定檔，統一自動載入與輸出格式
- 確立核心建構、序列化、巢狀結構與 with 操作效能基線
- 新增 Basic、Advanced、Initialized、Enum 等測試物件
- 建立完整測試（覆蓋率 100%），覆蓋所有核心行為與例外情境

### 🧰 開發與持續整合

- 新增 phpunit 自動化測試及產出報告 #範圍涵蓋 PHP 8.1 ~ 8.4
- 新增 FOSSA 相依與安全性檢測
- 新增 SONAR 品質檢測

### 🔧 重構

- 簡化 toArray 代碼結構
- 提升整體可讀性

## [v2.4.5] - 2025-09-12

### 🐞 修正

- 修復 `toArray()` 時將 0/null/false/'' 等值誤判為不存在並捨棄狀況。

## [v2.4.4] - 2025-09-10

### 🐞 修正

- 修復 `toArray()` 判斷順序錯誤，導致包含 UnionType 屬性的物件使用 `toArray()` 時會一律拋錯狀況。

## [v2.4.3] - 2025-09-03

- 調整專案描述

## [v2.4.2] - 2025-09-03

### 🐞 修正

- 修復 `?array` 傳入 `[]` 時，toArray() 輸出遺失該屬性問題(#2)。

## [v2.4.1] - 2025-09-02

### 🐞 修正

- 修復 `#[ArrayOf]` 導致 nullable property 傳入 `null` 或參數不存在時拋出錯誤；現已正確返回 `null`。

### ✨ 功能改進

- ArrayOf 判斷流程更明確：先行處理 nullable 條件再進行陣列元素轉換，降低不必要的 `array_map` 呼叫。

## [v2.4.0] - 2025-09-01

### 🎉 新功能

- `#[ArrayOf]` 現在支援 Enum 類型：可傳入 enum case 名稱或其底層 scalar 值，自動解析為對應 case。

## [v2.3.2] - 2025-08-31

### 🐞 修正

- 修正 UnionType 未嘗試所有型別就直接拋錯的問題，現在會逐一嘗試所有子型別後再回報錯誤

## [v2.3.1] - 2025-08-18

- **修復 PHP 8.1 ~ 8.3 子類無法初始化父類 readonly 屬性導致非預期的繼承意外**

## [v2.3.0] - 2025-08-07

### 🎉 新功能

- **強化 `#[ArrayOf]` 標註驗證**：
  - 新增類型檢查，確保指定的類別是 ImmutableBase 的子類
  - 支援傳入已實例化的物件或陣列資料
  - 優化錯誤訊息提供更清楚的指引

### ✨ 功能改進

- **增強 `toArray()` 方法**：
  - 優化陣列處理邏輯，支援物件陣列的遞迴序列化
  - 自動處理 ArrayOf 標註的物件陣列輸出
- **強制架構模式標註**：
  - 所有 ImmutableBase 子類必須使用 `#[DataTransferObject]`、`#[ValueObject]` 或 `#[Entity]` 其中之一
  - 更嚴格的屬性可見性檢查

### 🗑️ 正式移除

- **移除已棄用標註**：
  - `#[Relaxed]` - 已完全移除
  - `#[Expose]` - 已完全移除
  - `#[Reason]` - 已完全移除

### 🔧 重大變更

- **Breaking Change**: 所有子類現在必須使用架構模式標註
- **Breaking Change**: ValueObject 和 Entity 新增支援 protected 屬性
- **Breaking Change**: 移除所有舊版標註

### 📚 範例

```php
#[DataTransferObject]
class OrderDTO extends ImmutableBase
{
    #[ArrayOf(OrderItemDTO::class)]
    public readonly array $items;
}

// 支援混合輸入
$order = new OrderDTO([
    'items' => [
        ['name' => 'Product A', 'price' => 100],  // 陣列會自動轉換
        new OrderItemDTO(['name' => 'Product B', 'price' => 200])  // 已實例化也可接受
    ]
]);
```

## [v2.2.0] - 2025-08-01

### 🎉 新功能

- **新增 `#[ArrayOf]` 標註**：
  - 支援陣列屬性的自動實例化
  - 可指定陣列元素的類別類型
  - 自動將陣列數據轉換為指定類別的實例
  - 提供錯誤驗證，確保類別名稱不為空

### 📝 更新

- **調整廢棄時程**：
  - `#[Relaxed]` - 廢棄時程延後至 v2.3.0
  - `#[Expose]` - 廢棄時程延後至 v2.3.0

### 📚 範例

```php
#[DataTransferObject]
class UserListDTO extends ImmutableBase
{
    #[ArrayOf(UserDTO::class)]
    public readonly array $users;
}

// 使用方式
$userList = new UserListDTO([
    'users' => [
        ['name' => 'Alice', 'age' => 30],
        ['name' => 'Bob', 'age' => 25]
    ]
]);
// 自動將每個陣列元素轉換為 UserDTO 實例
```

---

## [v2.1.0] - 2025-08-01

### 🎉 新功能

- **新增架構模式標註**：
  - `#[DataTransferObject]` - 資料傳輸物件，要求所有屬性為 public readonly
  - `#[ValueObject]` - 值物件，要求所有屬性為 private
  - `#[Entity]` - 實體物件，要求所有屬性為 private

### ✨ 功能改進

- **增強屬性訪問控制**：
  - 根據架構模式自動驗證屬性可見性
  - DataTransferObject 強制 public readonly 屬性
  - ValueObject 和 Entity 強制 private 屬性
- **改善 Union Type 支援**：
  - 優化複合型別的處理邏輯
  - 改進型別驗證錯誤訊息

### 🗑️ 即將棄用標註

- `#[Relaxed]` - 標記為 @deprecated v2.3.0
- `#[Expose]` - 標記為 @deprecated v2.3.0

### 📚 範例

```php
// DataTransferObject 模式
#[DataTransferObject]
class UserDto extends ImmutableBase
{
    public readonly string $name;
    public readonly int $age;
}

// ValueObject 模式
#[ValueObject]
class Money extends ImmutableBase
{
    private int $amount;
    private string $currency;
}

// Entity 模式
#[Entity]
class User extends ImmutableBase
{
    private string $id;
    private string $email;
}
```

---

## [v2.0.0] - 2025-07-20

### 🎉 新功能

- **新增屬性標註系統**：
  - `#[Relaxed]` - 鬆散模式，不強制要求填寫 `#[Reason]`
  - `#[Expose]` - 標記可被 `toArray()` 輸出的屬性
  - `#[Reason]` - 屬性非 private 時強制使用此標註說明設計原因

### ✨ 功能改進

- **優化 `with()` 方法**：
  - 現已支援嵌套 ImmutableBase 物件的部分更新
  - 使用 Reflection 直接處理屬性，不再依賴 `toArray()`
  - 支援對嵌套物件進行遞迴 `with()` 操作

### 🔧 重構

- **屬性管理強化**：
  - 移除 `$lock` 屬性和 `HIDDEN` 常數機制
  - 新增屬性訪問控制檢查（禁止非 readonly 的 public 屬性）
  - 新增 `isRelaxed()` 方法檢查類別是否為鬆散模式

### 🔄 API 變更

- **`with()` 方法**：現在支援嵌套物件的部分更新
- **`toArray()` 方法**：移除 `$lock` 檢查，簡化邏輯
- **屬性管理**：引入新的屬性標註系統取代舊的隱藏機制

### 📚 範例

```php
// 現在支援嵌套更新
$user = $user->with([
    'profile' => [
        'address' => [
            'city' => '新城市'
        ]
    ]
]);
```

---

## [v1.1.0] - 之前版本

- 實現 `toArray()` 功能

## [v1.04] - 之前版本

- 修復 `toArray()` 相關問題

## [v1.0.3] - 之前版本

- 重構建構子

## [v1.0.2] - 之前版本

- 重構建構子、備註和 `toArray()`

## [v1.0.1] - 之前版本

- 重構 namespace

## [v1.0.0] - 之前版本

- 初始版本
