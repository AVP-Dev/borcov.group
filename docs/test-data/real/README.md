# Real-world test data

These files match actual export formats from Google Search Console, Google Ads, and Ahrefs.

| File | Source | Columns |
|------|--------|---------|
| `search_console_ru.csv` | Google Search Console (Russian) | `Популярные запросы,Kлики,Показы,CTR,Позиция` |
| `search_console_en.csv` | Google Search Console (English) | `Search query,Impressions,Clicks,CTR,Position` |
| `search_console.json` | Google Search Console API | `rows[].keys[0]` format |
| `google_ads.csv` | Google Ads Search Terms | `Keyword,Volume,Competition` |
| `ahrefs_organic.csv` | Ahrefs Organic Keywords | `Keyword,Volume,KD` |
| `ahrefs_paid.csv` | Ahrefs Paid Keywords | `Keyword,Volume,CPC` |
