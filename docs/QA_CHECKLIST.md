# QA Checklist — Приёмочное тестирование

## Тестовые данные

Сгенерированы автоматически:

```bash
# 1. Стресс-тест (25 строк, ручные edge cases)
docs/test-data/stress_test.csv

# 2. Large sample (5000+ строк, программно)
php yii generate-test-data 5000
```

### stress_test.csv включает:
- ✅ UTF-8 BOM
- ✅ Нестандартные заголовки колонок
- ✅ Volume в разных форматах: `"1,200"`, `"1.2K"`, `"1.5M"`, `N/A`, `-`
- ✅ Строка с недостающими колонками
- ✅ Пустая строка между данными
- ✅ Кириллица с ё/й
- ✅ Бренд site.pro / конкурентов (wix, tilda, wordpress)
- ✅ Forbidden-термин
- ✅ Дубли с разным регистром/порядком слов
- ✅ B2B-паттерн (reseller, white label)
- ✅ Мусор (цифры, <2 символов)

---

## Чек-лист приёмки

### 0. Base check
- [ ] `https://vibecoding.avpdev.com` открывается, CSS/Bootstrap подгружается
- [ ] Логин `admin` / (пароль из `ADMIN_PASSWORD`) редиректит на dashboard
- [ ] **Сразу смени пароль admin**, раз сервер публичный
- [ ] Переключение языка en↔ru — весь интерфейс переключается
- [ ] Выбор языка сохраняется между визитами

### 1. Импорт — обычные файлы
- [ ] Загрузить каждый из 4 стандартных файлов (`common/tests/Support/data/gads.csv`, `search_console.csv`, `ahrefs_organic.csv`, `ahrefs_paid.csv`)
- [ ] Batch: `processing` → `done` автоматически (polling каждые 3с)
- [ ] Повторная загрузка того же файла — **не** плодит дубли
- [ ] `rows_total`/`rows_accepted`/`rows_rejected` совпадают с реальным количеством строк

### 2. Импорт — stress_test.csv
- [ ] BOM не ломает парсинг
- [ ] Нестандартные названия колонок распознаны
- [ ] Все форматы volume обработаны без падения
- [ ] Битая строка — пропущена, импорт не падает
- [ ] Пустая строка — пропущена молча
- [ ] Кириллица с ё/й отображается корректно
- [ ] Batch status = `done`

### 3. Импорт — large_sample.csv
```bash
php yii generate-test-data 5000
# Загрузить сгенерированный docs/test-data/large_sample.csv через UI
```
- [ ] Загрузка не таймаутится (парсинг в queue job)
- [ ] 5000+ строк проходят весь pipeline
- [ ] Queue worker не падает по памяти

### 4. Pipeline — очистка и классификация
После загрузки `stress_test.csv`, в Keywords table проверить:
- [ ] `site.pro` → `category=general_brand`, `intent=navigational`
- [ ] `wix`/`tilda` → отклонён с brand-detection причиной
- [ ] Forbidden-термин → отклонён
- [ ] Дубли с разным регистром → один помечен `is_duplicate_of_id`
- [ ] B2B-паттерн → `audience_segment=b2b`
- [ ] Мусор → отклонён как junk
- [ ] `rejection_reason` читаемый

### 5. Gap Analysis
- [ ] Открыть отчёт после обработки данных
- [ ] Результаты — действительно ключи конкурентов, которых нет в нашем пуле

### 6. Ad Groups & Ads
- [ ] Группы созданы по `category + audience_segment + language`
- [ ] `target_url` соответствует категории
- [ ] Headlines ≤ 30, Descriptions ≤ 90 символов
- [ ] LLM fallback: временно сломать API-ключ → пайплайн не падает

### 7. Export
- [ ] Скачать CSV, открыть в Excel/Google Sheets
- [ ] Колонки: Campaign, Keyword, Match Type, Headlines 1-15, Descriptions 1-4, Final URL
- [ ] Кириллица в CSV не кракозябры

### 8. Тесты на сервере
```bash
# Через терминал Coolify или exec в контейнер:
cd /var/www/html && php vendor/bin/codecept run common/Unit
```
- [ ] Все тесты зелёные на сервере

### 9. Финальный цикл "глазами маркетолога"
- [ ] Залогиниться → загрузить 4 файла → дождаться обработки → Gap Analysis → Ad Groups → Export → скачать CSV
- [ ] Весь цикл без единого ручного вмешательства в код/БД
