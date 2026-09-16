<?php
/**
 * Read-only soft dependency detection.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Foundation;

defined( 'ABSPATH' ) || exit;

final class DependencyChecker {
	/**
	 * @var array<string, array{label: string, category: string, plugin_files: list<string>, runtime_markers: list<array{string, string}>}>
	 */
	private array $definitions;

	/** @var list<string>|null */
	private ?array $provided_active_plugins;

	/**
	 * @param array<string, array{label: string, category: string, plugin_files: list<string>, runtime_markers: list<array{string, string}>}> $definitions Definitions keyed by dependency slug.
	 * @param list<string>|null                                                                                                                $active_plugins Active plugin files for isolated checks.
	 */
	public function __construct( array $definitions, ?array $active_plugins = null ) {
		$this->definitions             = $definitions;
		$this->provided_active_plugins = $active_plugins;
	}

	/**
	 * @return array<string, array{key: string, label: string, category: string, available: bool}>
	 */
	public function statuses(): array {
		$active_plugins = $this->active_plugins();
		$statuses       = array();

		foreach ( $this->definitions as $key => $definition ) {
			$statuses[ $key ] = array(
				'key'       => $key,
				'label'     => $definition['label'],
				'category'  => $definition['category'],
				'available' => $this->is_available( $definition, $active_plugins ),
			);
		}

		return $statuses;
	}

	/**
	 * @return list<string>
	 */
	private function active_plugins(): array {
		if ( null !== $this->provided_active_plugins ) {
			return $this->provided_active_plugins;
		}

		$site_plugins    = get_option( 'active_plugins', array() );
		$network_plugins = is_multisite() ? get_site_option( 'active_sitewide_plugins', array() ) : array();

		$site_plugins    = is_array( $site_plugins ) ? $site_plugins : array();
		$network_plugins = is_array( $network_plugins ) ? array_keys( $network_plugins ) : array();

		return array_values( array_unique( array_merge( $site_plugins, $network_plugins ) ) );
	}

	/**
	 * @param array{plugin_files: list<string>, runtime_markers: list<array{string, string}>} $definition Dependency definition.
	 * @param list<string>                                                                    $active_plugins Active plugin files.
	 */
	private function is_available( array $definition, array $active_plugins ): bool {
		if ( array_intersect( $definition['plugin_files'], $active_plugins ) ) {
			return true;
		}

		foreach ( $definition['runtime_markers'] as $marker ) {
			if ( $this->marker_exists( $marker[0], $marker[1] ) ) {
				return true;
			}
		}

		return false;
	}

	private function marker_exists( string $type, string $name ): bool {
		if ( 'constant' === $type ) {
			return defined( $name );
		}

		if ( 'function' === $type ) {
			return function_exists( $name );
		}

		return 'class' === $type && class_exists( $name, false );
	}
}
