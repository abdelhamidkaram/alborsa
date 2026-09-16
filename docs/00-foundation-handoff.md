# Foundation Handoff

> Read this file before starting any new implementation work.

## Current state

- The plugin lives at `wp-content/plugins/alborsa-platform`.
- The `Foundation`, `Editorial`, and `Assets` modules are wired. Editorial registers the public `alb_analysis` and `alb_guide` post types with the approved `/tahleel/` and `/taleem/` rewrite bases. Assets registers `alb_asset` without a generic archive.
- Six taxonomies are registered with admin and core REST support: `alb_topic`, `alb_guide_level`, `alb_guide_track`, `alb_asset_type`, `alb_exchange`, and `alb_sector`. Their public archives and rewrite rules remain disabled until URL contracts are approved.
- Asset singles use the approved `/aswaq/tadawul/{symbol}/` route for assets classified as `stock` on the `tadawul` exchange and `/forex/{pair}/` for assets classified as `forex`. Route queries enforce those classifications to prevent duplicate cross-route URLs; other asset classes keep WordPress query permalinks until their routes are approved.
- `AssetMeta` registers six public, single-value fields (`alb_canonical_symbol`, `alb_base_currency`, `alb_quote_currency`, `alb_isin`, `alb_official_website`, `alb_listing_status`) with sanitizers and per-post edit authorization. The structured `_alb_provider_mappings` field is protected and excluded from core REST responses.
- `AssetsModule` exposes read-only `GET /wp-json/alborsa/v1/assets` and `GET /wp-json/alborsa/v1/assets/{id}` routes. They return published assets only, cap collection requests at 100 items and page 1000, support search plus asset-type/exchange filters, omit protected provider mappings, and include published related content on detail responses.
- `wp alborsa assets import <file> [--dry-run]` imports bounded UTF-8 CSV files through `AssetUpsertService`. Each row requires an explicit Polylang language, title, canonical symbol, and existing language-matched asset-type term. Identity is `(language, canonical_symbol)`; missing optional columns are preserved on updates, new rows default to draft, and English rows cannot be published in the current phase.
- Phase 3 has started only at the provider-neutral boundary: `QuoteProviderInterface`, immutable `NormalizedQuote`, and deterministic `MockQuoteProvider` are implemented. Decimal market values stay as normalized strings, timestamps require timezone-aware ISO-8601 input and are stored in UTC, and no real provider or external request is configured while D-001 is open.
- The plugin-root `wpml-config.xml` makes the three implemented post types and six taxonomies translatable in Polylang and keeps all seven neutral asset fields synchronized with `copy` semantics.
- Content registration and permalink behavior are covered by isolated tests; a live WordPress integration pass with Polylang and Yoast remains required on Staging.
- The bounded database migration for Phase 2 relationships (`{$wpdb->prefix}alb_relationships`, e.g. `wp_alb_relationships` on a single-site installation) is implemented.
- Schema version `1` adds administrator capability `manage_alborsa_platform`.
- Schema version `2` creates the relationships table `{$wpdb->prefix}alb_relationships` idempotently using WordPress `dbDelta` with columns `id`, `from_object_id`, `from_object_type`, `to_object_id`, `to_object_type`, `relation_type`, `sort_order`, and `created_at` (a `datetime` column populated in UTC by `RelationshipRepository`), a unique key preventing duplicate relationships, and a reverse target lookup index. No foreign keys are added.
- Schema version `3` installs the shared `alb_content` capability map for the implemented product post types. Administrators and editors receive the ten generated primitive edit, publish, read-private, and delete capabilities; `manage_alborsa_platform` remains administrator-only.
- The migration uses the current site table prefix and the existing network activation lifecycle, while real WordPress/MySQL table creation and network activation across multiple blogs remain unverified integration boundaries.
- Soft checks cover the seven existing WordPress plugins.
- Site Health and `wp alborsa health` report migration and dependency status.
- Rewrite rules use a versioned Foundation service and are soft-flushed once, at late `init`, after activation or a rewrite contract version change.
- Isolated unit tests verify schema SQL definition and migration lifecycle orchestration.
- Quality checks run with `composer quality` from the plugin directory.

## Not implemented

- Other product modules, remaining custom post types and taxonomies, editorial workflow/statuses and metadata, remaining custom REST routes, or remaining feature tables (`alb_market_quotes`, `alb_affiliate_links`, `alb_affiliate_clicks`).
- `RelationshipRepository` persists, deletes, and reads exact relationships with a 100-row read cap. `ContentAssetRelationshipService` validates `WP_Post` object roles and allows `mentions`/`analyzes` links from posts, analyses, and guides to assets; it has no admin UI or REST write route yet. D-002 approves Arabic on `/` and English under `/en/`; the remaining entity registration, write routes, caching, and UI integration remain unimplemented.
- Real market providers, user accounts, watchlists, or alerts. The quote contract and mock provider are implemented, but no external adapter is selected while D-001 remains open.
- Theme work, frontend design, CSS, or layouts.

## Before the next change

1. Read `AGENTS.md` and all required documents listed there.
2. Check `docs/04-open-decisions.md`; do not decide an open item silently.
3. Keep product logic inside the plugin and independent of the theme.
4. Update this file when the implementation state changes.
5. Run `composer quality` before delivery.
