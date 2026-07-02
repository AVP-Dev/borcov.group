# PROGRESS.md — Deployment Status Tracker

## Фаза -1: Empty Skeleton + Docker + Coolify Deploy
- [x] Yii2 advanced skeleton создан (composer create-project)
- [x] Dockerfile написан (PHP 8.2-fpm-alpine + nginx + supervisor + PostgreSQL ext)
- [x] docker-compose.yml (app + postgres + healthcheck)
- [x] docker/entrypoint.sh (cookieValidationKey генерируется явно, migrate без || echo)
- [x] docker/nginx.conf (backend/web как root, /health endpoint)
- [x] docker/supervisord.conf
- [x] SiteController::actionStatus() — публичный health-check endpoint
- [x] .gitignore обновлён (vendor исключён, local configs исключены)
- [x] git init + push в GitHub (AVP-Dev/borcov.group)
- [x] Coolify: подключить репо → поддомен vibecoding.avpdev.com
- [x] Деплой подтверждён: `curl https://vibecoding.avpdev.com/` → 302, PHP 8.4.22

**Деплой на реальный URL подтверждён:** ✅ `curl https://vibecoding.avpdev.com/` → 302 redirect to `/site/login`, `X-Powered-By: PHP/8.4.22`

---

## Фаза 0: Setup
- [x] PostgreSQL migrations (схема из BRIEF.md §2)
- [x] pg_trgm extension (`m260703_000001_enable_pg_trgm`)
- [x] Аутентификация admin (`console/controllers/AdminController.php`, `ADMIN_PASSWORD` env var в entrypoint)
- [x] yii\queue (driver: db) — `yiisoft/yii2-queue ~2.3.2`, таблица `queue`, supervisor worker
- [ ] Codeception unit/functional suites — ожидает настройки тестовой БД
- [x] i18n (en/ru, `/site/language` route, session-based, nav switcher)

**Деплой на реальный URL подтверждён:** ❌ (ждём применения миграций на проде)

---

## Фазы 1-8: TBD (начнутся после Фазы 0)
