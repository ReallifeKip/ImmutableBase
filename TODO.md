# TODO

## prepareInput() 實作後 QA 紀錄

### 背景
新增 `prepareInput(array $data): array` 鉤子，在 key remapping 後、型別解析前，讓子類別可對輸入資料做預處理/轉換。支援繼承鏈疊加（父 → 子順序，同 validate()）。

---

### 問題一：`with()` 重跑 `prepareInput()`
**結論：問題前提錯誤，關閉**

`with()` 不走 `__construct()`——它直接 `newInstanceWithoutConstructor()` + hydrator，完全繞過 `prepareInput()`。所以「重跑」的問題根本不存在。

`with()` 不呼叫 `prepareInput()` 是合理設計：`with()` 操作的是已型別解析後的值（ImmutableBase 實例、enum 等），`prepareInput()` 設計用於原始輸入（string/int from JSON/form），兩者處於不同階段，不應混用。

---

### 問題二：`array_intersect_key` 擋掉「注入」使用情境
**結論：設計正確，保留限制**

`prepareInput()` 只能修改「已存在的 key」（有 input 或有 default），無法無中生有。

符合 IB「代碼即文件」哲學——property 要在型別系統裡可見，否則就是魔法。**注入衍生值本來就不是 IB 希望看見的行為。**

---

### 問題三：Cache 相容性（`hasPrepareInput` 欄位）
**結論：加三元補償，餘下為開發者責任**

`hasPrepareInput` 已寫入 `scanProperties()`，新產生的 cache 正常。舊 cache 若未重新生成，`$ref['hasPrepareInput']` 為 `null`，`!null = true` 會靜默跳過所有 `prepareInput()`。

**修法**：loop 內改為 `!($ref['hasPrepareInput'] ?? false)` 即可安全降級。
刷新 cache 是開發者升版後的責任。

---

### 問題四：`$compiled` pre-merge 複雜 default 的 double-resolve 疑慮
**結論：誤報，非新問題**

`$type['defaults']` 存的是掃描期合併的預設值（優先 `defaultValues()`，fallback `#[Defaults]`）。

原本 `resolvePropertyData()` 已經把 `$type['defaults']` 放進 `$data[$name]` 再傳給 `resolveValue()`——我的修改只是把這步提前，resolver 收到的值完全相同。不是新問題，無需處理。

---

### 問題五：runtime `defaultValues()` 語意冗餘
**結論：已重構**

`$compiled` 提前 merge 進 `$data` 後，runtime `static::defaultValues()` 的結果永遠不被 `resolvePropertyData()` 使用。

已移除：
- `__construct()` 內的 `$defaults = static::defaultValues()`
- `resolvePropertyData()` 的 `$defaults` 參數及對應分支
- docblock 相關說明

`resolvePropertyData()` 現在只依賴 `$class['types'][$name]['defaults']`（掃描期已合併兩者來源），職責更單一。
