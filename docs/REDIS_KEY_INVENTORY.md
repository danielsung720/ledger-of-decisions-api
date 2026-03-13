# Redis Key Inventory (Phase 1)

> 最後更新：2026-03-13

## 命名原則

- 命名空間：`ledger-api`
- 分隔：冒號 `:`
- 命名格式：PascalCase segment
- 不在 key 內放 `env`，環境隔離由 Redis instance/DB 層級保證

## 過渡策略

- 既有 key 進入相容期：`dual-read + dual-write`
- 讀取順序：new key -> legacy key
- 寫入順序：new key + legacy key 同步寫入
- 清除策略：new key + legacy key 同步清除

## Inventory

| Owner | Purpose | Legacy Pattern | New Pattern | TTL | Strategy |
|---|---|---|---|---|---|
| `UserPreferencesService` | 使用者偏好設定 | `{app}:{env}:user_preferences:{user_id}` | `ledger-api:UserPreferences:User:{user_id}` | forever | dual-read/dual-write |
| `VerificationCodeService` | OTP code | `{app}:{env}:verification:{type}:{email_hash}:code` | `ledger-api:Verification:{type}:{email_hash}:Code` | 600s | dual-read/dual-write |
| `VerificationCodeService` | OTP resend cooldown | `{app}:{env}:verification:{type}:{email_hash}:sent_at` | `ledger-api:Verification:{type}:{email_hash}:SentAt` | 60s | dual-read/dual-write |
| `VerificationCodeService` | OTP attempts/lockout | `{app}:{env}:verification:{type}:{email_hash}:attempts` | `ledger-api:Verification:{type}:{email_hash}:Attempts` | 900s | dual-read/dual-write |
| `ApiReadCacheService` | Read cache entry | N/A (new) | `ledger-api:ReadCache:{Domain}:{Endpoint}:User:{user_id}:Version:{version}:Query:{query_hash}` | endpoint TTL | new |
| `ApiReadCacheService` | Read cache version | N/A (new) | `ledger-api:ReadCache:{Domain}:Version:User:{user_id}` | forever | new |

## Enum 白名單

- Domain: `Statistics`, `CashFlow`, `Expenses`
- Endpoint: `Summary`, `Trends`, `CashFlowSummary`, `CashFlowProjection`, `Index`

