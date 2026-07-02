# Marketing Keyword Automation Platform

**Live URL:** https://vibecoding.avpdev.com  
**Status:** Phase -1 (skeleton verification)

---

## English

### What is this?

A test assignment for [site.pro](https://site.pro) — a keyword cleaning, classification, and preparation system for Google Ads campaigns, based on 4 data sources (Google Ads, Search Console, Ahrefs Organic, Ahrefs Paid).

**Goal:** Upload CSV/JSON keyword exports → auto-pipeline → admin review → export Google Ads Editor CSV.

### Why PostgreSQL (not MySQL)?

The `pg_trgm` extension provides trigram-based fuzzy matching (`similarity()`, `%` operator) directly in SQL — critical for `DeduplicationService` which otherwise would require Levenshtein distance in PHP loops across thousands of rows. Plus JSONB for flexible metadata storage from heterogeneous sources.

### Architecture

```
ImportService → NormalizationService → CleaningService
→ DeduplicationService → VolumeFilterService
→ ClassificationService → GroupingService
→ AdGenerationService → ExportService
```

Each step: single service class, single responsibility (SOLID), explicit event emission, async via `yii\queue` (DB driver).

### Stack

- **Backend:** Yii2 (PHP 8.2), PostgreSQL 16
- **Queue:** `yii\queue` with `db` driver (no Redis required)
- **Tests:** Codeception (Unit + Functional suites)
- **i18n:** Yii2 built-in, en/ru
- **Deploy:** Docker (PHP-FPM + nginx + supervisor), Coolify

### Quick Start (Docker)

```bash
cp .env.example .env
# Edit .env with your values
docker compose up
```

App available at `http://localhost:8080`

### Health Check

```bash
curl https://vibecoding.avpdev.com/site/status
# → {"status":"OK","app":"Marketing Keyword Automation Platform",...}
```

---

## Русский

### Что это?

Тестовое задание для [site.pro](https://site.pro) — система очистки, классификации и подготовки ключевых слов для Google Ads на основе 4 источников данных (Google Ads, Search Console, Ahrefs Organic, Ahrefs Paid).

**Цель:** загрузить CSV/JSON выгрузки ключевых слов → авто-пайплайн → ревью в админке → экспорт в Google Ads Editor CSV.

### Почему PostgreSQL (не MySQL)?

Расширение `pg_trgm` даёт триграммный fuzzy-match (`similarity()`, оператор `%`) прямо на уровне SQL — критично для `DeduplicationService`, который иначе пришлось бы реализовывать через Левенштейн в PHP-коде на тысячах строк. Плюс JSONB для гибкого хранения разнородных метаданных из разных источников.

### Архитектура

```
ImportService → NormalizationService → CleaningService
→ DeduplicationService → VolumeFilterService
→ ClassificationService → GroupingService
→ AdGenerationService → ExportService
```

Каждый шаг — отдельный сервис-класс, одна ответственность (SOLID), явные события, асинхронность через `yii\queue` (драйвер: db).

### Стек

- **Backend:** Yii2 (PHP 8.2), PostgreSQL 16
- **Очередь:** `yii\queue` с драйвером `db` (не требует Redis)
- **Тесты:** Codeception (Unit + Functional suites)
- **i18n:** встроенный Yii2 i18n, en/ru
- **Деплой:** Docker (PHP-FPM + nginx + supervisor), Coolify

### Быстрый старт (Docker)

```bash
cp .env.example .env
# Заполни .env своими значениями
docker compose up
```

Приложение доступно по `http://localhost:8080`

### Проверка работоспособности

```bash
curl https://vibecoding.avpdev.com/site/status
# → {"status":"OK","app":"Marketing Keyword Automation Platform",...}
```

---

## Vibecoding Log

> *This section is explicitly required by site.pro: demonstrate AI-orchestrated engineering, not just "I pressed generate".*

**Architecture & data schema** — designed by human (Aliaksei Patskevich).  
**Implementation & tests** — AI agents (Claude Sonnet via Antigravity IDE).

### Chronology

| Phase | What | When |
|-------|------|------|
| Briefing | Requirements analysis, schema design, phase breakdown | Day 1 |
| Phase -1 | Empty skeleton + Docker + live URL verification | Day 1 |
| Phase 0 | DB migrations + auth + queue + i18n | TBD |
| Phase 1-7 | Pipeline services (TDD: tests first, then controllers) | TBD |
| Phase 8 | Final verification + README | TBD |

### Key Prompting Strategy

1. **Deploy-first:** Фаза -1 обязательна до написания бизнес-логики — пустой skeleton на реальном URL убирает всю неопределённость деплоя
2. **Lessons captured:** `AGENTS.md` содержит список конкретных ошибок из предыдущей попытки, агент читает его перед каждой фазой
3. **TDD enforcement:** тесты пишутся до контроллеров — фиксирует контракт до того, как агент начнёт "додумывать" поведение
4. **No silent failures:** `|| echo` запрещён в entrypoint — каждый сбой должен быть виден в логах Coolify

Total time from brief to live URL: ~2 hours (Phase -1 only at this stage)
