# 從 4.x 升級到 5.0

5.0 更名了兩個 attribute，移除了 4.x 中屬於內部或已 deprecated 的 API，並包含一些行為修正：部分 4.x 接受的輸入現在會被拒絕，部分錯誤的回報方式也不同了。PHP 版本需求不變（`^8.4`），4.x 的 `ib-cacher` 產生的快取也能繼續使用。

大多數專案只需要做第 1 步。第 2–4 步列出要搜尋的東西；其餘依照影響可能性由高到低說明行為變更。

## 1. 更名 `#[InputKeyTo]` / `#[OutputKeyTo]`

| 4.x | 5.0 |
|---|---|
| `#[InputKeyTo(KeyCase::…)]` | `#[InputCaseTo(KeyCase::…)]` |
| `#[OutputKeyTo(KeyCase::…)]` | `#[OutputCaseTo(KeyCase::…)]` |

行為完全不變。舊名稱已移除，沒有保留別名。仍使用舊名稱的類別或屬性，會在掃描時丟出 `RemovedAttributeException`，並指出該改成什麼；不會默默跳過轉換（ImmutableBase 不查找的 attribute，PHP 永遠不會解析它，所以若不檢查，舊名稱會被無聲忽略）。一次全部改名：

```bash
grep -rlE 'InputKeyTo|OutputKeyTo' src tests | xargs sed -i -e 's/InputKeyTo/InputCaseTo/g' -e 's/OutputKeyTo/OutputCaseTo/g'
# macOS: sed -i '' -e …
```

新名稱直接說明 attribute 轉換的是「key 的命名格式」，也和新增的指定確切名稱 attribute `#[InputKey]` / `#[OutputKey]` 明確區分。

4.x 的 `ib-cacher` 產生的快取裡，存的是由舊 attribute 算出的命名格式，所以從快取載入的類別在重新產生快取前都能正常運作；重新產生快取時，會回報任何漏改的名稱。

## 2. 移除的 API

- **`ImmutableBase::state()`** — 原本就標記為 `@internal` 且 deprecated。透過它重設或注入快取的測試，可以改用 `ReallifeKip\ImmutableBase\Internal\Metadata::state()`，回傳的是同一個 handle，但屬於內部 API，不提供支援。應用程式碼不應該需要用到任何一個。
- **`ImmutableBaseException::$depth` / `::$paths`** — 錯誤路徑改為存放在各個例外實例上之後就已 deprecated，沒有任何程式讀寫它們。請移除所有重設它們的程式碼。
- **`InvalidKeyCaseException` 改為繼承 `DefinitionException`**，和其他定義錯誤一致，不再繼承 `\InvalidArgumentException`。包在類別定義外的 `catch (\InvalidArgumentException)` 不會再接到它；請改為 catch `DefinitionException`（或 `ImmutableBaseException`）。

## 3. 非法輸入不再丟出 `TypeError`

所有非法輸入現在都會丟出 `ImmutableBaseException` 的子類別。請搜尋包在 `fromArray()`、`fromJson()`、`from()`、`with()` 外的 `catch (TypeError` / `catch (\TypeError`，這些分支不會再執行。以前會以 `TypeError` 形式逸出的情況：

- `SVO::from()` 收到錯誤的 scalar 型別、`null` 或物件 → `InvalidValueException` / `RequiredValueException`。
- `#[ArrayOf]` 收到非陣列，或 enum 元素收到 bool / float / null / 陣列 → `InvalidValueException` / `InvalidArrayOfItemException`。
- backed enum 收到另一種 backing 型別的值 → `InvalidEnumValueException`。

union 的解析也隨之改變：

- `null` 成員不再匹配任意陣列：`A|B|null` 收到既不是 `A` 也不是 `B` 的陣列時，現在會失敗，而不是把陣列原樣傳下去。
- `string|false`（以及其他 `true` / `false` 字面型別成員）現在接受該字面值；單獨的 `true` / `false` 型別會拒絕其他所有值。
- 不是 case 的 enum 常數（`const LIMIT = 10`）不再被解析成 case。case 別名（`const DEFAULT = self::Low`）仍可解析。
- union 或多型別 `#[ArrayOf]` 的成員不論因何種原因拒絕輸入（成員自身的 `#[Strict]`、格式錯誤的 JSON），現在都會改試下一個成員，而不是中止。

## 4. strict 模式套用到 `with()`

在 `#[Strict]` 或 `ImmutableBase::strict(true)` 下（除非有 `#[Lax]`），`with()` 遇到不屬於任何屬性的 key 時，現在會丟出 `StrictViolationException`，和建構時一致。非 strict 模式下這類 key 仍會被忽略。請檢查 strict 類別上類似 `with($request->all())` 的寫法。

## 5. `with()` 的解析方式和 `fromArray()` 完全一致

- 看起來像 JSON 的文字（`"[draft] hi"`、`'{"a":1}'`）在 `string` 和 SVO 屬性中會以文字儲存，不再被解碼。
- 給 `#[ArrayOf]` 屬性的 JSON 字串會產生有型別的元素，而不是一般陣列。
- 給一般 `array` 屬性的 JSON 字串仍會被解碼，和 4.x 相同。

## 6. `equals()`

- 含有 `null` 的陣列能正確比較（`[null]` 等於 `[null]`）。
- enum 的 `#[ArrayOf]` 可以比較，不再丟出例外。
- 不同 ImmutableBase 類別的元素，或物件對上 scalar，會比較為不相等，不再丟出例外。
- **`mixed` 屬性中的外部物件（例如 `DateTimeImmutable`）會丟出 `InvalidCompareTargetException`。** 4.x 會讀取它的 `->name`，可能把兩個不同的日期判為相等。兩邊是同一個實例時，視為相等。

## 7. `toArray()` / `toJson()` 遇到外部物件

`mixed` 或一般 `array` 屬性中的外部物件，以前會以「Call to undefined method …::toArray()」crash。現在 `JsonSerializable` 物件會透過 `jsonSerialize()` 序列化；其他物件會丟出 `InvalidSerializeTargetException`。

## 8. 錯誤訊息

`Class > $property > …` 前綴不再沾上先前失敗留下的殘留片段（例如 `validate()` 逸出非 ImmutableBase 例外之後，或 union 成員失敗之後）。若有解析錯誤訊息，前綴現在只描述真正失敗的路徑。部分 `#[ArrayOf]` 元素失敗時回報的例外類別也不同了：

- DTO / VO 元素收到格式錯誤的 JSON 字串 → `InvalidJsonException`（原為 `InvalidArrayOfItemException`），和同樣字串給屬性時一致。
- SVO 元素收到物件 → `InvalidArrayOfItemException`（原為 SVO 自身的 `InvalidValueException`）。
- enum 元素收到陣列 → `InvalidArrayOfItemException`（原為「undefined method」錯誤）。

## 9. `ib-writer` 輸出

- **TypeScript：** nullable 屬性宣告為 `name: T | null`，和 `toJson()` 的輸出一致。只有帶 `#[SkipOnNull]` 且沒有 `#[KeepOnNull]` 的屬性維持可選（`name?: T`）。依 4.x 型別定義編譯的前端程式碼，可能需要明確處理 `null`。
- **TypeScript：** 字串 enum 值會被跳脫，只透過 `#[ArrayOf]` 使用的 enum 也會被宣告，產生的檔案可以編譯。
- **Markdown / Mermaid：** 會顯示繼承的屬性、`#[ArrayOf]` 元素型別與 union 關聯；布林預設值顯示為 `true` / `false`；有預設值的屬性不會標為必填。
- `--format` / `--output` 可在無互動提示的情況下執行（CI、composer scripts）。

## 10. 快取

4.x 的 `ib-cacher` 產生的快取可直接使用。若某個類別在產生快取後改過屬性名稱、型別或 nullable，該筆快取會被忽略，改用 reflection 掃描。只改 attribute 仍然偵測不到：請和以前一樣在部署時重新產生快取。同一檔案中有多個 namespace，或旁邊有 `new readonly class` / `new #[Attr] class` 運算式的類別，現在 `ib-cacher` 與 `ib-writer` 都能正確找到。

## 5.0 新增

- **`#[InputKey('…')]` / `#[OutputKey('…')]`** 將屬性對應到確切的 key，用於任何 `KeyCase` 都轉不出來的 key（`'2fa'`、`'user.id'`），優先於 `#[InputCaseTo]` / `#[OutputCaseTo]`。
- strict 模式下，被 property 層級 `#[InputCaseTo]` 使用掉的 key 不再被視為未宣告。
