# Ledger of Decisions API

[English](README.md) | [繁體中文](README.zh-TW.md)

**Ledger of Decisions** 的後端 API 服務 - 一款不只記錄「花了什麼」，更追蹤「為什麼花」的個人財務應用。

## 技術棧

- **框架**: Laravel 12 (PHP 8.4)
- **資料庫**: SQLite (開發) / PostgreSQL (正式)
- **快取**: Redis 7
- **認證**: Laravel Sanctum (Token-based)
- **文件**: L5 Swagger (OpenAPI)
- **郵件**: Resend
- **容器化**: Docker (PHP-FPM + Nginx)

## 快速開始

### 前置需求

- Docker & Docker Compose

### 安裝

```bash
# 建構並啟動容器
make build
make up

# 執行資料庫遷移
make migrate

# (選用) 重置資料庫並填入種子資料
make fresh
```

API 服務位於 `http://localhost:8080/api/`。

### 環境設定

複製 `.env.example` 為 `.env`，依需求調整。主要變數：

| 變數 | 預設值 | 說明 |
|------|--------|------|
| `DB_CONNECTION` | `sqlite` | 資料庫驅動 (`sqlite`, `pgsql`) |
| `CACHE_STORE` | `redis` | 快取後端 |
| `MAIL_MAILER` | `log` | 郵件驅動 (`log`, `resend`) |
| `RESEND_API_KEY` | - | Resend API 金鑰 (正式環境) |

## 架構

```
app/
├── Console/Commands/     # Artisan 指令 (週期性支出排程)
├── DTO/                  # 資料傳輸物件 (依領域分類)
├── Enums/                # PHP 8.1+ 列舉 (Category, Intent 等)
├── Events/               # 領域事件
├── Http/
│   ├── Controllers/      # 11 個 API 控制器
│   ├── Requests/         # 表單驗證請求 (20+)
│   └── Resources/        # JSON 資源轉換器
├── Models/               # Eloquent 模型
├── Repositories/         # 資料存取層
├── Rules/                # 自訂驗證規則
└── Services/             # 業務邏輯層 (12 個服務)
```

**設計模式**: Service Layer、Repository Pattern、DTO、Form Requests、Enum 分類。

## API 端點

### 公開路由

| 方法 | 端點 | 說明 |
|------|------|------|
| `GET` | `/api/health` | 健康檢查 |
| `POST` | `/api/register` | 使用者註冊 |
| `POST` | `/api/login` | 登入認證 |
| `POST` | `/api/verify-email` | Email 驗證 |
| `POST` | `/api/resend-verification` | 重寄驗證碼 |
| `POST` | `/api/forgot-password` | 請求密碼重設 |
| `POST` | `/api/reset-password` | 完成密碼重設 |

### 受保護路由 (Bearer Token)

**使用者**

| 方法 | 端點 | 說明 |
|------|------|------|
| `POST` | `/api/logout` | 登出 |
| `GET` | `/api/user` | 當前使用者 |
| `PUT` | `/api/user/password` | 更新密碼 |
| `GET/PUT` | `/api/user/preferences` | 使用者偏好設定 |

**支出**

| 方法 | 端點 | 說明 |
|------|------|------|
| `GET/POST` | `/api/expenses` | 列表 / 新增 |
| `GET/PUT/DELETE` | `/api/expenses/{id}` | 讀取 / 更新 / 刪除 |
| `DELETE` | `/api/expenses/batch` | 批次刪除 |

**決策標記** (巢狀於支出下)

| 方法 | 端點 | 說明 |
|------|------|------|
| `POST` | `/api/expenses/{id}/decision` | 標記決策 |
| `GET/PUT/DELETE` | `/api/expenses/{id}/decision` | 讀取 / 更新 / 移除 |

**組合記錄**

| 方法 | 端點 | 說明 |
|------|------|------|
| `POST` | `/api/entries` | 同時建立支出 + 決策 |

**統計分析**

| 方法 | 端點 | 說明 |
|------|------|------|
| `GET` | `/api/statistics/intents` | 意圖分布 |
| `GET` | `/api/statistics/summary` | 總覽摘要 |
| `GET` | `/api/statistics/trends` | 趨勢分析 |

**週期性支出**

| 方法 | 端點 | 說明 |
|------|------|------|
| `GET/POST` | `/api/recurring-expenses` | 列表 / 新增 |
| `GET/PUT/DELETE` | `/api/recurring-expenses/{id}` | 讀取 / 更新 / 刪除 |
| `GET` | `/api/recurring-expenses/upcoming` | 未來 7 天 |
| `POST` | `/api/recurring-expenses/{id}/generate` | 產生支出紀錄 |
| `GET` | `/api/recurring-expenses/{id}/history` | 檢視歷史 |

**現金流**

| 方法 | 端點 | 說明 |
|------|------|------|
| CRUD | `/api/incomes` | 收入管理 |
| CRUD | `/api/cash-flow-items` | 現金流項目 |
| `GET` | `/api/cash-flow/summary` | 現金流摘要 |
| `GET` | `/api/cash-flow/projection` | 未來預測 |

### API 回應格式

```json
{
  "success": true,
  "data": { ... },
  "error": null
}
```

分頁回應包含 `meta` 和 `links` 物件。

## 資料庫結構

| 資料表 | 說明 |
|--------|------|
| `users` | 使用者帳號 (含 Email 驗證) |
| `expenses` | 支出紀錄 (金額、分類、日期、備註) |
| `decisions` | 決策標記 (與支出 1:1) |
| `recurring_expenses` | 週期性支出範本 |
| `incomes` | 收入紀錄 |
| `cash_flow_items` | 現金流預測項目 |

### 列舉值

- **Category (分類)**: `food`, `transport`, `training`, `living`, `other`
- **Intent (意圖)**: `necessity`, `efficiency`, `enjoyment`, `recovery`, `impulse`
- **ConfidenceLevel (信心程度)**: `high`, `medium`, `low`
- **FrequencyType (頻率)**: `daily`, `weekly`, `monthly`, `yearly`

## Makefile 指令

```bash
make build       # 建構 Docker 映像
make up          # 啟動容器
make down        # 停止容器
make restart     # 重啟容器
make shell       # 進入應用容器 Shell

make artisan cmd="..."   # 執行 Artisan 指令
make composer cmd="..."  # 執行 Composer 指令

make migrate     # 執行資料庫遷移
make fresh       # 重置資料庫 + 種子資料

make test        # 執行測試
make coverage    # 執行測試含覆蓋率報告 (最低 85%)
make phpstan     # 靜態分析
```

## 測試

```bash
# 執行所有測試
make test

# 含覆蓋率報告 (最低 85%)
make coverage

# 執行特定測試
make artisan cmd="test --filter=ExpenseTest"
```

測試使用記憶體內 SQLite 以提升速度。測試套件：
- `tests/Feature/` - API 整合測試
- `tests/Unit/` - 單元測試 (Repositories、Resources、Enums)

## API 文件

Swagger/OpenAPI 文件位於：

```
GET /api/documentation
```

## 授權條款

MIT
