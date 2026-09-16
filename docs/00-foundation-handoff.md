# Foundation Handoff

> Read this file before starting any new implementation work.

## Current state

- The plugin lives at `wp-content/plugins/alborsa-platform`.
- The `Foundation`, `Editorial`, and `Assets` modules are wired. Editorial registers the public `alb_analysis` and `alb_guide` post types with the approved `/tahleel/` and `/taleem/` rewrite bases. Assets registers `alb_asset` without a generic archive.
- Six taxonomies are registered with admin and core REST support: `alb_topic`, `alb_guide_level`, `alb_guide_track`, `alb_asset_type`, `alb_exchange`, and `alb_sector`. Their public archives and rewrite rules remain disabled until URL contracts are approved.
- Asset singles use the approved `/aswaq/tadawul/{symbol}/` route for assets classified as `stock` on the `tadawul` exchange and `/forex/{pair}/` for assets classified as `forex`. Route queries enforce those classifications to prevent duplicate cross-route URLs; other asset classes keep WordPress query permalinks until their routes are approved.
- Content registration and permalink behavior are covered by isolated tests; a live WordPress integration pass with Polylang and Yoast remains required on Staging.
- The bounded database migration for Phase 2 relationships (`{$wpdb->prefix}alb_relationships`, e.g. `wp_alb_relationships` on a single-site installation) is implemented.
- Schema version `1` adds administrator capability `manage_alborsa_platform`.
- Schema version `2` creates the relationships table `{$wpdb->prefix}alb_relationships` idempotently using WordPress `dbDelta` with columns `id`, `from_object_id`, `from_object_type`, `to_object_id`, `to_object_type`, `relation_type`, `sort_order`, and `created_at` (a `datetime` column populated in UTC by `RelationshipRepository`), a unique key preventing duplicate relationships, and a reverse target lookup index. No foreign keys are added.
- The migration uses the current site table prefix and the existing network activation lifecycle, while real WordPress/MySQL table creation and network activation across multiple blogs remain unverified integration boundaries.
- Soft checks cover the seven existing WordPress plugins.
- Site Health and `wp alborsa health` report migration and dependency status.
- Rewrite rules use a versioned Foundation service and are soft-flushed once, at late `init`, after activation or a rewrite contract version change.
- Isolated unit tests verify schema SQL definition and migration lifecycle orchestration.
- Quality checks run with `composer quality` from the plugin directory.

## Not implemented

- Other product modules, remaining custom post types and taxonomies, custom REST routes, or remaining feature tables (`alb_market_quotes`, `alb_affiliate_links`, `alb_affiliate_clicks`).
- A minimal internal `RelationshipRepository` now persists, deletes, and reads exact relationships from the existing table; it is not wired as a module or API. D-002 approves Arabic on `/` and English under `/en/`; the remaining entity registration, custom REST routes, caching, and relationship product integration remain unimplemented.
- Market providers, user accounts, watchlists, or alerts.
- Theme work, frontend design, CSS, or layouts.

## Before the next change

1. Read `AGENTS.md` and all required documents listed there.
2. Check `docs/04-open-decisions.md`; do not decide an open item silently.
3. Keep product logic inside the plugin and independent of the theme.
4. Update this file when the implementation state changes.
5. Run `composer quality` before delivery.
