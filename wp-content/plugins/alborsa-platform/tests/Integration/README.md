# Integration test boundary

The isolated PHPUnit suite covers the Foundation registry, dependency detection,
repeatable migration orchestration, and schema v2 table SQL definition for
`{$wpdb->prefix}alb_relationships` (e.g. `wp_alb_relationships` on a single-site
setup). Role persistence, multisite activation across blogs via the existing
lifecycle, actual MySQL/MariaDB table creation via WordPress `dbDelta`, Site
Health rendering, and WP-CLI exit behavior require a real WordPress test
installation and are intentionally not replaced with mock-heavy database simulations.

Add the WordPress integration suite here when the project standardizes its local
WordPress/Docker test environment.

