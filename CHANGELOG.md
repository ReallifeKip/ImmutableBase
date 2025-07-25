# CHANGELOG

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