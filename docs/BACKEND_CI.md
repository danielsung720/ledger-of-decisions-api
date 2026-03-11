# 後端 CI 策略

> 最後更新：2026-02-17

## 範圍

本文件定義 `ledger-of-decisions-api` 的基準 CI 策略。

## 第一階段：基準

- 工作流程：`.github/workflows/backend-ci.yml`
- 觸發條件：`pull_request`、`push`、`workflow_dispatch`
- 品質關卡：
  - `./vendor/bin/pint --test`（CI 中只對變更的 PHP 檔案執行）
  - `php artisan test`

## 第二階段：品質關卡

- 靜態分析：
  - 工作項目：`backend-static-analysis`
  - 工具：`phpstan/phpstan`（`phpstan.neon.dist`）
  - 目前範圍：`app/**` 和 `routes/**` 下變更的 PHP 檔案
  - 備註：全倉庫掃描仍有歷史技術債（例如 `routes/console.php`），因此 CI 目前優先針對新增或變更的程式碼執行。
- 覆蓋率關卡：
  - 工作項目：`backend-coverage`
  - 指令：`make coverage-ci COVERAGE_MIN=85`
  - 門檻：`85%`（基準穩定後可逐步提高）
- 失敗診斷：
  - PHPStan 和覆蓋率工作項目會列印摘要失敗資訊，並發出 `::error` 註解。

## 第三階段：安全性

- 安全性審計：
  - 工作項目：`backend-security-audit`
  - 工具：`composer audit --format=plain`
  - 觸發條件：每次 PR 和 push（與 `backend-ci` 相同）
  - 失敗時：發出 `::error` 註解，並在啟用分支保護時封鎖合併
- 套件更新：
  - 工具：Dependabot（`.github/dependabot.yml`）
  - 排程：每週一 09:00 Asia/Taipei
  - 生態系：`composer`、`npm`（web + e2e）、`github-actions`
  - Laravel 套件合併為單一 PR（`laravel/*`）
- 弱點 SLA：請參閱 `docs/SECURITY_SLA.md`

## 第四階段：Docker 建置驗證

- Docker 建置：
  - 工作項目：`backend-docker-build`
  - 工具：`docker/build-push-action@v6`，使用 `push: false`
  - 上下文：`ledger-of-decisions-api/`，Dockerfile：`ledger-of-decisions-api/Dockerfile`
  - 目的：驗證正式映像檔在每次 PR 時能成功建置；不推送至任何 Registry
- 建置快取：
  - 類型：`type=gha`（GitHub Actions 快取）
  - 範圍：`backend-docker`（與其他快取範圍隔離）
  - 模式：`max`（快取所有層，包含中間層）
- PR 可見性：工作項目作為必要狀態檢查出現；失敗時顯示在 PR 檢查清單中，並透過 `docker/build-push-action` 發出 `::error` 註解

## 資料庫策略

CI 使用 **MySQL 8.0 服務**（而非 sqlite），以降低與正式環境的差異風險。

原因：
- 正式環境規劃使用 MySQL。
- 在 CI 使用 MySQL 能提早發現 SQL、Migration、排序規則的行為差異。
- 降低因 sqlite/mysql 差異導致「CI 通過但正式環境壞掉」的機率。

CI 資料庫環境變數：
- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=ledger_test`
- `DB_USERNAME=ledger`
- `DB_PASSWORD=ledger`

## Pint 範圍（目前）

- 因既有檔案有格式化技術債，CI 目前僅對**變更的 PHP 檔案**執行 Pint。
- 這讓新增變更的 PR 關卡持續有效，同時避免因歷史問題造成永久紅燈。
- 長期目標仍是全倉庫 Pint 合規。

## 本機重現（選用）

在 Docker 中本機執行相同的基準檢查：

```bash
cd ledger-of-decisions-api
docker compose exec -T app php artisan migrate --force
docker compose exec -T app ./vendor/bin/pint --test
docker compose exec -T app php artisan test
docker compose exec -T app ./vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --memory-limit=1G
docker compose exec -T app php artisan test --coverage --min=85
```

## 需手動設定的 Repo 設定

分支保護必須在 GitHub UI 中設定：
- 要求狀態檢查：`backend-ci`
- `backend-ci` 失敗時封鎖合併
