=== Alborsa Platform ===
Contributors: alborsa
Requires at least: 6.6
Requires PHP: 8.1
Stable tag: 0.1.0
License: Proprietary

The theme-independent product foundation for alborsa.net. Health check: `wp alborsa health`.

== Description ==

This source release contains the Foundation, Editorial, and Assets modules. It
provides bootstrap, module registration, migrations, conservative capabilities,
dependency diagnostics, Site Health integration, the `wp alborsa health`
command, analyses, guides, assets, their core taxonomies, validated asset
metadata, validated content-to-asset relationships, and selective Polylang field
synchronization. It also exposes bounded, read-only public REST routes for the
published asset collection and asset details with published related content.

It does not yet register market-data or write REST routes, market providers,
user accounts, watchlists, alerts, frontend layouts, CSS, or theme templates.

== Installation ==

Build the production artifact with Composer dependencies included:

1. Run `composer install --no-dev --classmap-authoritative` in the plugin directory.
2. Activate Alborsa Platform from WordPress.
3. Review Tools > Site Health or run `wp alborsa health`.
