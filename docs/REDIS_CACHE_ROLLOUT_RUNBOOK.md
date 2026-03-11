# Redis 快取切換操作手冊

> 最後更新：2026-02-17
> 範圍：`ledger-of-decisions-api`

## 1. 目的

本文件提供 Redis 快取切換在測試環境/正式環境的操作步驟、驗證方法、回滾流程與事件應變指引。

## 2. 前置條件

- 服務已支援 `CACHE_STORE=redis`。
- Redis 連線參數已設定：
  - `REDIS_HOST`
  - `REDIS_PORT`
  - `REDIS_PASSWORD`（如有）
- 應用 image 已包含 `phpredis` extension。
- 可執行健康檢查腳本：
  - `./scripts/redis-cache-healthcheck.sh`

## 3. 測試環境切換流程

可使用一鍵腳本（建議）：
- `./scripts/redis-cache-rollout.sh apply --yes --local --api-url https://<staging-api>/up --restart-cmd "<your-restart-command>"`

1. 設定環境變數
- `CACHE_STORE=redis`
- `CACHE_STORE_FALLBACK=database`
- Redis 連線參數指向測試環境 Redis

2. 重啟服務
- 以既有部署流程重啟 app（確保新 env 生效）。

3. 執行冒煙驗證
- API 基本健康：
  - `./scripts/redis-cache-healthcheck.sh --api-url https://<staging-api>/up --skip-cache`
- Redis 來回測試：
  - `./scripts/redis-cache-healthcheck.sh --api-url https://<staging-api>/up --skip-api --local`
  - 若在容器內部署，使用 `--docker`

4. 觀測 30 分鐘以上
- API 延遲（P95/P99）
- 5xx 錯誤率
- Redis 連線錯誤（逾時/連線被拒）
- 快取命中率（若監控可得）

## 4. 正式環境漸進切換流程

建議在低流量時段執行，採漸進策略：

1. 先切小流量節點（金絲雀部署）
- 先切 1 台或小比例流量。
- 持續觀測 15-30 分鐘。

2. 擴大流量
- 無異常後，逐步擴大到 50%，再到 100%。

3. 每階段必做檢查
- `GET /up` 正常
- 核心 API（登入、忘記密碼、驗證碼）可用
- Redis 相關錯誤未上升

## 5. 回滾策略

若出現以下任一情況，立即回滾：
- 5xx 錯誤率持續升高
- 驗證碼流程大量失敗（登入/重設密碼/驗證信箱）
- Redis 逾時/連線被拒持續發生

回滾步驟：

可使用一鍵腳本（建議）：
- `./scripts/redis-cache-rollout.sh rollback --yes --local --api-url https://<api>/up --restart-cmd "<your-restart-command>"`

1. 設定 `CACHE_STORE=database`
2. 保留 `CACHE_STORE_FALLBACK=database`
3. 重啟 app
4. 執行健康檢查：
- `./scripts/redis-cache-healthcheck.sh --skip-cache --api-url https://<api>/up`
5. 追蹤 15-30 分鐘確認穩定

## 6. 事件應變手冊（Redis 相關）

### A. Redis 無法連線（連線被拒 / 逾時）

1. 先確認 Redis 服務/實例是否存活。
2. 驗證網路與防火牆（app -> redis）。
3. 立即切回 `CACHE_STORE=database`（避免影響登入與驗證碼流程）。
4. 建立事件 ticket，記錄時間點與錯誤率。

### B. 驗證碼流程異常（OTP 驗證失敗率升高）

1. 檢查 app log 是否有 cache 寫入/讀取異常。
2. 驗證 Redis 來回測試：
  - `./scripts/redis-cache-healthcheck.sh --skip-api --docker`
3. 異常持續則回滾 `CACHE_STORE=database`。

### C. Redis 記憶體壓力過高

1. 先查看 Redis 記憶體使用量與資料驅逐狀態。
2. 評估 key TTL 是否過長（本專案驗證碼類 key TTL 已限制）。
3. 必要時擴容 Redis 或回滾快取儲存設定。

## 7. 驗收檢查清單

- [ ] 測試環境切換完成並穩定觀測
- [ ] 正式環境漸進切換完成
- [ ] 核心 API 無回歸
- [ ] 回滾流程已演練至少一次
- [ ] 事件 SOP 可由值班工程師獨立執行

## 8. 部署方式建議（尚未定案時）

若目前尚未決定部署方式，建議先用以下順序評估：

1. Docker Compose on VM（最快落地）
- 適合小型團隊、單一服務、希望先把流程跑起來。
- 可直接重用本專案既有 `docker-compose.yml` 與腳本。
- 缺點是擴展性與高可用性較有限（需自行補監控與備援）。

2. 託管容器平台（中期推薦）
- 例如 Cloud Run / App Runner / Azure Container Apps（依雲供應商選）。
- 優點是維運成本低、部署一致性高、滾動更新與回滾較簡單。
- 缺點是平台限制較多，需調整一些基礎設定。

3. Kubernetes（長期、規模化）
- 適合多服務、流量大、需要完整調度/高可用性/可觀測性。
- 優點是彈性高；缺點是維運與學習成本最高。

### 建議決策標準

- 團隊維運能力：是否有專人管理基礎設施。
- 變更頻率：每週部署次數是否高。
- 可用性需求：是否需要多區、快速故障轉移。
- 預算與時間：先求穩定上線，還是一次到位。

### 目前專案建議路線

- 短期（現在）：`Docker Compose on VM` + 本操作手冊（先穩定上線）
- 中期（1-2 個月）：導入 `backend-cd-staging.yml` 與自動化部署
- 長期（需求成長後）：再評估遷移到託管平台或 Kubernetes
