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

### Известные проблемы
- `yiisoft/yii2-queue` не работал в Docker build (`composer install --no-dev` exit 4) — пофикшено обновлением composer.lock
- Action name `language` — зарезервированное слово в Yii2, переименован в `set-language`

### Деплой на реальный URL
- После push `de79d2c` (composer.lock) — **билд упал** (см. логи Coolify, код ошибки?)
- Сейчас: HTTP 503 (контейнер пересобирается или упал)

---

## Фазы 1-8: Начнутся после деплоя Фазы 0
