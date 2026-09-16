=== Alborsa Platform ===
Contributors: alborsa
Requires at least: 6.6
Requires PHP: 8.1
Stable tag: 0.1.0
License: Proprietary

The theme-independent product foundation for alborsa.net. Health check: `wp alborsa health`.

== Description ==

This source release contains the Foundation module and the first Editorial
content types. It provides bootstrap, module registration, migrations,
conservative capabilities, dependency diagnostics, Site Health integration,
the `wp alborsa health` command, analyses, and guides.

It does not yet register assets, taxonomies, custom REST routes, market
providers, user accounts, watchlists, alerts, frontend layouts, CSS, or theme
templates.

== Installation ==

Build the production artifact with Composer dependencies included:

1. Run `composer install --no-dev --classmap-authoritative` in the plugin directory.
2. Activate Alborsa Platform from WordPress.
3. Review Tools > Site Health or run `wp alborsa health`.
