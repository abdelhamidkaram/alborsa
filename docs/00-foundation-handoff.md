# Foundation Handoff

> Read this file before starting any new implementation work.

## Current state

- The plugin lives at `wp-content/plugins/alborsa-platform`.
- Only the `Foundation` module is implemented.
- Schema version `1` adds one administrator capability and creates no tables.
- Soft checks cover the seven existing WordPress plugins.
- Site Health and `wp alborsa health` report migration and dependency status.
- Quality checks run with `composer quality` from the plugin directory.

## Not implemented

- Product modules, custom post types, REST routes, or feature tables.
- Market providers, user accounts, watchlists, or alerts.
- Theme work, frontend design, CSS, or layouts.

## Before the next change

1. Read `AGENTS.md` and all required documents listed there.
2. Check `docs/04-open-decisions.md`; do not decide an open item silently.
3. Keep product logic inside the plugin and independent of the theme.
4. Update this file when the implementation state changes.
5. Run `composer quality` before delivery.
