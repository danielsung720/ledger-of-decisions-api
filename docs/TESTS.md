# 後端測試文件

> 最後更新：2026-02-08
> 測試總數：215｜斷言總數：605

---

## 單元測試

### 1. 枚舉測試

枚舉測試確保所有枚舉值、標籤和轉換方法正確運作。

#### CategoryTest (10 tests)

**意圖**：驗證消費分類枚舉的完整性和正確性。

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `it_has_five_categories` | 確保分類數量固定為 5 個 | 防止意外新增或刪除分類 |
| `it_has_correct_values` | 驗證每個分類的字串值 | food, transport, training, living, other |
| `it_returns_correct_labels` (x5) | 驗證中文標籤對應 | 飲食, 交通, 學習/訓練, 生活, 其他 |
| `values_returns_all_string_values` | 確保 values() 回傳所有值的陣列 | 用於驗證規則和前端選項 |
| `it_can_be_created_from_string` | 驗證 from() 能從字串建立枚舉 | 正常字串輸入 |
| `it_returns_null_for_invalid_value_with_try_from` | 驗證 tryFrom() 對無效值回傳 null | 邊界：無效字串不拋例外 |

#### IntentTest (10 tests)

**意圖**：驗證決策意圖枚舉，這是系統核心概念。

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `it_has_five_intents` | 確保意圖數量固定為 5 個 | 防止意外修改 |
| `it_has_correct_values` | 驗證每個意圖的字串值 | necessity, efficiency, enjoyment, recovery, impulse |
| `it_returns_correct_labels` (x5) | 驗證中文標籤 | 必要性, 效率, 享受, 恢復, 衝動 |
| `values_returns_all_string_values` | 確保 values() 方法正確 | 陣列順序與定義一致 |
| `it_can_be_created_from_string` | from() 正常運作 | 有效字串輸入 |
| `it_returns_null_for_invalid_value_with_try_from` | tryFrom() 安全處理無效輸入 | 邊界：防止程式崩潰 |

#### ConfidenceLevelTest (8 tests)

**意圖**：驗證滿意度/信心程度枚舉。

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `it_has_three_levels` | 確保只有 3 個等級 | high, medium, low |
| `it_has_correct_values` | 驗證字串值 | 用於資料庫儲存 |
| `it_returns_correct_labels` (x3) | 驗證中文標籤 | 高, 中, 低 |
| `values_returns_all_string_values` | values() 方法 | 用於統計計算 |
| `it_can_be_created_from_string` | from() 方法 | 正常輸入 |
| `it_returns_null_for_invalid_value_with_try_from` | tryFrom() 安全性 | 無效輸入處理 |

#### FrequencyTypeTest (10 tests)

**意圖**：驗證循環消費的頻率類型枚舉。

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `it_has_four_frequency_types` | 確保 4 種頻率類型 | daily, weekly, monthly, yearly |
| `it_has_correct_values` | 驗證字串值 | 用於日期計算邏輯 |
| `it_returns_correct_labels` (x4) | 驗證中文標籤 | 每日, 每週, 每月, 每年 |
| `values_returns_all_string_values` | values() 方法 | 用於表單選項 |
| `it_can_be_created_from_string` | from() 方法 | 正常輸入 |
| `it_returns_null_for_invalid_value_with_try_from` | tryFrom() 安全性 | 無效輸入處理 |

---

### 2. 模型測試

模型測試驗證資料模型的屬性、類型轉換、關聯和業務方法。

#### ExpenseTest (13 tests)

**意圖**：驗證消費記錄模型的基本功能。

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `it_has_correct_fillable_attributes` | 確保只有預期欄位可批量賦值 | 防止意外欄位被寫入（安全性） |
| `it_has_default_currency` | 驗證預設幣別為 TWD | 新建記錄不需指定幣別 |
| `it_casts_amount_to_decimal` | 金額正確轉為 decimal:2 | 邊界：100.50 → "100.50" |
| `it_casts_category_to_enum` | 分類自動轉為 Category 枚舉 | 類型安全，防止無效值 |
| `it_casts_occurred_at_to_datetime` | 日期時間正確轉換 | Carbon 實例，便於日期操作 |
| `it_has_one_decision` | 驗證一對一關聯 | 每筆消費最多一個決策標註 |
| `it_can_have_no_decision` | 決策可為空 | 邊界：消費可以沒有決策標註 |
| `it_belongs_to_recurring_expense` | 驗證多對一關聯 | 消費可來自循環消費 |
| `is_from_recurring_returns_true_when_has_recurring_expense` | isFromRecurring() 有關聯時 | 判斷消費來源 |
| `is_from_recurring_returns_false_when_no_recurring_expense` | isFromRecurring() 無關聯時 | 邊界：一般消費 |
| `it_can_be_created_with_factory` | Factory 能正確建立模型 | 測試基礎設施驗證 |
| `food_factory_state_creates_food_category` | Factory state 正確 | 快速建立特定分類 |
| `transport_factory_state_creates_transport_category` | Factory state 正確 | 快速建立特定分類 |

#### DecisionTest (11 tests)

**意圖**：驗證決策標註模型，這是系統核心。

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `it_has_correct_fillable_attributes` | 確保可批量賦值欄位 | expense_id, intent, confidence_level, decision_note |
| `it_casts_intent_to_enum` | 意圖自動轉為 Intent 枚舉 | 類型安全 |
| `it_casts_confidence_level_to_enum` | 信心程度轉為枚舉 | 類型安全 |
| `it_can_omit_confidence_level` | 信心程度可不填 | 邊界：使用者可選擇不評價 |
| `it_belongs_to_expense` | 驗證關聯到消費 | 決策必須附屬於消費 |
| `it_can_be_created_with_factory` | Factory 正常運作 | 測試基礎設施 |
| `necessity_factory_state_creates_necessity_intent` | Factory state | 快速建立必要性決策 |
| `impulse_factory_state_creates_impulse_intent` | Factory state | 快速建立衝動決策 |
| `high_confidence_factory_state_creates_high_confidence` | Factory state | 快速建立高信心決策 |
| `it_can_have_decision_note` | 決策備註可填寫 | 使用者補充說明 |
| `it_can_have_null_decision_note` | 決策備註可為空 | 邊界：備註為選填 |

#### RecurringExpenseTest (30 tests)

**意圖**：驗證循環消費模型，包含複雜的日期計算邏輯。

##### 基本屬性測試

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `it_has_correct_fillable_attributes` | 確保 16 個可批量賦值欄位 | 完整的欄位清單 |
| `it_has_correct_default_attributes` | 驗證預設值 | currency=TWD, frequency_interval=1, is_active=true |
| `it_casts_attributes_correctly` | 所有類型轉換正確 | decimal, enum, date, boolean |
| `it_has_many_expenses` | 驗證一對多關聯 | 一個循環消費產生多筆消費 |

##### hasAmountRange() 測試

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `has_amount_range_returns_false_when_no_max` | 沒有最大金額時 | 邊界：amount_max = null |
| `has_amount_range_returns_false_when_min_equals_max` | 最小等於最大時 | 邊界：固定金額 100 = 100 |
| `has_amount_range_returns_true_when_max_greater_than_min` | 有變動範圍時 | 正常情境：100 < 200 |

##### generateAmount() 測試

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `generate_amount_returns_min_when_no_range` | 無範圍時回傳最小值 | 固定金額情境 |
| `generate_amount_returns_value_within_range` | 有範圍時隨機生成 | 邊界：結果在 min ~ max 之間 |

##### isDue() 測試

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `is_due_returns_false_when_inactive` | 停用時不到期 | 邊界：is_active = false |
| `is_due_returns_false_when_past_end_date` | 超過結束日期 | 邊界：今天 > end_date |
| `is_due_returns_false_when_next_occurrence_is_future` | 下次日期在未來 | 正常情境：還沒到期 |
| `is_due_returns_true_when_next_occurrence_is_today` | 下次日期是今天 | 邊界：剛好到期 |
| `is_due_returns_true_when_next_occurrence_is_past` | 下次日期已過 | 情境：遺漏處理 |

##### 日期計算測試 - Daily

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `calculate_next_daily_adds_interval_days` | 每日計算 | interval=1 → 明天 |
| `calculate_next_daily_with_interval_3` | 每 3 天計算 | interval=3 → +3 天 |

##### 日期計算測試 - Weekly

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `calculate_next_weekly_with_day_of_week` | 指定星期幾 | 例：每週一 |
| `calculate_next_weekly_with_interval_2` | 每 2 週計算 | interval=2 → +2 週 |
| `calculate_next_weekly_without_day_of_week` | 未指定星期幾 | 直接加 7 天 |

##### 日期計算測試 - Monthly（複雜邊界）

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `calculate_next_monthly_same_day` | 每月同一天 | 例：每月 15 日 |
| `calculate_next_monthly_handles_month_end` | **月末邊界** | 31 日 → 2 月變 28 日 |
| `calculate_next_monthly_handles_leap_year` | **閏年邊界** | 2028 年 2 月有 29 日 |
| `calculate_next_monthly_with_interval_3` | 每 3 個月 | interval=3 → +3 個月 |

##### 日期計算測試 - Yearly

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `calculate_next_yearly` | 每年計算 | 例：每年 6 月 15 日 |
| `calculate_next_yearly_handles_feb_29_in_non_leap_year` | **閏年邊界** | 2/29 → 非閏年變 2/28 |
| `calculate_next_occurrence_returns_null_when_past_end_date` | 超過結束日期 | 邊界：回傳 null 表示結束 |

##### getMissedOccurrences() 測試

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `get_missed_occurrences_returns_empty_when_inactive` | 停用時無遺漏 | 不處理停用的循環 |
| `get_missed_occurrences_returns_empty_when_next_occurrence_is_future` | 未到期無遺漏 | 正常情境 |
| `get_missed_occurrences_returns_due_dates` | 計算所有遺漏日期 | 例：漏了 4 天 → 4 個日期 |
| `get_missed_occurrences_stops_at_end_date` | 在結束日期停止 | 邊界：不超過 end_date |

##### advanceNextOccurrence() 測試

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `advance_next_occurrence_updates_date` | 更新下次日期 | 正常推進 |
| `advance_next_occurrence_deactivates_when_next_would_exceed_end_date` | 超過結束日期時停用 | 邊界：自動停用 |

##### Scope 測試

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `scope_active_returns_only_active_records` | active() 只回傳啟用的 | 過濾條件 |
| `scope_due_returns_due_records` | due() 回傳到期的 | 包含今天和過去 |
| `scope_due_excludes_inactive` | due() 排除停用的 | 複合條件 |
| `scope_upcoming_returns_records_within_days` | upcoming() 回傳 N 天內的 | 預設 7 天 |

##### Factory State 測試

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `monthly_factory_state_works` | 月度 factory | 快速建立測試資料 |
| `weekly_factory_state_works` | 週度 factory | 快速建立測試資料 |
| `daily_factory_state_works` | 日度 factory | 快速建立測試資料 |
| `yearly_factory_state_works` | 年度 factory | 快速建立測試資料 |
| `with_amount_range_factory_state_works` | 金額範圍 factory | 快速建立測試資料 |

---

### 3. 服務測試

#### RecurringExpenseServiceTest (23 tests)

**意圖**：驗證循環消費服務的業務邏輯。

##### processAllDue() 測試

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `process_all_due_generates_expenses_for_due_recurring_expenses` | 處理所有到期的循環消費 | 正常情境：2 筆到期 → 2 筆消費 |
| `process_all_due_skips_inactive_recurring_expenses` | 跳過停用的 | 邊界：is_active=false 不處理 |
| `process_all_due_skips_future_recurring_expenses` | 跳過未到期的 | 邊界：next_occurrence 在未來 |

##### processRecurringExpense() 測試

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `process_recurring_expense_generates_all_missed_occurrences` | 補建所有遺漏的消費 | 情境：漏了 4 天 → 建 4 筆 |
| `process_recurring_expense_updates_next_occurrence` | 處理後更新下次日期 | 自動推進日期 |
| `process_recurring_expense_returns_empty_for_inactive` | 停用的回傳空集合 | 邊界：不處理停用項目 |

##### generateExpenseForDate() 測試

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `generate_expense_for_date_creates_expense` | 為指定日期建立消費 | 欄位正確複製 |
| `generate_expense_for_date_creates_decision_when_default_intent_set` | 有預設意圖時自動建決策 | 自動標註 |
| `generate_expense_for_date_does_not_create_decision_when_no_default_intent` | 無預設意圖時不建決策 | 邊界：default_intent=null |
| `generate_expense_for_date_uses_random_amount_when_range_set` | 有金額範圍時隨機生成 | 變動金額情境 |

##### generateManually() 測試

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `generate_manually_creates_expense_with_today_date` | 手動建立（預設今天） | 使用者手動觸發 |
| `generate_manually_uses_custom_date` | 手動建立（自訂日期） | 可補建過去日期 |
| `generate_manually_uses_custom_amount` | 手動建立（自訂金額） | 可覆蓋預設金額 |
| `generate_manually_creates_decision_when_default_intent_set` | 手動建立也會建決策 | 一致的行為 |

##### getUpcoming() 測試

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `get_upcoming_returns_expenses_within_days` | 取得 N 天內即將到期 | 預設 7 天 |
| `get_upcoming_orders_by_next_occurrence` | 按日期排序 | 最近的排前面 |

##### getHistory() 測試

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `get_history_returns_expenses_for_recurring_expense` | 取得該循環的消費歷史 | 關聯查詢 |
| `get_history_respects_limit` | 限制回傳筆數 | 邊界：limit=5 只回傳 5 筆 |
| `get_history_includes_decisions` | 歷史包含決策資料 | eager loading |

##### deactivate() / reactivate() 測試

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `deactivate_sets_is_active_to_false` | 停用循環消費 | 簡單狀態切換 |
| `reactivate_sets_is_active_to_true_and_calculates_next_occurrence` | 重新啟用並計算日期 | 從今天重新計算 |
| `reactivate_does_nothing_when_past_end_date` | 超過結束日期無法啟用 | 邊界：已過期不能啟用 |

##### Transaction 測試

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `generate_expense_for_date_uses_transaction` | 使用資料庫交易 | 確保原子性：expense + decision |

---

### 4. 請求測試

驗證表單驗證規則和錯誤訊息。

#### StoreExpenseRequestTest (22 tests)

**意圖**：驗證消費記錄的輸入驗證。

##### 正常情境

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `it_passes_with_valid_data` | 最小有效資料通過 | amount, category, occurred_at |
| `it_passes_with_all_fields` | 完整資料通過 | 包含所有可選欄位 |

##### amount 驗證

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `amount_is_required` | 金額必填 | 缺少 amount 應失敗 |
| `amount_must_be_numeric` | 金額必須是數字 | 邊界："abc" 應失敗 |
| `amount_must_be_non_negative` | 金額不能為負 | 邊界：-100 應失敗 |
| `amount_can_be_zero` | 金額可以是 0 | 邊界：0 應通過 |

##### category 驗證

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `category_is_required` | 分類必填 | 缺少 category 應失敗 |
| `category_accepts_valid_values` (x5) | 接受有效枚舉值 | food, transport, training, living, other |
| `category_rejects_invalid_values` | 拒絕無效值 | 邊界："invalid" 應失敗 |

##### occurred_at 驗證

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `occurred_at_is_required` | 日期必填 | 缺少 occurred_at 應失敗 |
| `occurred_at_must_be_valid_date` | 必須是有效日期 | 邊界："not-a-date" 應失敗 |

##### currency 驗證

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `currency_must_be_3_characters` | 幣別必須 3 字元 | 邊界："USDD" 應失敗 |
| `currency_is_optional` | 幣別選填 | 不提供使用預設 TWD |

##### note 驗證

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `note_is_optional` | 備註選填 | null 應通過 |
| `note_cannot_exceed_500_characters` | 備註最多 500 字 | 邊界：501 字應失敗 |
| `note_can_be_500_characters` | 500 字可通過 | 邊界：剛好 500 字 |

##### 其他

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `it_returns_chinese_error_messages` | 錯誤訊息為中文 | UX：使用者看懂 |
| `request_is_authorized` | 請求已授權 | authorize() 回傳 true |

#### StoreDecisionRequestTest (21 tests)

**意圖**：驗證決策標註的輸入驗證。

##### 正常情境

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `it_passes_with_valid_data` | 最小有效資料 | 只需 intent |
| `it_passes_with_all_fields` | 完整資料 | intent + confidence_level + decision_note |

##### intent 驗證

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `intent_is_required` | 意圖必填 | 缺少 intent 應失敗 |
| `intent_accepts_valid_values` (x5) | 接受有效枚舉值 | necessity, efficiency, enjoyment, recovery, impulse |
| `intent_rejects_invalid_values` | 拒絕無效值 | 邊界："invalid" 應失敗 |

##### confidence_level 驗證

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `confidence_level_is_optional` | 信心程度選填 | 不提供應通過 |
| `confidence_level_accepts_valid_values` (x3) | 接受有效值 | high, medium, low |
| `confidence_level_rejects_invalid_values` | 拒絕無效值 | 邊界："invalid" 應失敗 |

##### decision_note 驗證

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `decision_note_is_optional` | 備註選填 | null 應通過 |
| `decision_note_cannot_exceed_1000_characters` | 最多 1000 字 | 邊界：1001 字應失敗 |
| `decision_note_can_be_1000_characters` | 1000 字可通過 | 邊界：剛好 1000 字 |

##### 中文錯誤訊息

| Test | 意圖 | 邊界/情境 |
|------|------|----------|
| `it_returns_chinese_error_messages` | 一般錯誤中文 | "請選擇決策意圖" |
| `it_returns_chinese_error_for_invalid_intent` | 無效意圖中文 | "無效的決策意圖" |
| `it_returns_chinese_error_for_invalid_confidence_level` | 無效信心中文 | "無效的信心程度" |
| `request_is_authorized` | 請求已授權 | authorize() 回傳 true |

---

## 功能測試

### API 整合測試

#### ExpenseApiTest (9 tests)

**意圖**：驗證消費記錄 API 端點的完整功能。

| Test | Endpoint | 意圖 | 邊界/情境 |
|------|----------|------|----------|
| `test_can_create_expense` | POST /api/expenses | 新增消費 | 回傳 201 + 正確資料 |
| `test_can_list_expenses` | GET /api/expenses | 列表分頁 | 回傳分頁結構 |
| `test_can_show_expense` | GET /api/expenses/{id} | 單筆查詢 | 回傳完整資料 |
| `test_can_update_expense` | PUT /api/expenses/{id} | 更新消費 | 資料正確更新 |
| `test_can_delete_expense` | DELETE /api/expenses/{id} | 刪除消費 | 資料庫移除 |
| `test_can_filter_expenses_by_category` | GET /api/expenses?category= | 分類篩選 | 只回傳該分類 |
| `test_can_filter_expenses_by_date_range` | GET /api/expenses?start_date=&end_date= | 日期篩選 | 只回傳範圍內 |
| `test_validates_required_fields` | POST /api/expenses | 驗證必填 | 回傳 422 |
| `test_validates_category_enum` | POST /api/expenses | 驗證枚舉 | 無效值回傳 422 |

#### DecisionApiTest (7 tests)

**意圖**：驗證決策標註 API 端點。

| Test | Endpoint | 意圖 | 邊界/情境 |
|------|----------|------|----------|
| `test_can_create_decision_for_expense` | POST /api/expenses/{id}/decision | 新增決策 | 回傳 201 |
| `test_cannot_create_duplicate_decision` | POST /api/expenses/{id}/decision | 防止重複 | 邊界：已有決策回傳 422 |
| `test_can_show_decision` | GET /api/expenses/{id}/decision | 查詢決策 | 回傳決策資料 |
| `test_returns_404_when_no_decision` | GET /api/expenses/{id}/decision | 無決策時 | 邊界：回傳 404 |
| `test_can_update_decision` | PUT /api/expenses/{id}/decision | 更新決策 | 資料正確更新 |
| `test_can_delete_decision` | DELETE /api/expenses/{id}/decision | 刪除決策 | 資料庫移除 |
| `test_validates_intent_enum` | POST /api/expenses/{id}/decision | 驗證枚舉 | 無效值回傳 422 |

#### EntryApiTest (3 tests)

**意圖**：驗證合併新增 API（消費 + 決策一次建立）。

| Test | Endpoint | 意圖 | 邊界/情境 |
|------|----------|------|----------|
| `test_can_create_expense_with_decision` | POST /api/entries | 合併新增 | 同時建立消費和決策 |
| `test_validates_both_expense_and_decision_fields` | POST /api/entries | 驗證全部欄位 | 消費和決策欄位都驗證 |
| `test_transaction_rolls_back_on_failure` | POST /api/entries | 交易回滾 | 邊界：失敗時兩者都不建立 |

#### RecurringExpenseApiTest (16 tests)

**意圖**：驗證循環消費 API 端點的完整功能。

| Test | Endpoint | 意圖 | 邊界/情境 |
|------|----------|------|----------|
| `test_can_create_recurring_expense` | POST /api/recurring-expenses | 新增循環消費 | 回傳 201 |
| `test_can_create_recurring_expense_with_amount_range` | POST /api/recurring-expenses | 有金額範圍 | 含 amount_min 和 amount_max |
| `test_can_list_recurring_expenses` | GET /api/recurring-expenses | 列表 | 回傳分頁結構 |
| `test_can_filter_recurring_expenses_by_active_status` | GET /api/recurring-expenses?is_active= | 狀態篩選 | 篩選啟用/停用 |
| `test_can_show_recurring_expense` | GET /api/recurring-expenses/{id} | 單筆查詢 | 回傳完整資料 |
| `test_can_update_recurring_expense` | PUT /api/recurring-expenses/{id} | 更新 | 資料正確更新 |
| `test_can_delete_recurring_expense` | DELETE /api/recurring-expenses/{id} | 刪除 | 資料庫移除 |
| `test_can_get_upcoming_recurring_expenses` | GET /api/recurring-expenses/upcoming | 即將到期 | 預設 7 天內 |
| `test_can_manually_generate_expense_from_recurring` | POST /api/recurring-expenses/{id}/generate | 手動生成 | 建立消費記錄 |
| `test_can_manually_generate_expense_with_custom_amount` | POST /api/recurring-expenses/{id}/generate | 自訂金額 | 覆蓋預設金額 |
| `test_can_get_recurring_expense_history` | GET /api/recurring-expenses/{id}/history | 歷史記錄 | 該循環的所有消費 |
| `test_validates_required_fields` | POST /api/recurring-expenses | 驗證必填 | 回傳 422 |
| `test_validates_amount_max_must_be_greater_than_or_equal_to_amount_min` | POST /api/recurring-expenses | 金額範圍 | 邊界：max >= min |
| `test_validates_end_date_must_be_after_start_date` | POST /api/recurring-expenses | 日期範圍 | 邊界：end > start |
| `test_validates_frequency_type_enum` | POST /api/recurring-expenses | 驗證枚舉 | 無效值回傳 422 |
| `test_can_deactivate_and_reactivate_recurring_expense` | PUT /api/recurring-expenses/{id} | 啟用/停用 | 狀態切換 |

#### StatisticsApiTest (4 tests)

**意圖**：驗證統計分析 API 端點。

| Test | Endpoint | 意圖 | 邊界/情境 |
|------|----------|------|----------|
| `test_intents_statistics` | GET /api/statistics/intents | 意圖統計 | 各意圖筆數和金額 |
| `test_summary_statistics` | GET /api/statistics/summary | 總覽統計 | 分類統計、衝動比例 |
| `test_trends_statistics` | GET /api/statistics/trends | 趨勢統計 | 週對週變化 |
| `test_summary_with_no_data` | GET /api/statistics/summary | 無資料時 | 邊界：回傳預設值不報錯 |

#### ProcessRecurringExpensesCommandTest (6 tests)

**意圖**：驗證排程命令的正確性。

| Test | Command | 意圖 | 邊界/情境 |
|------|---------|------|----------|
| `test_processes_due_recurring_expenses` | recurring:process | 處理到期項目 | 建立消費記錄 |
| `test_skips_inactive_recurring_expenses` | recurring:process | 跳過停用 | 邊界：is_active=false |
| `test_processes_missed_occurrences` | recurring:process | 處理遺漏 | 補建所有遺漏的消費 |
| `test_dry_run_does_not_create_expenses` | recurring:process --dry-run | 預演模式 | 不實際建立資料 |
| `test_handles_month_end_edge_case` | recurring:process | 月末邊界 | 31 日 → 2 月處理 |
| `test_deactivates_expired_recurring_expenses` | recurring:process | 自動停用 | 超過結束日期 |

---

## 執行測試

```bash
# 執行所有測試
php artisan test

# 只執行 Unit 測試
php artisan test --testsuite=Unit

# 只執行 Feature 測試
php artisan test --testsuite=Feature

# 執行特定檔案
php artisan test tests/Unit/Models/RecurringExpenseTest.php

# 執行特定測試
php artisan test --filter=calculate_next_monthly_handles_month_end

# 查看覆蓋率
php artisan test --coverage
```
