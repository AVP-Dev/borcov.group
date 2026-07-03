# Marketing Keyword Automation Platform

> **Live:** [https://vibecoding.avpdev.com](https://vibecoding.avpdev.com)

A pipeline-based system for importing, cleaning, classifying, grouping, and exporting marketing keywords for Google Ads campaigns. Built for **site.pro** homework — demonstrates product thinking combined with AI-orchestrated development.

**Stack:** Yii2 (PHP 8.4), PostgreSQL (pg_trgm), Bootstrap 5, DeepSeek API

---

## English

### Architecture

The system processes keywords through a sequential pipeline of independent services, each with a single responsibility (SOLID):

```
Import → Normalize → Clean → Dedup → VolumeFilter → Classify → Group → Generate Ads → Export
```

| Step | Service | What it does |
|------|---------|-------------|
| 1 | `ImportService` | Parses CSV/JSON from 4 sources (Google Ads, Search Console, Ahrefs Organic, Ahrefs Paid). Idempotent (SHA-256 hash) |
| 2 | `NormalizationService` | Lowercase, trim, collapse whitespace, unify special chars, detect language (cyrillic → ru) |
| 3 | `CleaningService` | Filters junk (short/digit/stopwords), detects brands (own vs competitor), checks forbidden terms, finds already-used keywords |
| 4 | `DeduplicationService` | PostgreSQL `pg_trgm` fuzzy match — finds similar keywords, keeps the one with highest volume |
| 5 | `VolumeFilterService` | Removes low-volume keywords (configurable threshold), except those appearing in 3+ sources |
| 6 | `ClassificationService` | Rule-based classifier: 7 product categories, 4 intents (commercial/informational/navigational), B2B/B2C |
| 7 | `GapAnalysisService` | Ahrefs Paid minus (GAds ∪ Search Console) — finds competitor keywords not covered by site.pro |
| 8 | `GroupingService` | Clusters ready keywords into ad groups by (category, audience, language) |
| 9 | `AdGenerationService` | Generates RSA headlines/descriptions via template or DeepSeek API |
| 10 | `ExportService` | Builds Google Ads Editor CSV (Campaign, Keywords, Headlines 1-15, Descriptions 1-4, Final URL) |

All pipeline steps run asynchronously via `yii\queue` (DB driver) — no web request timeouts.

### Admin Panel

- **Dashboard** — stats: imports, ready keywords, pipeline status
- **Import** — upload CSV/JSON, select source type, track batch status
- **Keywords** — filterable table with manual status override
- **Gap Analysis** — competitor keyword opportunities report
- **Ad Groups** — generate groups and ads (template or LLM-powered)
- **Export** — grouped by ad group, select individual ads, direct CSV download
- **Settings** — volume threshold, forbidden terms, brand terms management

i18n: English / Russian interface with session-based language switch.

### Test Data

Sample CSV/JSON files for all 4 source types are included in `common/tests/Support/data/`.

### Vibecoding Log

- **Architecture & schema** designed by human (Aliaksei Patskevich)
- **Implementation & tests** by AI agent (Claude Code)
- **Phases:** Brief → Phase 0 (Setup) → Phases 1-7 (Pipeline + UI) → Phase 6.5 (Category ads) → Phase 7.2-7.4 (Grouped export, filters, direct download) → Settings page
- **Total tests:** 130 unit tests, 286 assertions
- **Static analysis:** PHPStan level 5 — 0 errors
- **Deploy:** Automated via GitHub → Coolify → Docker → https://vibecoding.avpdev.com

### Roadmap (not implemented)

- Integration with self-hosted LLMs as alternative to DeepSeek API
- RBAC (admin / editor / viewer)
- API source integrations (Google Ads API, Search Console API)
- Auto-detect source type from file structure
- A/B ad testing with CTR statistics
- Workspace isolation for multiple campaigns/projects
- Visual UI modernization

---

## Русский

### Архитектура

Система обрабатывает ключевые слова через последовательный пайплайн независимых сервисов (SOLID):

```
Импорт → Нормализация → Очистка → Дедупликация → Фильтр Volume → Классификация → Группировка → Генерация объявлений → Экспорт
```

| Шаг | Сервис | Что делает |
|-----|--------|-----------|
| 1 | `ImportService` | Парсит CSV/JSON из 4 источников (Google Ads, Search Console, Ahrefs). Идемпотентность через SHA-256 |
| 2 | `NormalizationService` | Lowercase, trim, спецсимволы, определение языка |
| 3 | `CleaningService` | Фильтр мусора, детекция брендов (свой/конкурент), запрещённые термины |
| 4 | `DeduplicationService` | Нечёткое сравнение через pg_trgm, оставляет ключ с макс. volume |
| 5 | `VolumeFilterService` | Удаляет низкообъёмные ключи (кроме 3+ источников) |
| 6 | `ClassificationService` | 7 категорий продуктов, 4 интента, B2B/B2C |
| 7 | `GapAnalysisService` | Ключи конкурентов из Ahrefs, которых нет в GAds/Search Console |
| 8 | `GroupingService` | Кластеризация в ad groups по (категория, аудитория, язык) |
| 9 | `AdGenerationService` | Генерация RSA через шаблоны или DeepSeek API |
| 10 | `ExportService` | CSV для Google Ads Editor |

Асинхронность через `yii\queue` (драйвер БД) — веб-запросы не таймаутят.

### Панель администратора

- **Dashboard** — статистика импортов и пайплайна
- **Import** — загрузка CSV/JSON, отслеживание статуса батча
- **Keywords** — таблица с фильтрами, ручной override статуса
- **Gap Analysis** — отчёт по возможностям конкурентов
- **Ad Groups** — генерация групп и объявлений (шаблон / AI)
- **Export** — сгруппирован по ad groups, скачивание CSV
- **Settings** — порог volume, запрещённые и брендовые термины

Интерфейс на английском и русском, переключение языка в шапке.

### Vibecoding Log

- **Архитектура и схема** — человек (Aliaksei Patskevich)
- **Реализация и тесты** — AI-агент (Claude Code)
- **Фазы:** Бриф → Setup → Import → Cleaning → Classification → UI → Gap Analysis → Ad Groups → Export → Settings
- **Тестов:** 130 unit-тестов, 286 assertions
- **Статический анализ:** PHPStan level 5 — 0 ошибок
- **Деплой:** Автоматически через GitHub → Coolify → Docker

### Roadmap (не реализовано)

- Интеграция с локальными LLM
- RBAC (admin / editor / viewer)
- API-источники (Google Ads API, Search Console)
- Автоопределение источника по структуре файла
- A/B тестирование объявлений
- Изоляция проектов/кампаний (workspaces)
- Визуальная модернизация интерфейса

---

## License

[MIT](LICENSE.md)
