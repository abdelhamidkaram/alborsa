# Project instructions — alborsa.net

These instructions apply to the entire repository.

## Required context

Before planning, changing code or documentation, or running commands that modify project state, read these files in order:

1. `docs/00-foundation-handoff.md`
2. `README.md`
3. `docs/01-plugin-architecture.md`
4. `docs/02-data-model.md`
5. `docs/03-implementation-plan.md`
6. `docs/04-open-decisions.md`
7. `docs/05-existing-wordpress-stack.md`

The Foundation handoff is mandatory and must be reviewed before every new implementation task. Do not modify project state before completing the required reading.

The original bilingual requirements document is available at:

`/Users/abdelhamid/Downloads/alborsa_rdb.html`

If that external file is unavailable, use the repository documents above as the working source of truth.

## Current project state

- The repository contains planning documents plus the Foundation scaffold under `wp-content/plugins/alborsa-platform`; product modules have not been implemented.
- The product plugin name is provisionally `alborsa-platform`.
- Build the product logic as one modular WordPress plugin independent of the theme.
- Do not start or infer frontend design, theme, CSS, layouts, or visual components until the user says the designer's work is ready.
- Proposed PHP symbols, REST routes, tables, and folders in the documents are target contracts, not existing implementation.

## Existing WordPress stack

The following plugins were observed active in a user-provided WordPress admin screenshot dated 2026-09-15:

- Antispam Bee `2.11.13`
- Breeze `2.5.16`
- Object Cache Pro `1.25.5`
- Polylang `3.8.9`
- WP 2FA `4.1.0`
- Yoast SEO `28.4`
- WPS Hide Login `1.9.19`

Treat these versions as a dated screenshot inventory, not as direct server verification. Audit installed versions, editions, licenses, settings, and health before implementing compatibility-sensitive behavior.

## Architectural constraints

- Use Polylang, not WPML, for multilingual integration.
- Use Yoast SEO, not Rank Math, as the owner of canonical URLs, robots directives, sitemaps, and the base Schema graph.
- Use Breeze for page cache and Object Cache Pro for persistent object cache; do not add WP Rocket or another Redis cache plugin without a new approved decision.
- Never perform a full cache flush for routine quote synchronization.
- Keep affiliate redirects such as `/go/*` outside page cache.
- Treat Antispam Bee as a helper for WordPress comments, not guaranteed protection for custom REST forms or registration.
- Do not hard-code `/wp-login.php`; remain compatible with WPS Hide Login.
- Do not assume WP 2FA is enforced merely because the plugin is active.
- Do not fetch external market APIs during a frontend page request.
- Do not describe market data as real-time until provider licensing and delay level are verified.

## Decisions and documentation

- Check `docs/04-open-decisions.md` before implementing anything dependent on market data, URLs, verified reviews, broker scoring, affiliate tracking, or zakat rules.
- Do not silently resolve an `Open` decision when the choice materially changes product behavior or external cost.
- When a decision is approved or implementation changes a documented contract, update all affected documentation in the same change.
- Preserve the separation between implemented behavior and proposed behavior in documentation.
