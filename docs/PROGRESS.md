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

### Известные проблемы (решённые)
- NormalizationServiceTest: `\u{2019}` не интерпретировался в одинарных кавычках PHP — пофикшено двойными кавычками
- CleaningServiceTest: сравнение i18n-ключей вместо переведённых строк — пофикшено проверкой на суффикс ключа
- Пароль `ADMIN_PASSWORD` не применялся из env — пофикшено: `admin/set-password` читает env напрямую через getenv()

### Деплой на реальный URL
- [x] **Подтверждено:** https://vibecoding.avpdev.com/ — login, dashboard, все миграции на сервере

---

## Фаза 3: Classification — rule-based classifier (category/audience_segment/intent)

### Создано
- [x] `common/config/classification.php` — конфигурируемые правила: 7 категорий + 3 интента + B2B-сегмент, en/ru паттерны
- [x] `common/components/pipeline/ClassificationService.php` — rule-based классификатор: category (6 продуктовых + general_brand + unclassified), audience_segment (b2c/b2b), intent (commercial/informational/navigational/unknown)
- [x] `common/jobs/ClassificationJob.php` — queue job: классифицирует все keywords batch, проставляет category/intent/audience_segment
- [x] CleanJob доработан: после VolumeFilter пушит ClassificationJob в очередь

### Тесты
- [x] `common/tests/Unit/pipeline/ClassificationServiceTest.php` — 30 тестов, 36 ассершнов
   - Все 7 категорий (en + ru): website_builder, email, domains, accounting, invoicing, reseller, general_brand
   - Unclassified fallback
   - Все 4 интента (en + ru): commercial, informational, navigational, unknown
   - B2B/B2C аудитория (en + ru)
   - Edge cases: пустой текст, null язык, whitespace, смешанный en/ru, site.pro без точки

### Статический анализ
- [x] PHPStan level 5 — 0 ошибок

### Деплой на реальный URL
- [x] **Подтверждено:** https://vibecoding.avpdev.com/ — login работает, дашборд открывается, нет 500

---

## Фаза 4: Admin UI — Dashboard, Import & Keyword Management

### Создано
- [x] `backend/controllers/ImportController.php` — actions: index (upload form), upload (process file), batches (batch list)
- [x] `backend/views/import/index.php` — upload form with source selector + file input
- [x] `backend/views/import/batches.php` — GridView: all import batches with source/status/counts
- [x] `backend/controllers/KeywordController.php` — actions: index (filterable GridView), override (admin status change)
- [x] `backend/views/keyword/index.php` — GridView with filters (source/status/category/intent/search), override dropdown with JS
- [x] Navbar обновлен: Import, Keywords ссылки для authenticated users
- [x] Dashboard (`backend/views/site/index.php`): реальные stats (imports, ready, total, rejected) + pipeline status breakdown + quick actions

### i18n
- [x] 20+ новых ключей (en/ru): dashboard.* (stats/actions), nav.* (Import/Keywords), status.*, keywords.* (фильтры/колонки/override)

### Статический анализ
- [x] PHPStan level 5 — 0 ошибок

### Деплой на реальный URL
- [x] **Подтверждено:** https://vibecoding.avpdev.com/ — login, dashboard, import/upload, batches page, keywords table

---

## Фаза 5: Post-Phase-4 Bugfixes & Improvements

Все изменения задеплоены до перехода к Фазе 5–8 из брифа.

### Search Console CSV адаптация
- [x] `createAdapter()` выбирает адаптер по расширению файла (`.csv` → CsvAdapter, `.json` → JsonAdapter), не по типу источника
- [x] CsvAdapter: columnMap поддерживает массивы fallback-имён колонок (первое совпадение wins), поиск case-insensitive
- [x] Search Console CSV: маппинг `Search query`/`Поисковый запрос` → keyword
- [x] `common/components/pipeline/CsvAdapter.php` — доработан
- [x] `common/components/pipeline/ImportService.php` — доработан createAdapter()
- [x] Тесты: `CsvAdapterTest` — 2 новых теста (Search Console CSV, case-insensitive)
- [x] Тестовые данные: `common/tests/Support/data/search_console.csv`, `common/tests/Support/data/search_console.json`
- [x] **Деплой подтверждён**

### Error resilience в queue jobs
- [x] ImportJob: try/catch с `Yii::error()`, помечает batch как failed при исключении
- [x] CleanJob: try/catch с `Yii::error()`, пробрасывает исключение для retry в очереди
- [x] ClassificationJob: try/catch с `Yii::error()`, пробрасывает исключение для retry
- [x] Queue config: `queue.ttr = 300`, `queue.attempts = 3`
- [x] Upload validation: только .csv/.json, max 20MB, temp file cleanup после ImportJob
- [x] Batches page UX: auto-refresh каждые 3s во время processing, spinner, JS timezone conversion для imported_at
- [x] **Деплой подтверждён**

### Language detection
- [x] `NormalizationService::detectLanguage(string $text): string` — `preg_match('/\p{Cyrillic}/u', $text) ? 'ru' : 'en'`
- [x] `CleanJob.php` — вызывает `detectLanguage()` после `normalize()`, сохраняет в `keyword->language`
- [x] Root cause: `language` никогда не заполнялся → `classify()` видел `'en'` → не применял русские паттерны → все ru-ключи были unclassified
- [x] Тесты: `NormalizationServiceTest` — 3 новых теста (detect ru, detect en, detect mixed)
- [x] **Деплой подтверждён**

### Расширенные ru-паттерны классификации
- [x] website_builder: добавлены `'конструктор'`, `'бесплатный конструктор'`, `'как сделать сайт'`, `'как создать сайт'`, `'как сделать интернет магазин'`
- [x] informational intent ru: добавлены `'бесплатный'`, `'бесплатная'`, `'бесплатные'` (было только `'бесплатно'` — наречие, не совпадало с прилагательными)
- [x] `common/config/classification.php` — обновлён
- [x] **Деплой подтверждён**

### rejection_reason display fix
- [x] `backend/views/keyword/index.php` — колонка `rejection_reason` обёрнута в `Yii::t('app', $model->rejection_reason)` c fallback (если в БД уже переведённая строка — `Yii::t()` возвращает как есть)
- [x] **Деплой подтверждён**

### Regression tests (5 ru-фраз)
- [x] `'как сделать сайт'` → website_builder + informational
- [x] `'создать сайт бесплатно'` → website_builder + informational
- [x] `'бесплатный конструктор'` → website_builder + informational
- [x] `'конструктор интернет магазина'` → website_builder
- [x] `'почта для домена'` → email
- [x] **Деплой подтверждён**

### pg_trgm fuzzy brand detection
- [x] `CleaningService::$brandFuzzyThreshold = 0.6`
- [x] `CleaningService::checkBrandFuzzy()` — word-level pg_trgm similarity через `regexp_split_to_table()` (сравнение по словам, а не по всей фразе — предотвращает разбавление триграммами соседних слов)
- [x] Own brands приоритетны: `ORDER BY is_own_brand DESC`
- [x] Fallback: вызывается после exact match (`str_contains`) если текст >= 3 символов
- [x] Тест: `'quarespace website builder'` → brand match на `'squarespace'` (similarity=0.6)
- [x] **Деплой подтверждён**

### ClassificationJob для всех ключей (включая rejected)
- [x] `ClassificationJob` теперь запрашивает `Keyword::find()->where(['batch_id' => $this->batchId])` без фильтра по статусу
- [x] `STATUS_READY` выставляется только для `STATUS_CLEANED`; rejected-ключи сохраняют `STATUS_REJECTED`
- [x] Gap Analysis теперь видит корректные категории для всех ключей, а не сплошной `unclassified`
- [x] **Деплой подтверждён**

---

## Сводка тестового покрытия

| Компонент | Файл | Тестов |
|-----------|------|--------|
| CsvAdapter | `CsvAdapterTest.php` | 9 |
| JsonAdapter | `JsonAdapterTest.php` | 8 |
| ImportService | `ImportServiceTest.php` | 6 |
| NormalizationService | `NormalizationServiceTest.php` | 10 |
| CleaningService | `CleaningServiceTest.php` | 10 |
| DeduplicationService | `DeduplicationServiceTest.php` | 2 |
| VolumeFilterService | `VolumeFilterServiceTest.php` | 2 |
| ClassificationService | `ClassificationServiceTest.php` | 35 |
| LoginForm | `LoginFormTest.php` | 3 |
| GapAnalysisService | `GapAnalysisServiceTest.php` | 6 |
| GroupingService | `GroupingServiceTest.php` | 4 |
| TemplateAdGenerator | `TemplateAdGeneratorTest.php` | 5 |
| LlmAdGenerator | `LlmAdGeneratorTest.php` | 5 |
| **Итого** | | **105 тестов, 192 assertions** |

### Статический анализ
- [x] PHPStan level 5 — **0 errors**

---

## Pipeline flow (production)

```
ImportJob (upsert, hash idempotency)
  → CleanJob (normalize + detect language + clean + dedup + volume filter)
    → ClassificationJob (classify ALL keywords, set READY only for cleaned)
      → GapAnalysisService (brand-filtered ahrefs_paid MINUS gads ∪ sc via pg_trgm)
      → Grouping / Export
```

---

## Что реализовано в этой сессии (Phase 5)

### Gap Analysis Service + UI

- **GapAnalysisService** (`common/components/pipeline/GapAnalysisService.php`): `analysis()` with pg_trgm `similarity()` in `NOT EXISTS` subquery — Ahrefs Paid keywords minus (GAds OR Search Console), filtered by `volume >= minVolume`; brand exclusion via exact `str_contains` + word-level fuzzy match against `brand_terms` (`is_own_brand = false`), own-brand override via `is_own_brand = true`
- **Bugfix (brand filter):** Gap analysis previously selected ALL ahrefs_paid keywords without brand check, so competitor-brand keywords ("wix конструктор", "tilda конструктор") appeared as gap candidates despite being rejected in the pipeline. Fixed by adding inline brand check matching `CleaningService` logic — own brand exact match overrides competitor exclusion; competitor brand exact/fuzzy match excludes from gap pool.
- **GapAnalysisController** (`backend/controllers/GapAnalysisController.php`): `actionIndex()` renders GridView with category/intent grouping
- **View** (`backend/views/gap-analysis/index.php`): sortable GridView, pagination 50/page, category/intent grouping disambiguation, `language` badge, volume totals per group
- **Navbar**: "Gap Analysis" link with `bi-graph-up-arrow` icon
- **i18n**: keys `gap.*` and `nav.gap_analysis` in both en + ru
- **Tests**: 6 tests — gap candidate found, existing keyword excluded, fuzzy match excluded, low-volume filtered, competitor brand excluded (regression for "wix конструктор"/"tilda конструктор"), result structure
- **Деплой + ручная верификация подтверждены** — 6 gap-кандидатов, brand-exclusion работает корректно

### Phase 6 — Grouping & Ad Generation (done, deploy pending)

- **Модели:** `AdGroup`, `AdGroupKeyword` (pivot), `Ad` — Active Record модели для таблиц `ad_groups`, `ad_group_keywords`, `ads`
- **GroupingService** (`common/components/pipeline/GroupingService.php`): `groupAll()` кластеризует `ready`-ключи в `ad_groups` по `(category, audience_segment, language)`, создаёт/пропускает существующие группы, линкует ключи M2M, опционально генерирует объявления через переданный `AdGeneratorInterface`
- **AdData** (`common/components/pipeline/AdData.php`): value object для сгенерированного RSA-объявления
- **AdGeneratorInterface** (`common/components/pipeline/AdGeneratorInterface.php`): контракт с `generate(AdGroup, Keyword): AdData[]`
- **TemplateAdGenerator** (`common/components/pipeline/TemplateAdGenerator.php`): MVP-шаблонизация — 5 headline/3 description паттернов на en + ru, подстановка `{keyword}` и `{usp}`, маппинг категорий → target URL (`/website-builder`, `/email`, `/domains`, `/accounting`, `/invoicing`, `/reseller`, `/`), USP по (category, language) из `uspMap`
- **LlmAdGenerator** (`common/components/pipeline/LlmAdGenerator.php`): через DeepSeek API (`api.deepseek.com/v1/chat/completions`), промпт для RSA-генерации с JSON-ответом, HTTP-клиент через callable (инъекция для тестов), fallback на `TemplateAdGenerator` при: пустом API-ключе, ошибке соединения, не-200 статусе, непарсибельном JSON, пустом результате
- **AdGroupsController** (`backend/controllers/AdGroupsController.php`): `actionIndex()` — список групп, `actionGenerate()` — POST-генерация, `actionView($id)` — просмотр группы с объявлениями и inline-редактированием
- **View** (`backend/views/ad-groups/index.php`): GridView с группами, кнопка генерации
- **View** (`backend/views/ad-groups/view.php`): детали группы, список ключей, GridView объявлений, модальное окно inline-редактирования (headline_1/2, description_1, final_url) через JS
- **Navbar**: "Ad Groups" link, i18n ключи `nav.ad_groups` + `ad.*` + `ad_groups.*` в en/ru
- **Tests**: 4 GroupingService (группировка, идемпотентность, без ready, с генератором), 5 TemplateAdGenerator (структура/длина/URL/подстановка/ру категория), 5 LlmAdGenerator (fallback на ошибку/пустой ключ/не-JSON/таймаут, парсинг ответа)
- **Верификация:** 105 тестов, 192 assertions, PHPStan 0 errors (level 5)

## Что не реализовано (из BRIEF.md §§3–4)

- **Фаза 7:** ExportService (п.10) + export history UI
- **Фаза 7:** ExportService (п.10) + export history UI
- **§4**: Settings page (volume threshold, forbidden/brand terms editors)
- **README**: билингвальная документация (EN + RU) — требуется по BRIEF.md §6
