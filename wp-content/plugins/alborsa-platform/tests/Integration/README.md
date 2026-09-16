# Integration test boundary

The isolated PHPUnit suite covers the Foundation registry, dependency detection,
and repeatable migration orchestration. Role persistence, multisite activation,
Site Health rendering, and WP-CLI exit behavior require a real WordPress test
installation and are intentionally not replaced with mocks.

Add the WordPress integration suite here when the project standardizes its local
WordPress/Docker test environment.

