# Ledger of Decisions API 文件

> 決策驅動的記帳 API - 前端開發指南

## 目錄

- [概述](#概述)
- [環境設置](#環境設置)
- [資料模型](#資料模型)
- [Enum 定義](#enum-定義)
- [API 端點](#api-端點)
  - [身份驗證 (Auth)](#身份驗證-auth)
  - [消費記錄 (Expenses)](#消費記錄-expenses)
  - [決策標註 (Decisions)](#決策標註-decisions)
  - [合併新增 (Entries)](#合併新增-entries)
  - [統計分析 (Statistics)](#統計分析-statistics)
- [錯誤處理](#錯誤處理)
- [篩選與分頁](#篩選與分頁)

---

## 概述

本 API 提供記帳功能，核心特色是每筆消費可標註「決策意圖」，幫助使用者回顧：
> 「我這週的錢，大多花在什麼樣的決策上？」

**Base URL**: `http://localhost:8080/api`

**Content-Type**: `application/json`

**時區**: Asia/Taipei (UTC+8)

**認證方式**: Sanctum Stateful Session（HttpOnly Cookie）+ CSRF Token

寫入型請求（`POST/PUT/PATCH/DELETE`）建議流程：
1. `GET /sanctum/csrf-cookie` 取得 `XSRF-TOKEN` cookie。
2. `POST /api/login` 建立 session。
3. 後續寫入請求攜帶 `X-XSRF-TOKEN` header。
4. 受保護 API 不接受 `Authorization: Bearer <token>`。

---

## 環境設置

```bash
# 啟動後端服務
cd ledger-of-decisions-api
make up

# 確認服務運行
curl http://localhost:8080/api/expenses
```

---

## 資料模型

### Expense (消費記錄)

| 欄位 | 類型 | 說明 |
|------|------|------|
| id | integer | 主鍵 |
| amount | decimal | 消費金額 |
| currency | string | 幣別，預設 `TWD` |
| category | enum | 消費類別 |
| occurred_at | datetime | 消費發生時間 (ISO 8601) |
| note | string | 消費備註 (最多 500 字) |
| decision | object | 關聯的決策標註 (可選) |
| created_at | datetime | 建立時間 |
| updated_at | datetime | 更新時間 |

### Decision (決策標註)

| 欄位 | 類型 | 說明 |
|------|------|------|
| id | integer | 主鍵 |
| expense_id | integer | 關聯的消費記錄 ID |
| intent | enum | 決策意圖 |
| confidence_level | enum | 信心程度，預設 `medium` |
| decision_note | string | 決策備註 (最多 1000 字) |
| created_at | datetime | 建立時間 |
| updated_at | datetime | 更新時間 |

---

## Enum 定義

### Category (消費類別)

| 值 | 中文標籤 | 說明 |
|----|----------|------|
| `food` | 飲食 | 餐飲相關 |
| `transport` | 交通 | 交通出行 |
| `training` | 學習/訓練 | 教育、課程、書籍 |
| `living` | 生活 | 日常生活用品 |
| `other` | 其他 | 其他類別 |

### Intent (決策意圖)

| 值 | 中文標籤 | 說明 |
|----|----------|------|
| `necessity` | 必要性 | 必須要花的錢 |
| `efficiency` | 效率 | 為了提升效率而花 |
| `enjoyment` | 享受 | 純粹享受、娛樂 |
| `recovery` | 恢復 | 休息、恢復精力 |
| `impulse` | 衝動 | 衝動消費 |

### ConfidenceLevel (信心程度)

| 值 | 中文標籤 | 說明 |
|----|----------|------|
| `high` | 高 | 非常確定這個決策 |
| `medium` | 中 | 還算確定 |
| `low` | 低 | 不太確定 |

---

## API 端點

### 身份驗證 (Auth)

#### 註冊

```http
POST /api/register
```

**Request Body**:
```json
{
  "name": "Test User",
  "email": "test@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response** (201 Created): `success: true` + `data(user)` + `message`

---

#### 取得 CSRF Cookie

```http
GET /sanctum/csrf-cookie
```

**Response** (204 No Content): 設定 `XSRF-TOKEN` cookie。

---

#### 登入

```http
POST /api/login
```

**Request Body**:
```json
{
  "email": "test@example.com",
  "password": "password123"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "登入成功",
  "data": {
    "user": {
      "id": 1,
      "name": "Test User",
      "email": "test@example.com"
    }
  }
}
```

**Error** (403):
```json
{
  "success": false,
  "error": "email_not_verified",
  "data": {
    "email": "test@example.com"
  }
}
```

---

#### Email 驗證

```http
POST /api/verify-email
```

**Request Body**:
```json
{
  "email": "test@example.com",
  "code": "123456"
}
```

**Response** (200 OK): `success: true` + `data(user)` + `message`

**Errors**:
- `404`: user 不存在（例如 code 驗證通過但使用者已刪除）
- `422`: 驗證碼錯誤或過期
- `429`: 驗證次數過多

---

#### 重送驗證碼

```http
POST /api/resend-verification
```

**Request Body**:
```json
{
  "email": "test@example.com"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "如果此 Email 已註冊，我們將發送驗證碼"
}
```

---

#### 忘記密碼（發送重設驗證碼）

```http
POST /api/forgot-password
```

**Request Body**:
```json
{
  "email": "test@example.com"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "如果此 Email 已註冊，我們將發送重設密碼驗證碼"
}
```

---

#### 重設密碼

```http
POST /api/reset-password
```

**Request Body**:
```json
{
  "email": "test@example.com",
  "code": "123456",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

**Response** (200 OK): `success: true` + `message`

**Errors**:
- `404`: user 不存在（例如 code 驗證通過但使用者已刪除）
- `422`: 驗證碼錯誤/格式錯誤/密碼驗證失敗
- `429`: 驗證次數過多

---

#### 取得目前使用者

```http
GET /api/user
```

**Response** (200 OK): `success: true` + `data(user)`

---

#### 更新目前使用者密碼

```http
PUT /api/user/password
```

**Request Body**:
```json
{
  "current_password": "oldpassword",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

**Response** (200 OK): `success: true` + `message`

---

#### 登出

```http
POST /api/logout
```

**Response** (200 OK): `success: true` + `message`

**Error**:
- `419`: 缺失或錯誤 CSRF token

---

### 消費記錄 (Expenses)

#### 新增消費

```http
POST /api/expenses
```

**Request Body**:
```json
{
  "amount": 150,
  "currency": "TWD",
  "category": "food",
  "occurred_at": "2026-02-06 12:00:00",
  "note": "午餐便當"
}
```

| 欄位 | 必填 | 說明 |
|------|------|------|
| amount | 是 | 金額，必須 >= 0 |
| currency | 否 | 幣別，預設 TWD |
| category | 是 | 消費類別 |
| occurred_at | 是 | 消費時間 |
| note | 否 | 備註 |

**Response** (201 Created):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "amount": "150.00",
    "currency": "TWD",
    "category": "food",
    "category_label": "飲食",
    "occurred_at": "2026-02-06T12:00:00+08:00",
    "note": "午餐便當",
    "created_at": "2026-02-06T14:30:00+08:00",
    "updated_at": "2026-02-06T14:30:00+08:00"
  }
}
```

---

#### 查詢消費列表

```http
GET /api/expenses
```

**Query Parameters**:

| 參數 | 說明 | 範例 |
|------|------|------|
| page | 頁碼 | `1` |
| per_page | 每頁筆數，預設 15 | `20` |
| start_date | 起始日期 | `2026-02-01` |
| end_date | 結束日期 | `2026-02-28` |
| preset | 預設時間範圍 | `today`, `this_week`, `this_month` |
| category | 類別篩選 (可多選) | `food,transport` |
| intent | 意圖篩選 (可多選) | `necessity,impulse` |
| confidence_level | 信心程度篩選 | `high,medium` |

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "amount": "150.00",
      "currency": "TWD",
      "category": "food",
      "category_label": "飲食",
      "occurred_at": "2026-02-06T12:00:00+08:00",
      "note": "午餐便當",
      "decision": {
        "id": 1,
        "intent": "necessity",
        "intent_label": "必要性",
        "confidence_level": "high",
        "confidence_level_label": "高",
        "decision_note": null
      },
      "created_at": "2026-02-06T14:30:00+08:00",
      "updated_at": "2026-02-06T14:30:00+08:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  },
  "links": {
    "first": "http://localhost:8080/api/expenses?page=1",
    "last": "http://localhost:8080/api/expenses?page=1",
    "prev": null,
    "next": null
  }
}
```

---

#### 查詢單筆消費

```http
GET /api/expenses/{id}
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "amount": "150.00",
    "currency": "TWD",
    "category": "food",
    "category_label": "飲食",
    "occurred_at": "2026-02-06T12:00:00+08:00",
    "note": "午餐便當",
    "decision": {
      "id": 1,
      "intent": "necessity",
      "intent_label": "必要性",
      "confidence_level": "high",
      "confidence_level_label": "高",
      "decision_note": null
    },
    "created_at": "2026-02-06T14:30:00+08:00",
    "updated_at": "2026-02-06T14:30:00+08:00"
  }
}
```

---

#### 更新消費

```http
PUT /api/expenses/{id}
```

**Request Body** (僅傳需要更新的欄位):
```json
{
  "amount": 200,
  "note": "午餐便當 + 飲料"
}
```

**Response** (200 OK): 同查詢單筆消費

---

#### 刪除消費

```http
DELETE /api/expenses/{id}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "消費記錄已刪除"
}
```

---

### 決策標註 (Decisions)

#### 新增決策標註

```http
POST /api/expenses/{expense_id}/decision
```

**Request Body**:
```json
{
  "intent": "necessity",
  "confidence_level": "high",
  "decision_note": "這是今天的午餐，必要開支"
}
```

| 欄位 | 必填 | 說明 |
|------|------|------|
| intent | 是 | 決策意圖 |
| confidence_level | 否 | 信心程度，建立時未提供預設 medium；更新時可傳 `null` 清空 |
| decision_note | 否 | 決策備註 |

**Response** (201 Created):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "expense_id": 1,
    "intent": "necessity",
    "intent_label": "必要性",
    "confidence_level": "high",
    "confidence_level_label": "高",
    "decision_note": "這是今天的午餐，必要開支",
    "created_at": "2026-02-06T14:35:00+08:00",
    "updated_at": "2026-02-06T14:35:00+08:00"
  }
}
```

**錯誤** (422 已有決策):
```json
{
  "success": false,
  "message": "此消費記錄已有決策標註，請使用更新 API"
}
```

---

#### 查詢決策標註

```http
GET /api/expenses/{expense_id}/decision
```

**Response** (200 OK): 同新增決策標註

**錯誤** (404 無決策):
```json
{
  "success": false,
  "message": "此消費記錄尚無決策標註"
}
```

---

#### 更新決策標註

```http
PUT /api/expenses/{expense_id}/decision
```

**Request Body**:
```json
{
  "intent": "impulse",
  "confidence_level": null
}
```

**Response** (200 OK): 同查詢決策標註

---

#### 刪除決策標註

```http
DELETE /api/expenses/{expense_id}/decision
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "決策標註已刪除"
}
```

---

### 合併新增 (Entries)

一次新增消費 + 決策，推薦用於快速記帳場景。

#### 新增消費與決策

```http
POST /api/entries
```

**Request Body**:
```json
{
  "amount": 250,
  "category": "transport",
  "occurred_at": "2026-02-06 08:00:00",
  "note": "計程車",
  "intent": "efficiency",
  "confidence_level": "high",
  "decision_note": "趕時間必須搭計程車"
}
```

**Response** (201 Created):
```json
{
  "success": true,
  "data": {
    "id": 2,
    "amount": "250.00",
    "currency": "TWD",
    "category": "transport",
    "category_label": "交通",
    "occurred_at": "2026-02-06T08:00:00+08:00",
    "note": "計程車",
    "decision": {
      "id": 2,
      "intent": "efficiency",
      "intent_label": "效率",
      "confidence_level": "high",
      "confidence_level_label": "高",
      "decision_note": "趕時間必須搭計程車"
    },
    "created_at": "2026-02-06T14:40:00+08:00",
    "updated_at": "2026-02-06T14:40:00+08:00"
  }
}
```

---

### 固定支出 (Recurring Expenses)

#### 查詢固定支出列表

```http
GET /api/recurring-expenses
```

**Query Parameters**: `page`, `per_page`, `category`, `is_active`, `frequency_type`

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "name": "車貸",
      "amount_min": "15000.00",
      "amount_max": null,
      "amount_display": "15000.00",
      "category": "living",
      "category_label": "生活",
      "frequency_type": "monthly",
      "frequency_type_label": "每月",
      "frequency_display": "每 1 月",
      "next_occurrence": "2026-03-01",
      "is_active": true
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

#### 新增固定支出

```http
POST /api/recurring-expenses
```

**Request Body**:
```json
{
  "name": "車貸",
  "amount_min": 15000,
  "category": "living",
  "frequency_type": "monthly",
  "frequency_interval": 1,
  "day_of_month": 15,
  "start_date": "2026-02-01",
  "default_intent": "necessity",
  "note": "中古車分期付款"
}
```

**Response** (201 Created): `success: true` + `data`

#### 查詢單筆 / 更新 / 刪除固定支出

```http
GET /api/recurring-expenses/{id}
PUT /api/recurring-expenses/{id}
DELETE /api/recurring-expenses/{id}
```

**Response**:
- `GET/PUT`: `success: true` + `data`
- `DELETE`: `success: true` + `message`

#### 查詢即將到期固定支出

```http
GET /api/recurring-expenses/upcoming?days=14
```

**Response** (200 OK): `success: true` + `data[]`

#### 手動生成消費記錄

```http
POST /api/recurring-expenses/{id}/generate
```

**Request Body**:
```json
{
  "date": "2026-02-08",
  "amount": 15000
}
```

**Response** (201 Created):
```json
{
  "success": true,
  "message": "已手動生成消費記錄",
  "data": {
    "id": 10,
    "amount": "15000.00",
    "category": "living",
    "recurring_expense_id": 1,
    "is_from_recurring": true
  }
}
```

#### 查詢固定支出生成歷史

```http
GET /api/recurring-expenses/{id}/history?limit=10
```

**Response** (200 OK): `success: true` + `data[]`

---

### 現金流 (Cash Flow)

#### 現金流摘要

```http
GET /api/cash-flow/summary
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "total_income": "80000.00",
    "total_expense": "25000.00",
    "net_cash_flow": "55000.00",
    "savings_rate": "68.8"
  }
}
```

---

#### 多月現金流預測

```http
GET /api/cash-flow/projection?months=3
```

**Query Parameters**:

| 參數 | 必填 | 預設 | 說明 |
|------|------|------|------|
| months | 否 | 1 | 預測月數，最小 1；大於 12 會限制為 12 |

**Response** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "month": "2026/02",
      "income": "80000.00",
      "expense": "50000.00",
      "net": "30000.00",
      "cumulative_balance": "30000.00"
    },
    {
      "month": "2026/03",
      "income": "80000.00",
      "expense": "50000.00",
      "net": "30000.00",
      "cumulative_balance": "60000.00"
    }
  ]
}
```

**Validation Error** (422):
```json
{
  "success": false,
  "error": "預測月數至少為 1"
}
```

---

### 統計分析 (Statistics)

#### Intent 統計

查看各決策意圖的出現次數與平均信心程度。

```http
GET /api/statistics/intents
```

**Query Parameters**: `start_date`, `end_date`, `preset`

**Response** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "intent": "necessity",
      "intent_label": "必要性",
      "count": 15,
      "avg_confidence_score": 2.67,
      "avg_confidence_level": "high"
    },
    {
      "intent": "impulse",
      "intent_label": "衝動",
      "count": 3,
      "avg_confidence_score": 1.33,
      "avg_confidence_level": "low"
    }
  ]
}
```

---

#### 消費總覽

```http
GET /api/statistics/summary
```

**Query Parameters**: `start_date`, `end_date`, `preset`

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "total_amount": 4500,
    "total_count": 30,
    "by_category": [
      {
        "category": "food",
        "category_label": "飲食",
        "total_amount": 2000,
        "count": 15
      },
      {
        "category": "transport",
        "category_label": "交通",
        "total_amount": 1500,
        "count": 10
      }
    ],
    "by_intent": [
      {
        "intent": "necessity",
        "intent_label": "必要性",
        "total_amount": 3000,
        "count": 20
      },
      {
        "intent": "impulse",
        "intent_label": "衝動",
        "total_amount": 500,
        "count": 3
      }
    ],
    "impulse_spending_ratio": 10.00
  }
}
```

---

#### 趨勢指標

```http
GET /api/statistics/trends
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "impulse_spending": {
      "this_week": 300,
      "last_week": 500,
      "change_percentage": -40.00,
      "trend": "down"
    },
    "high_confidence_intents": [
      {
        "intent": "necessity",
        "intent_label": "必要性",
        "count": 10
      },
      {
        "intent": "efficiency",
        "intent_label": "效率",
        "count": 5
      }
    ]
  }
}
```

---

## 錯誤處理

### 驗證錯誤 (422 Unprocessable Entity)

```json
{
  "message": "請輸入消費金額",
  "errors": {
    "amount": ["請輸入消費金額"],
    "category": ["請選擇消費類別"]
  }
}
```

### 資源不存在 (404 Not Found)

```json
{
  "message": "No query results for model [App\\Models\\Expense] 999"
}
```

### 自訂錯誤

```json
{
  "success": false,
  "message": "此消費記錄已有決策標註，請使用更新 API"
}
```

---

## 篩選與分頁

### 分頁參數

所有列表 API 都支援分頁：

| 參數 | 預設值 | 說明 |
|------|--------|------|
| page | 1 | 頁碼 |
| per_page | 15 | 每頁筆數 |

### 時間範圍篩選

| 參數 | 說明 | 範例 |
|------|------|------|
| start_date | 起始日期 | `2026-02-01` |
| end_date | 結束日期 | `2026-02-28` |
| preset | 預設範圍 | `today`, `this_week`, `this_month` |

### 多值篩選

使用逗號分隔多個值：

```
GET /api/expenses?category=food,transport&intent=necessity,impulse
```

---

## TypeScript 類型定義

供前端參考的類型定義：

```typescript
// Enums
type Category = 'food' | 'transport' | 'training' | 'living' | 'other';
type Intent = 'necessity' | 'efficiency' | 'enjoyment' | 'recovery' | 'impulse';
type ConfidenceLevel = 'high' | 'medium' | 'low';

// Models
interface Decision {
  id: number;
  expense_id: number;
  intent: Intent;
  intent_label: string;
  confidence_level: ConfidenceLevel | null;
  confidence_level_label: string | null;
  decision_note: string | null;
  created_at: string;
  updated_at: string;
}

interface Expense {
  id: number;
  amount: string;
  currency: string;
  category: Category;
  category_label: string;
  occurred_at: string;
  note: string | null;
  decision?: Decision | null;
  created_at: string;
  updated_at: string;
}

// API Responses
interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
}

interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  links: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
}

// Request DTOs
interface CreateExpenseRequest {
  amount: number;
  currency?: string;
  category: Category;
  occurred_at: string;
  note?: string;
}

interface CreateDecisionRequest {
  intent: Intent;
  confidence_level?: ConfidenceLevel | null;
  decision_note?: string;
}

interface CreateEntryRequest extends CreateExpenseRequest, CreateDecisionRequest {}
```

---

## 聯絡方式

如有 API 問題，請聯繫後端開發者。
