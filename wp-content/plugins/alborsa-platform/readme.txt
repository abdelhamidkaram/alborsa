=== Alborsa Platform ===
Contributors: alborsa
Requires at least: 6.6
Requires PHP: 8.1
Stable tag: 0.1.0
License: Proprietary

The theme-independent product foundation for alborsa.net. Health check: `wp alborsa health`.

== Description ==

This release contains the Foundation, Editorial, Assets, Market Data, Brokers,
Financial Tools, and Yoast SEO integration modules. It provides versioned
migrations, conservative capabilities, dependency diagnostics, Site Health,
the `wp alborsa health` command, editorial and financial content types, public
read-only REST contracts, privacy integration, and bounded audit events.

The plugin imports assets from validated CSV and editorial feeds from safe RSS
URLs through WP-CLI. Provider-neutral market data is synchronized in batches by
`wp alborsa market sync --provider=<key>`, stores only the latest snapshot, uses
targeted object-cache keys, exposes attribution and delay state, and never
fetches an external provider during a frontend request.

Broker profiles expose only complete, source-backed verified license records.
User review averages stay separate from the pending editorial score, and no
public review submission or affiliate redirect is registered until the related
open decisions are approved. Four stateless financial calculators are exposed;
the zakat calculator and currency converter remain unavailable until their
methodology and data-provider decisions are approved.

The plugin does not bundle a licensed market-data adapter, user accounts,
watchlists, price alerts, a stock screener, newsletter delivery, frontend CSS,
or the separate Alborsa theme.

== Installation ==

Build the production artifact with Composer dependencies included:

1. Run `composer install --no-dev --classmap-authoritative` in the plugin directory.
2. Activate Alborsa Platform from WordPress.
3. Review Tools > Site Health or run `wp alborsa health`.
4. Save permalink settings once if the environment does not run the activation hook normally.
