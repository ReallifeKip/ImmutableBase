# ImmutableBase

一個專為 **不可變物件（Immutable Object）** 設計的抽象基底類別，適用於 **DTO（Data Transfer Object）**、**VO（Value Object）** 等需要「一次初始化、不可更改」的場景。

此類別強調資料的**不可變性（Immutability）**、**類型安全（Type Safety）**，並可透過建構式快速初始化、內建型別自動轉換機制、淺層複製 (`with`) 以及自動序列化支援 (`toArray`, `jsonSerialize`)。

---

## 特性總覽

- ✅ **Constructor 自動對應屬性並進行型別驗證**
- ✅ **支援 `readonly` 行為邏輯（非語法）**
- ✅ **支援 `ReflectionUnionType` 型別解析**
- ✅ **遞迴初始化巢狀 ImmutableBase 子類**
- ✅ **支援 `with([...])` 複製模式**
- ✅ **自動 `toArray()` 與 `jsonSerialize()`**
- ✅ **隱藏內部欄位（如 lock, events）避免序列化污染**

---

## 使用方式

### 1. 建立你的不可變物件類別

```php
use DDD\Shared\Domain\ImmutableBase;

final class UserDTO extends ImmutableBase
{
    public string $name;
    public int $age;
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
$olderUser = $user->with(['age' => 31]);
```

### 4. 輸出為陣列或 JSON

```php
$user->toArray();         // ['name' => 'Alice', 'age' => 30]
json_encode($user);       // '{"name":"Alice","age":30}'
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
public AddressDTO $address;
```

則若 `$data['address']` 為 array，會自動遞迴初始化：

```php
new AddressDTO([...])
```

---

### 不可變與 `with()` 設計

- `with(array $data)`：建立 **修改後的新實體**，不影響原始物件。
- 內部以 `$this->lock` 控制是否允許讀寫屬性，避免意外覆蓋。

---

### 自動 `toArray()` 與 `jsonSerialize()`

- 透過 Reflection 自動導出所有屬性（排除 `HIDDEN` 名單）
- `json_encode()` 等同於 `toArray()` 的輸出

---

## 注意事項

1. **型別宣告為必要**：未宣告型別會導致 Reflection 錯誤。
2. **不支援可變屬性**：務必遵守 Immutable 設計原則。
3. **不支援 constructor injection**：請以 `$data` array 傳入。

---

## 授權 License

本套件使用 [MIT License](https://opensource.org/license/mit/)

---

## 開發者資訊

由 [Kip](mailto:bill402099@gmail.com) 開發與維護，適用於 Laravel、DDD、Hexagonal Architecture 等架構中 Immutable DTO/VO 實作需求。

---

如果有任何建議或發現錯誤，歡迎開 PR 或提出 Issue
