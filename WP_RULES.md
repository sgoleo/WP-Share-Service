# Workspace Rules - WP-Share-Service

本文件定義了此專案開發時必須嚴格遵守的規範。

## 1. 權限與安全驗證 (Permissions & Security)
- **強制權限檢查**：所有後台介面、AJAX 或 API 端點必須使用 `current_user_can()`。
- **輸入清理**：使用 `sanitize_text_field()` 等函式。
- **輸出跳脫**：使用 `esc_html()` 等函式。

## 2. 密碼學強制規範 (Cryptography Standards)
- **禁止弱加密**：禁止明文或 MD5。
- **標準函式**：必須使用 `wp_hash_password()` 與 `wp_check_password()`。

## 3. 錯誤處理標準 (Error Handling)
- **禁止裸露例外**：所有錯誤必須記錄日誌。
- **路徑隱匿**：禁止向前端暴露伺服器實體路徑。
