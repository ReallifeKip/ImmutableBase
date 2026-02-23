# ImmutableBase

> 🌐 其他語言版本：[English](./README.md)

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

一個用於建構**不可變資料物件**的 PHP 函式庫，具備嚴格的型別驗證機制，適用於 **DTO（資料傳輸物件）**、**VO（值物件）** 及 **SVO（單值物件）**。

強調**不可變性**、**型別安全**及**深層結構操作**，包含巢狀建構、點路徑變更、以及遞迴的相等性比較。

---

## 為什麼選擇 ImmutableBase？

### 🚀高效率的自動建構
```php
// 🥳 ImmutableBase 不需要撰寫建構子，直接傳入陣列或 JSON 資料即可建構，且傳入的資料 key 無順序限制，可自由排序。
readonly class Order extends DataTransferObject
{
    public string $date;
    public string $time;
}
Order::fromArray($data); // $data 可以是陣列、JSON

// 🫤 一般常見做法需要重複撰寫建構子，也常因順序不正確而無法建構，且無法直接使用外部傳入資料進行建構。
class Order extends DataTransferObject
{
    public function __construct(
        public readonly string $date,
        public readonly string $time
    ){}
}
new Order('2026-01-01', '00:00:00', ...); // 無法直接接受外部傳入的陣列或 JSON 資料，且若未明確指定參數名稱則有順序錯亂的風險
```

### 🔧靈活便利的深層更新
支援直接指定物件深層路徑，拒絕俄羅斯套娃。
```php
// 🥳 ImmutableBase 靈活且精準。
$order->with(['items.0.count' => 1]); // 直接指定物件陣列索引並更改 count

// 🫤 一般常見做法複雜且無法保障原物件陣列的其他內容。
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

### 🔎直觀易讀的錯誤追蹤
```php
// 🥳 ImmutableBase 清楚明瞭指出錯誤位置。
SomeException: Order > $profile > 0 > $count > {錯誤訊息}

// 🫤 一般常見做法只有模糊或難以追蹤的基礎訊息。
SomeException: {錯誤訊息}
```

### ⚡閃電般的啟動速度

🥳 ImmutableBase 可以透過 `php cacher` 掃瞄並建置所有 ImmutableBase 物件快取檔案 `cache.php`，極致優化速度。
🫤 一般常見做法可能根本不存在快取機制，每次運行都需要為反射付出大量時間成本。

### 🔗自動且可控的繼承驗證鏈

🥳 ImmutableBase 的 `ValueObject`、`SingleValueObject` 可選設計 `validate(): bool`，使物件在建構初期就自動由繼承鏈最上層開始向下歷遍 `validate(): bool` 進行驗證，且可透過 `#[ValidateFromSelf]` 反轉驗證方向。
🫤 一般常見做法幾乎無自動驗證鏈機制及概念，只能透過建構子自己設計。

### 📃文件即代碼，代碼即文件

🥳 ImmutableBase 可以透過 `php writer` 對專案進行 ImmutableBase 子類物件掃描，力求避免文件與代碼不一致、需要花費額外人力的窘境，快速產出 Mermaid、Markdown 等技術文件。
🫤 一般常見做法無法保障代碼與文件一致。

### 🆓高相容、輕量化、0 依賴

🥳 ImmutableBase 使用時，若無產出文件、產出快取、單元或效能測試的需求，**不需要額外安裝任何依賴，不依附於任何框架**。
🫤 一般常見做法若依賴特定套件或框架則難以快速解藕。

### 📦可控的資料輸出

```php
// 🥳 ImmutableBase 可以透過 `#[KeepOnNull]`、`#[SkipOnNull]` 標籤精準控制屬性為空時是否輸出，不需親自過濾。
#[SkipOnNull]
readonly class User extends ValueObject
{
    #[KeepOnNull]
    public ?string $name;
    public ?int $age;
}
User::fromArray([])->toArray(); // ["name" => null]

// 🫤 一般常見做法通常需要親自手動過濾 null。
readonly class User extends ValueObject
{
    public ?string $name;
    public ?int $age;
}

$user = new User();
$data = get_object_vars($user);
$data['name'] ??= null;
```
### ⭐類 TypeScript 的型別縮窄

```php
// 🥳 ImmutableBase 雖然約束了 `SingleValueObject` 必須宣告 $value，但允許靈活、自由定義該屬性型別。（透過 interface + hooked property 設計隔代約束，零反射開銷）
readonly class ValidAge extends SingleValueObject
{
    public int $value; // 與物件名稱語義相符的型別
}

// 🫤 一般常見做法交由 parent 宣告型別且無法自訂，parent 通常宣告為 mixed 或複雜、太寬的聯型，難以設計 SVO。
class ValidAge extends SingleValueObject
{
    public string $value; // parent 約束了型別，無法改變，與物件名稱語義不符
}
```

---

## 安裝

```bash
composer require reallifekip/immutable-base
```

需要 PHP 8.4 以上。

---

## 快速範例

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
        ['name' => 'ReallifeKip', 'age' => 18],           // 陣列
        '{"name": "Bob", "age": 19}',                     // JSON 字串
        User::fromArray(['name' => 'Carl', 'age' => 20]), // 實例 fromArray
        User::fromJson('{"name": "Dave", "age": 21}'),    // 實例 fromJson
    ],
    'userCount' => 4,
]);
```

---

## 測試

```bash
# 單元測試
vendor/bin/phpunit tests

# 效能測試
vendor/bin/phpbench run
```

---

## 物件類型

### DataTransferObject（DTO）

傳輸、交互用的純資料結構，即便設計 `validate(): bool`，也不會在建構過程中觸發進行驗證。

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

### ValueObject（VO）

具語義的資料結構，可以透過設計函式 `validate(): bool` 在建構過程中自動驗證。

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

### SingleValueObject（SVO）

具語義的單一資料，可以透過設計函式 `validate(): bool` 在建構過程中自動驗證，此類及其子類物件 `validate()`、`from()`、`jsonSerialize()`、`__toString()`、`__invoke()` 僅對 `$value` 屬性生效。

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

echo $age;          // 18（透過 __toString，僅 $value 為字串時可用）
echo $age();        // 18（透過 __invoke）
echo $age->value;   // 18
```

---

## API

### 建構 - `fromArray()`、`fromJson()`

建構輸入的資料中，不屬於已宣告屬性的 key 會被靜默忽略（除非啟用[嚴格模式](#strict---嚴格模式)）。

```php
$user = User::fromArray(['name' => 'Kip', 'age' => 18]);
$user = User::fromJson('{"name": "Kip", "age": 18}');
```

### 建構 - `from()`（僅 SVO 可用）

```php
$age = ValidAge::from(18);
```

### 序列化 - `toArray()`、`toJson()`

```php
$user->toArray();  // ['name' => 'ReallifeKip', 'age' => 18]
$user->toJson();   // {"name":"ReallifeKip","age":18}
```

### 變更 - `with()`

更新指定屬性並回傳一個**新實例**，原始物件不會被修改，可接受陣列、物件或 JSON 字串。

```php
$newUser = $user->with(['name' => 'Kip']);
$newUser = $user->with('{"name": "Kip"}');
$newUser = $user->with((object) ['name' => 'Kip']);
```

**深層路徑語法** - 透過點記法、中括號記法或自訂分隔符更新巢狀屬性：

```php
// 點記法
$newSignUp = $signUp->with(['users.0.name' => 'Kip']);
// 中括號記法
$newSignUp = $signUp->with(['users[0].name' => 'Kip']);
// 自訂分隔符
$newSignUp = $signUp->with(['users/0/name' => 'Kip'], '/');
```

**SVO with()** - 直接替換封裝的值：

```php
$newAge = $age->with(20);
```

### 比較 - `equals()`

深層結構相等性比較。適用於所有 ImmutableBase 子類物件，比對對象的資料、結構、類需與自身完全相同，巢狀 ImmutableBase 物件及陣列會被遞迴比較。

```php
$a = User::fromArray(['name' => 'Kip', 'age' => 18]);
$b = User::fromArray(['name' => 'Kip', 'age' => 18]);
$c = User::fromArray(['name' => 'Kip', 'age' => 20]);

$a->equals($b);  // true - 相同資料，不同實例
$a->equals($c);  // false - age 不同
```

對 SVO 子類而言，直接比較封裝的 `$value`：

```php
$age1 = ValidAge::from(18);
$age2 = ValidAge::from(18);
$age3 = ValidAge::from(20);

$age1->equals($age2);  // true
$age1->equals($age3);  // false
```

---

## Attributes

### `#[ArrayOf]` - 型別陣列

將陣列屬性標記為 ImmutableBase 實例的型別集合。每個元素會自動從陣列、JSON 字串或已建構物件進行實例化，目標類必須是 DTO、VO 或 SVO 的子類。

```php
use ReallifeKip\ImmutableBase\Attributes\ArrayOf;

readonly class SignUpUsersDTO extends DataTransferObject
{
    #[ArrayOf(User::class)]
    public array $users;
    public int $userCount;
}
```

### `#[Strict]` - 嚴格模式

拒絕不存在於已宣告屬性的 key 資料輸入。

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

### `#[Lax]` - 寬鬆模式

使類無視嚴格模式約束，接受不存在於已宣告屬性的 key 資料輸入，權重高於 `#[Strict]`、`ImmutableBase::strict()`。

```php
use ReallifeKip\ImmutableBase\Attributes\Lax;

#[Lax]
readonly class User extends ValueObject
{
    public string $name;
    public ValidAge $age;
    // ...
}

User::fromArray(['name' => 'Kip', 'age' => 18, 'extra' => '...']); // 正常建構
```

### `#[SkipOnNull]` / `#[KeepOnNull]`

`#[SkipOnNull]` 使 `toArray()`、`toJson()` 輸出排除值為 null 的內容，可套用於類層級（影響所有屬性）或屬性層級（僅影響單一屬性）。
`#[KeepOnNull]` 僅可套用於屬性層級，無視 `#[SkipOnNull]` 效果，使該屬性即使為 null 仍輸出。
類未使用 `#[SkipOnNull]` 時，`toArray()`、`toJson()` 預設會輸出值為 null 的內容。

```php
use ReallifeKip\ImmutableBase\Attributes\SkipOnNull;
use ReallifeKip\ImmutableBase\Attributes\KeepOnNull;

#[SkipOnNull]
readonly class UserDTO extends DataTransferObject
{
    #[KeepOnNull]
    public ?string $name;      // 即使為 null 也保留在輸出中
    public ValidAge|null $age; // 為 null 時從輸出中排除
}

UserDTO::fromArray([])->toArray();
// ['name' => null]（age 被排除，name 因 KeepOnNull 保留）
```

### `#[Spec]` - 驗證鏈資訊

VO、SVO 的可選附加訊息，當 `validate()` 回傳 false 時，此訊息會包含在 `ValidationChainException` 中，使用者可透過 `$exception->getSpec()` 取得訊息內容。

```php
use ReallifeKip\ImmutableBase\Attributes\Spec;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\ValidationChainException;

#[Spec('年齡必須大於等於 18')]
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
    echo $e->getSpec(); // 年齡必須大於等於 18
}
```

### `#[ValidateFromSelf]` - 驗證鏈反轉

VO、SVO 驗證鏈預設由繼承鏈頂層向下驗證到當前類，套用 `#[ValidateFromSelf]` 後，驗證鏈將改為從當前類開始向上驗證。

---

## 設定

### `ImmutableBase::strict(bool $on)`

全域嚴格模式，啟用時效果等同於對所有 ImmutableBase 子類套用 `#[Strict]`。

```php
ImmutableBase::strict(true);
```

### `ImmutableBase::debug(?string $path)`

啟用除錯記錄，輸入資料中多餘的 key 將會被記錄至 `{$path}/ImmutableBaseDebugLog.log`，包含時間戳、堆疊追蹤及輸入內容，傳入 `null` 停用紀錄。

```php
ImmutableBase::debug(__DIR__); // 啟用除錯紀錄
ImmutableBase::debug(null);    // 停用除錯紀錄
```

### `ImmutableBase::loadCache(string $path)`

載入預先透過 `cacher` 產生的屬性元資料快取，用以跳過執行期反射掃描、加速啟動速度。

```php
ImmutableBase::loadCache(__DIR__ . '/cache.php');
```

---

## CLI 工具

### `cacher` - 元資料快取產生器

掃描指定目錄中的所有 ImmutableBase 子類，產生序列化的元資料快取檔案 `cache.php`，消除啟動時的反射開銷，需透過 `ImmutableBase::loadCache()` 載入快取。

```bash
php cacher
```

### `writer` - 文件產生器

為專案所有 ImmutableBase 子類物件產生文件，可產生 Mermaid 類別圖及 Markdown 屬性表。

```bash
php writer
```

---

## 錯誤處理

所有例外皆繼承自 `ImmutableBaseException`，依據錯誤性質分為兩大類、三大主題，巢狀建構錯誤會在訊息中包含完整的屬性路徑，如：`OrderDTO > $customer > $email > {錯誤訊息}`。

### LogicException - 設計錯誤

#### DefinitionException - 定義錯誤

類結構或 Attribute 配置有誤時拋出，屬於程式設計錯誤，通常在首次實例化進行反射掃描時觸發。

`InvalidPropertyTypeException` - 屬性宣告了不受支援的型別（如：`iterable`、`object`、非 ImmutableBase 子類或非 Enum 的類）。

`InvalidVisibilityException` - 屬性未宣告為 `public`。

`InvalidArrayOfTargetException` - `#[ArrayOf]` 指定的目標類不是 DTO、VO 或 SVO 的子類。

`InvalidArrayOfUsageException` - `#[ArrayOf]` 套用在非 `array` 型別的屬性上。

`InvalidSpecException` - `#[Spec]` 未提供引數或引數為空。

`InvalidCompareTargetException` - `equals()` 的比較對象與自身類不同，或陣列中包含無法比較的非 ImmutableBase 物件。

`InvalidWithPathException` - `with()` 的深層路徑指向純量屬性，無法向下展開。

`DebugLogDirectoryInvalidException` - `ImmutableBase::debug()` 指定的路徑不存在、不可寫或不是目錄。

### RuntimeException - 執行錯誤

#### InitializationException - 初始化錯誤

建構（`fromArray`、`fromJson`）或變更（`with`）時，輸入資料不符合宣告的型別約束時拋出。

`RequiredValueException` - 非 nullable 屬性收到 null 或在輸入資料中缺失。

`InvalidValueException` - 值的型別與宣告的屬性型別不符。

`InvalidEnumValueException` - 值無法解析為目標 Enum 的任何 case，名稱查找及 `tryFrom()` 皆失敗。

`InvalidJsonException` - JSON 字串解碼失敗。

#### ValidationException - 驗證錯誤

領域驗證失敗或結構約束違規時拋出。

`ValidationChainException` - VO、SVO 的 `validate()` 回傳 false。若類套用了 `#[Spec]`，可透過 `$exception->getSpec()` 取得自定義訊息。

`StrictViolationException` - 嚴格模式下，輸入資料包含未宣告為屬性的 key。

`InvalidArrayOfItemException` - `#[ArrayOf]` 陣列中的某個元素無法解析為目標類的實例。

---

## 已廢棄

### Attributes

`#[DataTransferObject]`, `#[ValueObject]`, `#[Entity]`

---

## 注意事項

1. 此體系子物件所有屬性必須為 `public readonly`（掃描時強制檢查）。
2. 此體系子物件所有屬性型別禁止設為：`iterable`、`object`、非 ImmutableBase 子類或非 Enum 的類，如：`DateTime`、`Closure`。
3. Enum 屬性接受 case 名稱（`"HIGH"`）或 backed 值（`3`），解析後的屬性值始終為 Enum 實例。
4. 支援 `mixed` 型別，但值不會被進行驗證。

---

## 授權 License

本套件使用 [MIT License](https://opensource.org/license/mit)。

---

## 開發者資訊

由 [Kip](mailto:bill402099@gmail.com) 開發與維護，適用於所有 PHP 專案。

---

如果有任何建議或發現錯誤，歡迎開 PR 或提出 Issue。
