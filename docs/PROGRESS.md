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
- [ ] git init + push в GitHub (borcov-group, private)
- [ ] Coolify: подключить репо → поддомен vibecoding.avpdev.com
- [ ] Деплой подтверждён: `curl https://vibecoding.avpdev.com/site/status` → {"status":"OK"}

**Деплой на реальный URL подтверждён:** ❌ PENDING

---

## Фаза 0: Setup (после подтверждения Фазы -1)
- [ ] PostgreSQL migrations (схема из BRIEF.md §2)
- [ ] pg_trgm extension
- [ ] Аутентификация admin (ADMIN_PASSWORD env var)
- [ ] yii\queue (driver: db)
- [ ] Codeception unit/functional suites
- [ ] i18n (en/ru, переключатель языка — реальный route)

**Деплой на реальный URL подтверждён:** ❌ PENDING

---

## Фазы 1-8: TBD (начнутся после Фазы 0)
