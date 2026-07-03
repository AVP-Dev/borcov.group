# PROGRESS.md — Deployment Status Tracker

## Фаза -1: Empty Skeleton + Docker + Coolify Deploy
- [x] Yii2 advanced skeleton создан (composer create-project)
- [x] Dockerfile (PHP 8.4-fpm-alpine + nginx + supervisor + PostgreSQL ext)
- [x] docker-compose.yaml (app + postgres + healthcheck)
- [x] docker/entrypoint.sh (cookieValidationKey генерируется явно, migrate без || echo, php init через yes |)
- [x] docker/nginx.conf (backend/web как root, /health endpoint)
- [x] docker/supervisord.conf
- [x] SiteController::actionStatus() — публичный health-check endpoint
- [x] .gitignore обновлён
- [x] git init + push в GitHub (AVP-Dev/borcov.group:main)
- [x] Coolify: подключить репо → поддомен vibecoding.avpdev.com
- [x] **Деплой подтверждён:** 302 redirect → `/site/login`, PHP 8.4.22

---

## Фаза 0: Setup
- [x] PostgreSQL миграции: полная схема из BRIEF.md §2 (9 таблиц)
- [x] pg_trgm extension (`m260703_000001_enable_pg_trgm`)
- [x] Аутентификация admin: `console/controllers/AdminController.php`, `ADMIN_PASSWORD` env var
- [x] yii\queue (driver: db): `yiisoft/yii2-queue ^2.3.0`, таблица `queue`, supervisor worker
- [x] i18n: `PhpMessageSource`, `common/messages/en,ru/app.php`, `Yii::t()` во всех view
- [x] Language switcher: `/site/set-language` action, session-based, в навбаре
- [ ] Codeception unit/functional suites — ожидает настройки тестовой БД

### Известные проблемы (решённые)
- `yiisoft/yii2-queue` не работал в Docker build (`composer install --no-dev` exit 4) — пофикшено обновлением composer.lock
- Action name `language` — зарезервированное слово в Yii2, переименован в `set-language`
- build падал на zip расширении (aarch64) — пофикшено отдельным `docker-php-ext-install zip`
- queue падал с `Error: Failed to instantiate component or class "mutex"` — пофикшено добавлением `PgsqlMutex` в конфиг queue

### Деплой на реальный URL
- [x] **Подтверждено:** https://vibecoding.avpdev.com/ — login page, CSRF, CSS, язык EN, health endpoint OK

---

---

## Фаза 1: Import — SourceAdapter, ImportService, Queue Job

### Создано
- [x] `common/models/Source.php` — ActiveRecord для таблицы sources (4 типа: gads, search_console, ahrefs_organic, ahrefs_paid)
- [x] `common/models/ImportBatch.php` — ActiveRecord для import_batches (status: processing/done/failed)
- [x] `common/models/Keyword.php` — ActiveRecord для keywords (TimestampBehavior, все статусы/категории/интенты)
- [x] `common/components/pipeline/SourceAdapterInterface.php` — контракт `parse(string $filePath): iterable`
- [x] `common/components/pipeline/CsvAdapter.php` — читает CSV с настраиваемым columnMap (delimiter, enclosure, header)
- [x] `common/components/pipeline/JsonAdapter.php` — читает JSON с настраиваемым fieldMap, ищет rows/data/items
- [x] `common/components/pipeline/ImportService.php` — хеширует файл (SHA-256), проверяет idempotency, создаёт ImportBatch, пушит ImportJob в очередь
- [x] `common/jobs/ImportJob.php` — Queue job: выбирает адаптер по типу источника, парсит, upsert через `ON CONFLICT (normalized_text, source_id)`, обновляет статистику батча
- [x] `console/migrations/m260703_000004_seed_sources.php` — seed 4 дефолтных источника
- [x] `console/migrations/m260703_000005_add_import_unique_constraints.php` — UNIQUE на file_hash и (normalized_text, source_id)
- [x] `console/migrations/m260703_000006_fix_queue_schema.php` — renamed created_at→pushed_at, добавил delay/priority (совместимость с yiisoft/yii2-queue v2.3.8)

### Тесты
- [x] `common/tests/Unit/pipeline/CsvAdapterTest.php` — 7 тестов (разные источники, quoted fields, missing volume, missing file)
- [x] `common/tests/Unit/pipeline/JsonAdapterTest.php` — 6 тестов (Search Console JSON, custom fieldMap, invalid JSON, non-existent file)
- [x] `common/tests/Unit/pipeline/ImportServiceTest.php` — 6 тестов (создание батча, idempotency, разные источники, JSON source, ошибки)
- [x] Итого: **22 теста, 50 ассершнов** — все проходят

### Статический анализ
- [x] PHPStan level 5 — 0 ошибок в новом коде (2 pre-existing в backend/controllers и views)
- [x] Добавлены `common/components/pipeline/` и `common/jobs/` в phpstan.neon

### i18n
- [x] Добавлены 20 новых ключей (en/ru): заголовки, ошибки, статусы, названия источников

### Деплой
- [x] **Деплой подтверждён:** health OK, login page, CSRF, Bootstrap CSS

---

## Фаза 2: Cleaning Pipeline — Normalization, Cleaning, Dedup, Volume Filter

### Создано
- [x] `common/components/pipeline/NormalizationService.php` — lowercase, trim, collapse whitespace, unify спецсимволы (кавычки, тире)
- [x] `common/components/pipeline/CleaningService.php` — фильтр: короткие (<2 chars), только цифры, стоп-слова, brand check (own vs competitor), forbidden terms (exact/contains/regex), Ahrefs artifacts, уже-использованные
- [x] `common/components/pipeline/DeduplicationService.php` — pg_trgm `similarity()`, сохраняет keyword с большим volume
- [x] `common/components/pipeline/VolumeFilterService.php` — порог volume (10), исключение для ключей из 3+ источников
- [x] `common/jobs/CleanJob.php` — оркестратор: Normalization → Cleaning → Dedup → VolumeFilter; вызывается из ImportJob после upsert
- [x] `common/models/BrandTerm.php` — ActiveRecord для brand_terms (is_own_brand)
- [x] `common/models/ForbiddenTerm.php` — ActiveRecord для forbidden_terms (match_type: exact/contains/regex)
- [x] `console/migrations/m260703_000008_seed_brand_forbidden_terms.php` — seed: site.pro (own), 11 конкурентов, 12 запрещённых терминов
- [x] `common/config/params.php` — pipeline.volume.min (10), pipeline.volume.min_source_count (3), pipeline.dedup.similarity_threshold (0.6)

### Тесты
- [x] `common/tests/Unit/pipeline/NormalizationServiceTest.php` — 7 тестов (lowercase, trim, collapse, unify chars, cyrillic-latin, empty, batch)
- [x] `common/tests/Unit/pipeline/CleaningServiceTest.php` — 8 тестов (valid, too short, digits, stop word, competitor brand, own brand, forbidden exact, forbidden contains, artifact)
- [x] `common/tests/Unit/pipeline/DeduplicationServiceTest.php` — 2 теста (finds similar, no match for dissimilar)
- [x] `common/tests/Unit/pipeline/VolumeFilterServiceTest.php` — 2 теста (rejects low volume, keeps low volume in 3+ sources)
- [x] Итого 42 теста, 97 ассершнов — все проходят

### Статический анализ
- [x] PHPStan level 5 — 0 ошибок в новом коде

### i18n
- [x] 15 новых ключей (en/ru): clean.* — причины отбраковки, статусы

### Известные проблемы (решённые)
- NormalizationServiceTest: `\u{2019}` не интерпретировался в одинарных кавычках PHP — пофикшено двойными кавычками
- CleaningServiceTest: сравнение i18n-ключей вместо переведённых строк — пофикшено проверкой на суффикс ключа
- Пароль `ADMIN_PASSWORD` не применялся из env — пофикшено: `admin/set-password` читает env напрямую через getenv()

### Деплой на реальный URL
- [x] **Подтверждено:** https://vibecoding.avpdev.com/ — login, dashboard, все миграции на сервере
