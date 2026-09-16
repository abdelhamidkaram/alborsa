# Foundation Handoff

> Read this file before starting any new implementation work.

## Current state

- The plugin lives at `wp-content/plugins/alborsa-platform`.
- Only the `Foundation` module is wired; the bounded database migration for Phase 2 relationships (`{$wpdb->prefix}alb_relationships`, e.g. `wp_alb_relationships` on a single-site installation) is implemented.
- Schema version `1` adds administrator capability `manage_alborsa_platform`.
- Schema version `2` creates the relationships table `{$wpdb->prefix}alb_relationships` idempotently using WordPress `dbDelta` with columns `id`, `from_object_id`, `from_object_type`, `to_object_id`, `to_object_type`, `relation_type`, `sort_order`, and `created_at` (a `datetime` column; UTC population is the responsibility of the future runtime service), a unique key preventing duplicate relationships, and a reverse target lookup index. No foreign keys are added.
- The migration uses the current site table prefix and the existing network activation lifecycle, while real WordPress/MySQL table creation and network activation across multiple blogs remain unverified integration boundaries.
- Soft checks cover the seven existing WordPress plugins.
- Site Health and `wp alborsa health` report migration and dependency status.
- Isolated unit tests verify schema SQL definition and migration lifecycle orchestration.
- Quality checks run with `composer quality` from the plugin directory.

## Not implemented

- Product modules, custom post types, taxonomies, REST routes, or remaining feature tables (`alb_market_quotes`, `alb_affiliate_links`, `alb_affiliate_clicks`).
- Relationship service / API and CPT/taxonomy registration (open URL decision D-002 remains unresolved).
- Market providers, user accounts, watchlists, or alerts.
- Theme work, frontend design, CSS, or layouts.

## Before the next change

1. Read `AGENTS.md` and all required documents listed there.
2. Check `docs/04-open-decisions.md`; do not decide an open item silently.
3. Keep product logic inside the plugin and independent of the theme.
4. Update this file when the implementation state changes.
5. Run `composer quality` before delivery.
