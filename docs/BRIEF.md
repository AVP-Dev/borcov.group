# Бриф: Marketing Keyword Automation Platform (site.pro homework)

## 0. Контекст и цель

Тестовое задание от site.pro (конструктор сайтов + email/домены/бухгалтерия/выставление счетов/reseller-программа для хостинг-провайдеров). Формат: "0 code", vibecoded на Yii2. Задача — не просто рабочий скрипт, а демонстрация продуктового мышления в связке с AI-оркестрацией: система очистки, классификации и подготовки ключевых слов для Google Ads на основе 4 источников данных.

Итог должен быть доступен по публичному URL: upload → admin-area → preview → export.

**Стек:** Yii2 (PHP), **PostgreSQL** (осознанный выбор, не MySQL — см. обоснование ниже), простой Bootstrap/Vue-фронт для админки (без переусложнения — задача не про фронтенд).

**Почему PostgreSQL:** расширение `pg_trgm` даёт триграммный fuzzy-match (`similarity()`, оператор `%`) прямо на уровне SQL — критично для `DeduplicationService` (п.3, шаг 4), который иначе пришлось бы реализовывать через Левенштейн в PHP-коде на тысячах строк. Плюс JSONB для гибкого хранения сырых метаданных из разнородных источников (Ahrefs/GAds/Search Console отдают разные наборы полей).

**Деплой:** публичный поддомен на avpdev.com (например vibecoding.avpdev.com).

---

## 1. Продуктовые направления site.pro (для маппинга target URL)

Из карты продукта:
- **Конструктор сайтов** (website builder) — B2C/малый бизнес
- **Эл. почта** (email) — B2C/малый бизнес
- **Домены** (domains) — B2C/малый бизнес
- **Бухгалтерия / ERP** (accounting) — B2C/малый бизнес, более высокий intent
- **Выставление счетов** (invoicing) — B2C/малый бизнес
- **Перепродажа** (reseller, для хостинг-провайдеров) — B2B, отдельный сегмент аудитории

Каждый keyword должен маппиться на одну из этих категорий (+ fallback "general/brand" для навигационных запросов типа "site.pro").

---

## 2. Схема данных

```sql
-- Источники и импорт
sources (
  id, name, type ENUM('gads','search_console','ahrefs_organic','ahrefs_paid'),
  created_at
)

import_batches (
  id, source_id FK, filename, file_hash, -- для idempotent re-import
  imported_at, rows_total, rows_accepted, rows_rejected, status ENUM('processing','done','failed')
)

-- Ключевые слова
keywords (
  id, batch_id FK, source_id FK,
  raw_text, normalized_text,
  volume INT NULL,
  language VARCHAR(5), -- ISO code, детект или из источника
  category ENUM('website_builder','email','domains','accounting','invoicing','reseller','general_brand','unclassified'),
  audience_segment ENUM('b2c','b2b'),
  intent ENUM('commercial','informational','navigational','unknown'),
  is_brand BOOLEAN,
  is_duplicate_of_id BIGINT NULL, -- self-reference, кластер дублей
  is_forbidden BOOLEAN,
  is_already_used BOOLEAN,
  quality_score FLOAT, -- составной скор для сортировки/приоритизации
  status ENUM('raw','cleaned','rejected','ready'),
  rejection_reason TEXT NULL, -- обязательное поле при status=rejected, для audit trail
  created_at, updated_at
)

forbidden_terms (id, term, match_type ENUM('exact','contains','regex'), reason)
brand_terms (id, term, is_own_brand BOOLEAN) -- site.pro vs конкуренты (wix, tilda, wordpress...)

-- Группировка и объявления
ad_groups (
  id, category, audience_segment, language, target_url, theme_label
)

ad_group_keywords (ad_group_id FK, keyword_id FK) -- M2M

ads (
  id, ad_group_id FK,
  headline_1..headline_15,
  description_1..description_4,
  final_url, path_1, path_2,
  status ENUM('draft','ready','exported')
)

export_batches (id, created_at, file_path, ads_count, keywords_count)
```

---

## 3. Pipeline обработки (архитектурный стержень)

Каждый этап — отдельный service-класс в Yii2 (`components/pipeline/`), явно логируемый, идемпотентный, с единой ответственностью (SOLID — никаких God Objects).

**Асинхронность:** импорт и очистка (особенно Levenshtein-дедупликация на тысячах строк) выполняются через `yii\queue\Queue` (драйвер `db` — не требует Redis/RabbitMQ, поднимается на любом хостинге). Синхронный веб-реквест для этого не годится — таймаут. Каждый батч импорта запускает job, статус батча (`processing/done/failed`) обновляется по завершении job.

**Событийная модель:** пайплайн эмитит события через `yii\base\Event` на границах этапов — `EVENT_AFTER_IMPORT`, `EVENT_AFTER_CLEANING`, `EVENT_AFTER_CLASSIFICATION`, `EVENT_AFTER_EXPORT`. Слушатели: логирование, будущие интеграции (уведомления, вебхуки), без прямой связанности сервисов друг с другом.

1. **ImportService** — принимает файл (CSV/JSON), через `SourceAdapterInterface` (реализации: `CsvAdapter`, `JsonAdapter`, задел под будущий `ApiAdapter`). Считает hash файла — повторный импорт того же файла не плодит дубли (upsert по `normalized_text + source_id`).

2. **NormalizationService** — lowercase, trim, унификация спецсимволов, разделение кириллица/латиница, единый формат чисел.

3. **CleaningService**:
   - Junk-фильтр: длина <2 символов, чисто цифровые запросы, стоп-слова, артефакты автогенерации Ahrefs
   - Brand-detection: сверка с `brand_terms` (site.pro — свой бренд, конкуренты — wix/tilda/wordpress/etc. — исключаются или уходят в отдельную brand-defense кампанию)
   - Forbidden-list: сверка с `forbidden_terms`
   - Already-used: сверка с текущими активными GAds-кампаниями (условно — список из отдельного импорта или ручного ввода)

4. **DeduplicationService** — fuzzy-match через PostgreSQL `pg_trgm` (`CREATE EXTENSION pg_trgm`, `similarity()`/`%` прямо в SQL — на тысячах строк на порядки быстрее, чем Левенштейн в PHP-цикле), плюс предварительная нормализация (токен-сортировка) перед сравнением. Кластеризация похожих ключей, оставляем представителя кластера с максимальным volume, остальные помечаем `is_duplicate_of_id`.

5. **VolumeFilterService** — настраиваемый порог (хранится в конфиге/settings, не хардкод), плюс логика "низкий volume, но встречается в 3+ источниках" = не отбрасывать автоматически, а помечать на ручной review.

6. **ClassificationService** (rule-based, прозрачный):
   - `category`: keyword-паттерны → одна из 6 категорий продукта
   - `audience_segment`: b2c/b2b по паттернам ("для хостинг-провайдеров", "white label", "partner" → b2b)
   - `intent`: commercial ("купить","заказать","цена","стоимость","тариф") / informational ("как","что такое","бесплатно","инструкция") / navigational (точное совпадение с брендом)

7. **GapAnalysisService** — отдельный отчёт: сравнение `ahrefs_paid_keywords` (конкуренты) против текущего пула site.pro → список keyword-кандидатов, на которые конкуренты тратят бюджет, а site.pro — нет. Приоритизация по потенциальному volume.

   Конкретная логика (чтобы агент не плыл в формулировках):
   ```sql
   gap_candidates =
     ahrefs_paid_keywords.normalized_text
     MINUS (
       gads_keywords.normalized_text
       UNION search_console_keywords.normalized_text
     )
   WHERE ahrefs_paid_keywords.volume > :threshold
   ```
   То есть: ключи конкурентов из Ahrefs Paid, которых нет ни в текущих GAds-кампаниях, ни в органическом трафике site.pro (Search Console) — и с объёмом выше порога. Это и есть "недополученные" возможности.

8. **GroupingService** — кластеризация `ready`-ключей в `ad_groups` по `category + audience_segment + language`.

9. **AdGenerationService** — генерация headlines/descriptions через `AdGeneratorInterface`:
   - `TemplateAdGenerator` (MVP, реализуется сейчас) — шаблонизация с подстановкой ключа + УТП по category/language
   - `LlmAdGenerator` — реализуется через DeepSeek API, с автоматическим fallback на `TemplateAdGenerator` при недоступности API/timeout. Переключение генератора — через DI-конфиг, без изменений в остальном пайплайне. Дальнейшее развитие (интеграция с локальными self-hosted моделями, мультивариантная генерация под A/B) — в Roadmap.

10. **ExportService** — сборка Google Ads Editor bulk-CSV (`Campaign, Ad Group, Keyword, Match Type, Headline 1-15, Description 1-4, Final URL, Path 1, Path 2`).

---

## 4. Admin-панель (минимально, но по делу)

**Интернационализация:** команда интернациональная — интерфейс на английском + русском (переключатель языка в шапке). Используем встроенный Yii2 `i18n` компонент: `Yii::t('app', 'key')` во всех view/labels, файлы переводов `messages/en/app.php` и `messages/ru/app.php`. Никаких хардкод-строк в шаблонах. Это отдельно от `language` поля у keywords (то — язык самого ключевого слова, это — язык интерфейса пользователя админки, не путать).

- **Dashboard**: сводка по батчам импорта, статистика raw/cleaned/rejected/ready
- **Keywords table**: фильтры по source/category/status/language, видимость `rejection_reason`, возможность ручного override статуса
- **Gap Analysis report**: отдельная страница-таблица конкурентных возможностей
- **Ad Groups & Ads preview**: просмотр сгенерированных объявлений перед экспортом, редактирование inline
- **Settings**: volume threshold, forbidden terms, brand terms — редактируемые списки
- **Export**: кнопка генерации финального CSV + история экспортов

---

## 5. TDD-план по фазам (для скармливания агенту поэтапно)

**Фаза 0 — Setup**
Yii2 skeleton, БД миграции по схеме из п.2, базовая аутентификация в админку, настройка `yii\queue` (driver: db), настройка `codeception` (стандарт тестирования для Yii2 — предпочтительнее голого phpunit, т.к. из коробки даёт Unit/Functional/Acceptance suites под Yii2-архитектуру), настройка `i18n`-компонента (en/ru, переключатель языка, message source).

**Жёсткое правило для агента на весь проект:** "Никаких God Objects. Каждый сервис пайплайна — единая ответственность (SOLID). Тесты пишутся до контроллеров, каждый сервис покрывается unit-тестом до того, как на него завязывается UI." Это одновременно архитектурная гигиена и защита от галлюцинаций агента — тест фиксирует контракт до того, как агент начнёт "додумывать" поведение в контроллере.

**Фаза 1 — Import**
`SourceAdapterInterface` + Csv/Json adapters, `ImportService` с idempotency и queue job, тестовые данные (сгенерировать реалистичные CSV для всех 4 источников на русском/английском с примесью мусора для проверки пайплайна). Unit-тесты на adapters и ImportService (идемпотентность — обязательный кейс).

**Фаза 2 — Cleaning pipeline**
Normalization → Cleaning → Deduplication → VolumeFilter как queue jobs, каждый шаг с unit-тестами на edge cases (пустые строки, дубли с разным регистром, брендовые запросы, кириллица/латиница вперемешку).

**Фаза 3 — Classification**
Rule-based classifier (category/audience_segment/intent), таблица правил вынесена в конфиг для лёгкого расширения.

**Фаза 4 — Admin UI**
Keywords table с фильтрами, ручной override, rejection_reason отображение. Все строки интерфейса через `Yii::t()` с самого начала (en/ru) — не докручивать i18n постфактум, дешевле сразу.

**Фаза 5 — Gap Analysis**
Отдельный сервис + UI-отчёт.

**Фаза 6 — Grouping & Ad Generation**
GroupingService + AdGenerationService + preview UI.

**Фаза 7 — Export**
ExportService (Google Ads Editor формат) + история экспортов.

**Фаза 8 — Деплой**
Docker-обёртка: `Dockerfile` (PHP-FPM + Yii2) + `docker-compose.yml` (app, MySQL, nginx, queue-worker как отдельный сервис). Проверить полный цикл через `docker-compose up` локально, затем поднять на поддомене avpdev.com. Убедиться, что `yii\queue` воркер запускается как отдельный процесс в контейнере (не блокирует основной web-сервис), а миграции применяются при старте (entrypoint-скрипт или явная команда). Финальная проверка: upload → admin → preview → export работает end-to-end на живом URL.

---

## 6. Требования к README

Документация — билингвальная (EN + RU), обе версии обязательны и синхронны по содержанию. Формат: либо `README.md` (en) + `README.ru.md`, либо один файл с секциями `## English` / `## Русский`.

Включи:

1. Краткое объяснение архитектуры и логики (2-3 абзаца): почему важны intent/audience_segment, зачем нужен gap analysis, почему pipeline из отдельных сервисов, а не монолит.
2. Ссылку на живой URL. Тестовые данные — внутри репозитория, не через запрос сторонних файлов.
3. Раздел **Vibecoding Log**:
   - Архитектура и схема данных спроектированы мной, реализация и тесты — AI-агентом (Claude Code).
   - Хронология работы: бриф → фазы → ревью на каждой фазе → деплой, с указанием затраченного времени.
   - При необходимости — ключевые промпты/цепочка задач по фазам.

---

## 7. Roadmap (не реализуется сейчас, только зафиксировать в README)

- **LlmAdGenerator** — генерация объявлений через DeepSeek/локальные модели вместо шаблонов (интерфейс уже заложен в архитектуре).
- **RBAC** — роли admin/editor/viewer для команды.
- **API-источники** — прямая интеграция с Google Ads API / Search Console API вместо ручной загрузки CSV.
- **Автоопределение источника при загрузке файла**, в два этапа:
  1. Быстрый вариант — auto-suggest по имени файла (совпадение с паттернами `google_ads`, `search_console`, `ahrefs_organic`, `ahrefs_paid` предзаполняет дропдаун "Тип источника", выбор остаётся редактируемым).
  2. Основной вариант на перспективу — детект по структуре/заголовкам колонок файла, не завязанный на имя файла (надёжнее, так как не зависит от того, как пользователь сохранил файл).
  3. Drag&drop зоны загрузки — вместо стандартного file input, для удобства.
- **A/B тестирование объявлений** — несколько вариантов на ad_group, статистика по CTR после реальных запусков.
- **Автоматическое обновление already-used** — синк с реальными активными кампаниями через API, а не ручной список.
- **Визуальная модернизация интерфейса.** Функционально админка интуитивна и закрывает все сценарии, но выглядит как типовой Yii2/Bootstrap-шаблон "из коробки". Стоит уйти от этого к собственному визуальному языку: продуманная типографика, акцентные цвета вместо стандартной Bootstrap-палитры, более выразительные статус-бейджи и карточки статистики, аккуратные микро-анимации на переходах между состояниями (загрузка → обработка → готово). Цель — интерфейс, который выглядит как продукт, а не как дефолтная генерация фреймворка.
- **Разделение данных по направлениям/проектам (workspaces).** Сейчас все загруженные ключи из всех источников попадают в один общий пул — при работе с несколькими направлениями/кампаниями одновременно (например, разные продуктовые линейки site.pro, или разные рынки/языковые группы, ведущиеся раздельно) это может привести к перемешиванию и конфликтам данных между направлениями. Нужен механизм изоляции: явный "проект/направление" при импорте, к которому привязывается весь пайплайн — очистка, классификация, gap analysis, экспорт — так, чтобы данные разных направлений не смешивались в одних отчётах и не конфликтовали друг с другом (например, already_used не должен сверяться с активными кампаниями чужого направления).

  Модель доступа — два уровня, оба на базе RBAC (п. выше):
  - **Ограниченный доступ** — пользователю выдаётся доступ только к конкретному направлению/проекту, без видимости остальных.
  - **Доступ с переключением** — пользователю доступны все направления, с переключателем организации/направления прямо в учётной записи (аналогично переключателю языка/темы в шапке), без необходимости отдельного логина под каждое направление.