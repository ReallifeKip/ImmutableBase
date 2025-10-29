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

一個專為 **不可變物件（Immutable Object）** 設計的抽象基底類別，適用於 **DTO（Data Transfer Object）**、**VO（Value Object）** 等需要「一次初始化、不可更改」的場景。

強調資料的**不可變性（Immutability）**、**類型安全（Type Safety）**，並可透過 API 快速建構不可變物件。

## 說明

1. 透過靜態建構函式建構物件，ImmutableBase 將掃描傳入參數的 key, value 進行建構並返回實例
2. 進行傳入參數掃描時，若發現 value 與宣告屬性型別不符，將拋出包含物件/屬性名稱的詳細例外說明
3. 型別接受所有 Builtin 類型、Enum、實例及 Union Type
4. 若物件屬性宣告型別同為 ImmutableBase 的子類，允許傳入符合宣告結構的陣列、物件進行自動實例

## 安裝

```bash
composer require reallifekip/immutable-base
```

## 範例

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

## 測試

### 單元測試

```bash
vendor/bin/phpunit tests
```

### 效能測試

```bash
vendor/bin/phpbench run
```

## 設計物件

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

### 建構物件 - fromArray(), fromJson()

> 傳入參數掃描時，若發現參數內容非物件宣告的屬性，該參數將被自動忽略而不會存在於返回的實例。

```php
$user = User::fromArray([
    'name' => 'Kip',
    'age' => 18
]);
```

```php
$user = Money::fromJson('{"value": 1000}');
```

### 修改屬性 - with()

> ⚠️ 注意：非修改原始物件，而是基於原始物件進行部分修改後返回 `新實例`，採用此設計的原因及底層原理請參考 [Objects and references](https://www.php.net/manual/en/language.oop5.references.php)。<br>
> ⚠️ 注意：當 with() 指定修改 #[ArrayOf] 屬性時會直接重建陣列。<br>
> 傳入參數掃描時，若發現參數內容非物件宣告的屬性，該參數將被自動忽略而不會存在於返回的實例。

```php
// 基礎屬性更新
$newUser = $user->with([
    'name' => 'someone'
]);

// 嵌套物件部分更新
$userWithNewAddress = $user->with([
    'profile' => [
        'address' => '台北市'
    ]
]);
```

### 輸出陣列 - toArray()

```php
// ['name' => 'Kip', 'age' => 18]
$user->toArray();
```

## 架構模式標註

### `#[DataTransferObject]` - 資料傳輸物件

> ⚠️ 即將於 v4.0.0 廢棄，新用法請參考 [架構模式繼承](#架構模式繼承)。

所有屬性必須為 `public readonly`，適用於跨層傳輸資料：

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

### `#[ValueObject]` - 值物件

> ⚠️ 即將於 v4.0.0 廢棄，新用法請參考 [架構模式繼承](#架構模式繼承)。

所有屬性必須為 `private`，適用於領域驅動設計中的值物件：

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

### `#[Entity]` - 實體物件

> ⚠️ 即將於 v4.0.0 廢棄，新用法請參考 [架構模式繼承](#架構模式繼承)。

所有屬性必須為 `private`，適用於領域驅動設計中的實體：

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

### `#[ArrayOf]` - 陣列自動實例化

> ⚠️ 注意：當 with() 指定修改 #[ArrayOf] 屬性時會直接重建陣列。

指定陣列屬性為 `實例物件陣列` ，將傳入參數對應 `實例化物件陣列` 的內容全部轉換為指定類的實例，

接受符合指定類所需結構的 `Json 字串`, `陣列`, `已實例的指定類`。

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
        // 四種方法都接受
        ['name' => 'Alice', 'age' => 18],
        '{"name": "Bob", "age": 19}',
        UserDTO::fromArray(['name' => 'Carl', 'age' => 20]),
        UserDTO::fromJson('{"name": "Dave", "age": 21}')
    ]
]);
```

## 架構模式繼承

### `DataTransferObject` - 資料傳輸物件

所有屬性必須為 `public readonly`，適用於跨層傳輸資料：

```php
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

class UserDTO extends DataTransferObject
{
    public readonly string $name;
    public readonly int $age;
    public readonly string $email;
}
```

### `ValueObject` - 值物件

所有屬性必須為 `private readonly`，適用於領域驅動設計中的值物件：

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

## ⚠️ 注意事項

1. **屬性型別**：必須宣告屬性型別，且不允許為 mixed，需明確宣告。
2. **Enum**：屬性型別為 Enum 時，建構過程會檢查參數是否符合 case 或 value **並且返回 Enum**，若希望取得文字應使用 string。

## 授權 License

本套件使用 [MIT License](https://opensource.org/license/mit)

## 開發者資訊

由 [Kip](mailto:bill402099@gmail.com) 開發與維護，適用於 Laravel、DDD、Hexagonal Architecture 等架構中 Immutable DTO/VO 實作需求。

---

如果有任何建議或發現錯誤，歡迎開 PR 或提出 Issue
