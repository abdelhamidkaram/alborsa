# Foundation Handoff

> Read this file before starting any new implementation work.

## Current state

- The plugin lives at `wp-content/plugins/alborsa-platform`.
- The `Foundation`, `Editorial`, `Assets`, and `MarketData` modules are wired. Editorial registers the public `alb_analysis` and `alb_guide` post types with the approved `/tahleel/` and `/taleem/` rewrite bases. Assets registers `alb_asset` without a generic archive.
- Six taxonomies are registered with admin and core REST support: `alb_topic`, `alb_guide_level`, `alb_guide_track`, `alb_asset_type`, `alb_exchange`, and `alb_sector`. Their public archives and rewrite rules remain disabled until URL contracts are approved.
- Asset singles use the approved `/aswaq/tadawul/{symbol}/` route for assets classified as `stock` on the `tadawul` exchange and `/forex/{pair}/` for assets classified as `forex`. Route queries enforce those classifications to prevent duplicate cross-route URLs; other asset classes keep WordPress query permalinks until their routes are approved.
- `AssetMeta` registers six public, single-value fields (`alb_canonical_symbol`, `alb_base_currency`, `alb_quote_currency`, `alb_isin`, `alb_official_website`, `alb_listing_status`) with sanitizers and per-post edit authorization. The structured `_alb_provider_mappings` field is protected and excluded from core REST responses.
- `AssetsModule` exposes read-only `GET /wp-json/alborsa/v1/assets` and `GET /wp-json/alborsa/v1/assets/{id}` routes. They return published assets only, cap collection requests at 100 items and page 1000, support search plus asset-type/exchange filters, omit protected provider mappings, and include published related content on detail responses.
- `wp alborsa assets import <file> [--dry-run]` imports bounded UTF-8 CSV files through `AssetUpsertService`. Each row requires an explicit Polylang language, title, canonical symbol, and existing language-matched asset-type term. Identity is `(language, canonical_symbol)`; missing optional columns are preserved on updates, new rows default to draft, and English rows cannot be published in the current phase.
- The provider-neutral Phase 3 runtime is implemented: `QuoteProviderInterface`, immutable `NormalizedQuote`, deterministic `MockQuoteProvider`, filtered provider discovery, bounded mapping batches, synchronization, targeted object caching, public reading, REST delivery, WP-CLI, and Site Health diagnostics. Decimal market values stay as normalized strings, timestamps require timezone-aware ISO-8601 input and are stored in UTC. No real provider or external request is configured while D-001 is open.
- The plugin-root `wpml-config.xml` makes the three implemented post types and six taxonomies translatable in Polylang and keeps all seven neutral asset fields synchronized with `copy` semantics.
- Content registration and permalink behavior are covered by isolated tests; a live WordPress integration pass with Polylang and Yoast remains required on Staging.
- The bounded database migration for Phase 2 relationships (`{$wpdb->prefix}alb_relationships`, e.g. `wp_alb_relationships` on a single-site installation) is implemented.
- Schema version `1` adds administrator capability `manage_alborsa_platform`.
- Schema version `2` creates the relationships table `{$wpdb->prefix}alb_relationships` idempotently using WordPress `dbDelta` with columns `id`, `from_object_id`, `from_object_type`, `to_object_id`, `to_object_type`, `relation_type`, `sort_order`, and `created_at` (a `datetime` column populated in UTC by `RelationshipRepository`), a unique key preventing duplicate relationships, and a reverse target lookup index. No foreign keys are added.
- Schema version `3` installs the shared `alb_content` capability map for the implemented product post types. Administrators and editors receive the ten generated primitive edit, publish, read-private, and delete capabilities; `manage_alborsa_platform` remains administrator-only.
- Schema version `4` creates `{$wpdb->prefix}alb_market_quotes` for the latest normalized snapshot per `(asset_id, provider_key)`. Decimal columns use `decimal(30,10)`, nullable unavailable values remain SQL `NULL`, and `QuoteRepository` performs prepared upserts and exact asset/provider reads without storing price history.
- `wp alborsa market sync --provider=<key>` scans published assets in bounded 100-asset pages, deduplicates provider symbols per batch, validates complete provider responses, persists the latest snapshots, and updates only affected `alborsa_platform_quotes` cache keys. It is intended for a real server cron; no WP-Cron visit trigger is registered.
- `QuoteReadService` and `GET /wp-json/alborsa/v1/assets/{id}/quote` return public quote fields, attribution, UTC timestamps, declared delay, status, and a stale flag without returning protected provider keys or symbols. A provider failure recorded after a snapshot marks that last value stale instead of deleting it.
- The migration uses the current site table prefix and the existing network activation lifecycle, while real WordPress/MySQL table creation and network activation across multiple blogs remain unverified integration boundaries.
- Soft checks cover the seven existing WordPress plugins.
- Site Health reports migration, dependency, and market-provider state. `wp alborsa health` remains the Foundation migration/dependency command.
- Rewrite rules use a versioned Foundation service and are soft-flushed once, at late `init`, after activation or a rewrite contract version change.
- Isolated unit tests verify schema SQL definition and migration lifecycle orchestration.
- Quality checks run with `composer quality` from the plugin directory.
- A standalone custom theme now lives at `wp-content/themes/alborsa`. It implements the approved RTL-first Editorial Signal design system, responsive site chrome, the complete homepage composition, news/analysis/education archives, markets/brokers/tools landing pages, editorial and asset singles, search, and 404 states.
- The theme reads only published WordPress content and the documented public asset identity fields. It does not read the quote table or provider mappings, make frontend market requests, manufacture prices, activate broker CTAs, or expose calculator forms before their plugin services are implemented.

## Not implemented

- Other product modules, remaining custom post types and taxonomies, editorial workflow/statuses and metadata, remaining custom REST routes, or remaining feature tables (`alb_affiliate_links`, `alb_affiliate_clicks`).
- `RelationshipRepository` persists, deletes, and reads exact relationships with a 100-row read cap. `ContentAssetRelationshipService` validates `WP_Post` object roles and allows `mentions`/`analyzes` links from posts, analyses, and guides to assets; it has no admin UI or REST write route yet. D-002 approves Arabic on `/` and English under `/en/`; remaining product entity registration, write routes, caching, and administrative/data-service integration remain unimplemented.
- Real market providers, retry/backoff policy, user accounts, watchlists, or alerts. The complete provider-neutral quote runtime is implemented, but no external adapter is selected and no server cron entry is installed while D-001 remains open.
- Live WordPress visual QA, real content population, menu/page assignment, and compatibility checks with the installed Polylang, Yoast, Breeze, and Object Cache Pro configuration remain staging tasks.

## Before the next change

1. Read `AGENTS.md` and all required documents listed there.
2. Check `docs/04-open-decisions.md`; do not decide an open item silently.
3. Keep product logic inside the plugin and independent of the theme.
4. Update this file when the implementation state changes.
5. Run `composer quality` before delivery.
