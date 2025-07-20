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
- ✅ **屬性標註系統：`#[Expose]`、`#[Reason]`、`#[Relaxed]`**
- ✅ **屬性訪問控制與設計原因強制說明**

---

## 使用方式

### 1. 建立你的不可變物件類別

```php
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\Expose;

final class UserDTO extends ImmutableBase
{
    #[Expose]
    private string $name;

    #[Expose]
    private int $age;

    #[Expose]
    private ?ProfileDTO $profile = null;
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

## 屬性標註系統

### `#[Expose]` - 輸出控制

標記可被 `toArray()` 和 `jsonSerialize()` 輸出的屬性：

```php
class UserDTO extends ImmutableBase
{
    #[Expose]
    private string $name;        // 會被輸出

    private string $password;    // 不會被輸出
}
```

### `#[Reason]` - 設計原因說明

當屬性不是 `private` 時，必須使用此標註說明設計原因：

```php
class UserDTO extends ImmutableBase
{
    #[Expose]
    #[Reason('需要被子類別存取')]
    protected string $id;

    #[Expose]
    public readonly string $email;  // readonly 屬性可以是 public
}
```

### `#[Relaxed]` - 鬆散模式

標記在 class 上，允許不強制要求 `#[Reason]` 標註：

```php
#[Relaxed]
class SimpleVO extends ImmutableBase
{
    #[Expose]
    protected string $value;  // 在鬆散模式下不需要 #[Reason]
}
```

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
#[Expose]
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

- 透過 Reflection 自動導出所有標記 `#[Expose]` 的屬性
- 支援嵌套 ImmutableBase 物件的遞迴序列化
- `json_encode()` 等同於 `toArray()` 的輸出

---

## 注意事項

1. **型別宣告為必要**：未宣告型別會導致 Reflection 錯誤
2. **不支援可變屬性**：務必遵守 Immutable 設計原則
3. **不支援 constructor injection**：請以 `$data` array 傳入
4. **屬性標註規則**：
   - 使用 `#[Expose]` 標記需要輸出的屬性
   - 非 private 屬性需要 `#[Reason]` 說明或使用 `#[Relaxed]` 模式
   - 禁止非 readonly 的 public 屬性

---

## 完整範例

```php
<?php

use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\Expose;
use ReallifeKip\ImmutableBase\Reason;

class AddressDTO extends ImmutableBase
{
    #[Expose]
    private string $city;

    #[Expose]
    private string $zipCode;
}

class ProfileDTO extends ImmutableBase
{
    #[Expose]
    private AddressDTO $address;

    #[Expose]
    private ?string $phone = null;
}

class UserDTO extends ImmutableBase
{
    #[Expose]
    private string $name;

    #[Expose]
    private int $age;

    #[Expose]
    private ?ProfileDTO $profile = null;

    #[Expose]
    #[Reason('需要被子類別訪問以實作特殊邏輯')]
    protected string $id;
}

// 初始化
$user = new UserDTO([
    'id' => 'user_123',
    'name' => 'Alice',
    'age' => 30,
    'profile' => [
        'address' => [
            'city' => '台北市',
            'zipCode' => '10001'
        ],
        'phone' => '0912-345-678'
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
