# ImmutableBase

一個專為 **不可變物件（Immutable Object）** 設計的抽象基底類別，適用於 **DTO（Data Transfer Object）**、**VO（Value Object）** 等需要「一次初始化、不可更改」的場景。

此類別強調資料的**不可變性（Immutability）**、**類型安全（Type Safety）**，並可透過建構式快速初始化、內建型別自動轉換機制、淺層複製 (`with`) 以及自動序列化支援 (`toArray`, `jsonSerialize`)。

---

## 特性總覽

- ✅ **Constructor 自動對應屬性並進行型別驗證**
- ✅ **支援 `readonly` 行為邏輯（非語法）**
- ✅ **支援 `ReflectionUnionType` 型別解析**
- ✅ **遞迴初始化巢狀 ImmutableBase 子類**
- ✅ **支援 `with([...])` 複製模式，包含嵌套物件更新**
- ✅ **自動 `toArray()` 與 `jsonSerialize()`**
- ✅ **架構模式標註：`#[DataTransferObject]`、`#[ValueObject]`、`#[Entity]` (必須)**
- ✅ **陣列自動實例化：`#[ArrayOf]` 含類型驗證**
- ✅ **強制屬性訪問控制與型別安全**

---

## 使用方式

### 1. 建立你的不可變物件類別

```php
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\DataTransferObject;

#[DataTransferObject]
final class UserDTO extends ImmutableBase
{
    public readonly string $name;
    public readonly int $age;
    public readonly ?ProfileDTO $profile = null;
}
```

### 2. 初始化（建構子將自動賦值）

```php
$user = new UserDTO([
    'name' => 'Alice',
    'age' => 30,
]);
```

### 3. 使用 `with()` 建立修改版

```php
// 簡單屬性更新
$olderUser = $user->with(['age' => 31]);

// 嵌套物件部分更新（v2.0.0 新功能）
$userWithNewAddress = $user->with([
    'profile' => [
        'address' => [
            'city' => '台北市',
            'zipCode' => '10001'
        ]
    ]
]);
```

### 4. 輸出為陣列或 JSON

```php
$user->toArray();         // ['name' => 'Alice', 'age' => 30]
json_encode($user);       // '{"name":"Alice","age":30}'
```

---

## 架構模式標註（必須使用）

> ⚠️ **重要**：v2.3.0 開始，所有 ImmutableBase 子類都**必須**使用以下其中一個標註。

### `#[DataTransferObject]` - 資料傳輸物件

所有屬性必須為 `public readonly`，適用於跨層傳輸資料：

```php
use ReallifeKip\ImmutableBase\DataTransferObject;

#[DataTransferObject]
class UserDTO extends ImmutableBase
{
    public readonly string $name;
    public readonly int $age;
    public readonly string $email;
}
```

### `#[ValueObject]` - 值物件

所有屬性必須為 `private`，適用於領域驅動設計中的值物件：

```php
use ReallifeKip\ImmutableBase\ValueObject;

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

---

### `#[ArrayOf]` - 陣列自動實例化

指定陣列屬性中每個元素的類別，自動將陣列數據轉換為指定類別的實例。
v2.3.0 新增嚴格類型驗證：

```php
use ReallifeKip\ImmutableBase\ArrayOf;
use ReallifeKip\ImmutableBase\DataTransferObject;

#[DataTransferObject]
class UserListDTO extends ImmutableBase
{
    #[ArrayOf(UserDTO::class)]  // 必須是 ImmutableBase 子類
    public readonly array $users;

    #[ArrayOf(TagDTO::class)]
    public readonly array $tags;
}

// 使用方式
$userList = new UserListDTO([
    'users' => [
        ['name' => 'Alice', 'age' => 30],
        ['name' => 'Bob', 'age' => 25]
    ],
    'tags' => [
        ['name' => 'developer'],
        ['name' => 'senior']
    ]
]);

// users 和 tags 陣列中的每個元素都會自動轉換為對應的 DTO 實例
````

---

## 實作原則與行為說明

### Constructor 行為

建構子將透過 Reflection 掃描所有屬性，並依照 `$data` 自動賦值，型別不合將主動拋出例外。

```php
throw new Exception("age 型別錯誤，期望：int，傳入：string。");
```

---

### 支援 Union Type（`int|string` 等）

```php
public int|string $value;
```

如 `$data['value']` 為整數或字串皆可；若皆不符合，將拋出型別錯誤。

---

### 巢狀 ImmutableBase 支援

若某屬性為另一個 ImmutableBase 子類：

```php
// DataTransferObject 模式
public readonly AddressDTO $address;

// 或 ValueObject/Entity 模式
private AddressDTO $address;
```

則若 `$data['address']` 為 array，會自動遞迴初始化：

```php
new AddressDTO([...])
```

**v2.0.0 新功能**：`with()` 方法現在支援嵌套更新：

```php
$user = $user->with([
    'address' => [
        'city' => '新城市'  // 只更新地址的城市，其他屬性保持不變
    ]
]);
```

---

### 不可變與 `with()` 設計

- `with(array $data)`：建立 **修改後的新實體**，不影響原始物件
- 支援嵌套物件的部分更新，會遞迴調用嵌套物件的 `with()` 方法
- 使用 Reflection 直接處理屬性，確保型別安全

---

### 自動 `toArray()` 與 `jsonSerialize()`

- 支援嵌套 ImmutableBase 物件的遞迴序列化
- `json_encode()` 等同於 `toArray()` 的輸出

---

## 注意事項

1. **型別宣告為必要**：未宣告型別會導致 Reflection 錯誤
2. **不支援可變屬性**：務必遵守 Immutable 設計原則
3. **不支援 constructor injection**：請以 `$data` array 傳入
4. **屬性標註規則**：
   - **推薦**：使用架構模式標註 `#[DataTransferObject]`、`#[ValueObject]`、`#[Entity]`
   - **陣列處理**：使用 `#[ArrayOf(ClassName::class)]` 進行陣列自動實例化
   - DataTransferObject 要求所有屬性為 `public readonly`
   - ValueObject 和 Entity 要求所有屬性為 `private`

---

## 完整範例

```php
<?php

use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\DataTransferObject;
use ReallifeKip\ImmutableBase\ValueObject;
use ReallifeKip\ImmutableBase\ArrayOf;

#[DataTransferObject]
class AddressDTO extends ImmutableBase
{
    public readonly string $city;
    public readonly string $zipCode;
}

#[DataTransferObject]
class TagDTO extends ImmutableBase
{
    public readonly string $name;
    public readonly ?string $color = null;
}

#[DataTransferObject]
class ProfileDTO extends ImmutableBase
{
    public readonly AddressDTO $address;
    public readonly ?string $phone = null;

    #[ArrayOf(TagDTO::class)]
    public readonly array $tags;
}

#[ValueObject]
class UserId extends ImmutableBase
{
    private string $value;

    public function getValue(): string
    {
        return $this->value;
    }
}

#[DataTransferObject]
class UserDTO extends ImmutableBase
{
    public readonly string $name;
    public readonly int $age;
    public readonly ?ProfileDTO $profile = null;
    public readonly UserId $id;
}

// 初始化
$user = new UserDTO([
    'id' => ['value' => 'user_123'],
    'name' => 'Alice',
    'age' => 30,
    'profile' => [
        'address' => [
            'city' => '台北市',
            'zipCode' => '10001'
        ],
        'phone' => '0912-345-678',
        'tags' => [
            ['name' => 'developer', 'color' => 'blue'],
            ['name' => 'senior', 'color' => 'gold']
        ]
    ]
]);

// 嵌套更新
$relocatedUser = $user->with([
    'profile' => [
        'address' => [
            'city' => '高雄市',
            'zipCode' => '80001'
        ]
    ]
]);

// 輸出
echo json_encode($relocatedUser, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
```

---

## 授權 License

本套件使用 [MIT License](https://opensource.org/license/mit/)

---

## 開發者資訊

由 [Kip](mailto:bill402099@gmail.com) 開發與維護，適用於 Laravel、DDD、Hexagonal Architecture 等架構中 Immutable DTO/VO 實作需求。

---

如果有任何建議或發現錯誤，歡迎開 PR 或提出 Issue
