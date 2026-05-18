<?php
/**
 * Main plugin bootstrap (Singleton).
 *
 * @package WPOddsComparison\Core
 */

declare(strict_types=1);

namespace WPOddsComparison\Core;

use WPOddsComparison\Admin\Admin;
use WPOddsComparison\Blocks\OddsComparisonBlock;
use WPOddsComparison\Frontend\Assets;
use WPOddsComparison\REST\OddsController;

/**
 * Central plugin orchestrator.
 */
final class Plugin {

	/** @var self|null */
	private static $instance = null;

	/** @var Settings */
	private $settings;

	/** @var Cache */
	private $cache;

	/**
	 * Get singleton instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor — use instance().
	 */
	private function __construct() {
		$this->settings = new Settings();
		$this->cache    = new Cache();
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Boot plugin components after WordPress loads.
	 */
	public function boot(): void {
		load_plugin_textdomain( 'wp-odds-comparison', false, dirname( WPOC_PLUGIN_BASENAME ) . '/languages' );

		if ( is_admin() ) {
			( new Admin( $this->settings ) )->register();
		}

		( new Assets() )->register();
		( new OddsComparisonBlock( $this->settings, $this->cache ) )->register();
		( new OddsController( $this->settings, $this->cache ) )->register();

		/**
		 * Fires when the plugin has finished booting.
		 *
		 * @param Plugin $plugin Plugin instance.
		 */
		do_action( 'wpoc_loaded', $this );
	}

	/**
	 * Activation: seed defaults and flush rewrite rules.
	 */
	public function activate(): void {
		$this->settings->ensure_defaults();
		flush_rewrite_rules();
	}

	/**
	 * Deactivation: clear scheduled events if any.
	 */
	public function deactivate(): void {
		$this->cache->flush_all();
		wp_clear_scheduled_hook( 'wpoc_refresh_odds_cache' );
	}

	/**
	 * @return Settings
	 */
	public function settings(): Settings {
		return $this->settings;
	}

	/**
	 * @return Cache
	 */
	public function cache(): Cache {
		return $this->cache;
	}
}
