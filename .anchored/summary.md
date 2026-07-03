# Anchored Summary

## Goal
Build and deploy a Yii2 marketing keyword automation platform with import, cleaning, classification pipeline, admin UI, gap analysis, ad groups, and RSA generation.

## Constraints & Preferences
- Coolify v4.1.2 on localhost (Docker 29.5.2, BuildKit, aarch64)
- Domain: `vibecoding.avpdev.com` → Coolify proxy routes to app
- PHP 8.4, PostgreSQL 16, nginx + php-fpm via supervisord
- All changes via git push to `AVP-Dev/borcov.group:main`; Coolify auto-deploys
- Admin login: `admin` / `ADMIN_PASSWORD` env var from Coolify
- `.gitignore` excludes `*-local.php`; generated at runtime by entrypoint.sh
- `DEEPSEEK_API_KEY` from env var, mapped via `common/config/params.php`

## Progress
### Done
- **Phase 0–5:** skeleton → login → import → clean+dedup → classify → admin UI → gap analysis; all deployed and verified on `vibecoding.avpdev.com`
- **Phase 6 – Grouping & Ad Generation with i18n:** complete, pushed (`efd5563`), deployed

  **New this round:**
  - `common/config/params.php` — added `deepseekApiKey` from `getenv('DEEPSEEK_API_KEY')`
  - **i18n keys (en/ru):** `ad_groups.generator_label`, `ad_groups.generator_template`, `ad_groups.generator_llm`, `ad_groups.generator_unavailable`, `ad_groups.generator_ai_available`, `ad_groups.regenerate_btn`, `ad_groups.regenerated`, `ad_groups.badge_ai`, `ad_groups.badge_ai_fallback`, `ad_groups.badge_template`
  - `AdGroupsController::actionRegenerate()` — label now uses i18n key `ad_groups.generator_llm` instead of hardcoded string
  - `LlmAdGeneratorTest` — source field verification (`AdData::SOURCE_LLM` / `SOURCE_LLM_FALLBACK`) in all 5 tests; renamed `testParsesValidDeepSeekResponse` → `testHappyPathWithSourceLlm`, added description length check

  **Previously:**
  - `AdGroup`, `AdGroupKeyword` (pivot), `Ad` models
  - `GroupingService::groupAll()` + `regenerateForGroup()`
  - `AdGeneratorInterface` + `AdData` value object
  - `TemplateAdGenerator` — 5 pattern templates per en/ru
  - `LlmAdGenerator` — DeepSeek API with fallback chain
  - Migration `m260703_000009` — adds `generator` column to `ads`
  - `AdGroupsController` — index/generate/view/regenerate/updateAd
  - Views with generator switcher, badges (AI/fallback/template), inline edit

### In Progress
- (none)

### Blocked
- (none)

## Key Decisions
- `deepseekApiKey` param set in `common/config/params.php` from `getenv()` — accessible across all app layers
- All UI labels use i18n — no hardcoded strings in views or controller output
- Generator source per-ad tracked in `ads.generator` column — `template` / `llm` / `llm_fallback`

## Next Steps
- Phase 7: ExportService + export history UI

## Critical Context
- All endpoints live: `/gap-analysis/index` → 302 (auth), `/ad-groups/index` → 302 (auth), `/site/login` → 200
- Test suite: **105 tests, 196 assertions**. PHPStan: **0 errors** (level 5)
- `ads.generator` column added by migration `m260703_000009` — applied on test and prod
- `DEEPSEEK_API_KEY` must be set in Coolify env

## Relevant Files
- `common/config/params.php` — `deepseekApiKey` from env
- `backend/controllers/AdGroupsController.php` — generator switcher + i18n labels
- `backend/views/ad-groups/view.php` — generator radio buttons, badges
- `backend/views/ad-groups/index.php` — AI availability badge
- `common/messages/{en,ru}/app.php` — all ad generator i18n keys
- `common/components/pipeline/GroupingService.php` — `groupAll()` + `regenerateForGroup()`
- `common/components/pipeline/AdGeneratorInterface.php`, `AdData.php`
- `common/components/pipeline/TemplateAdGenerator.php`, `LlmAdGenerator.php`
- `common/models/Ad.php`, `AdGroup.php`, `AdGroupKeyword.php`
- `console/migrations/m260703_000009_add_ad_generator_column.php`
- `common/tests/Unit/pipeline/LlmAdGeneratorTest.php` — source verification
