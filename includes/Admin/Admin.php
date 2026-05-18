<?php
/**
 * Admin bootstrap.
 *
 * @package WPOddsComparison\Admin
 */

declare(strict_types=1);

namespace WPOddsComparison\Admin;

use WPOddsComparison\Core\Settings;

/**
 * Registers admin menus and assets.
 */
class Admin {

	/** @var Settings */
	private $settings;

	/**
	 * @param Settings $settings Plugin settings.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Hook into WordPress admin.
	 */
	public function register(): void {
		$page = new SettingsPage( $this->settings );

		add_action( 'admin_menu', array( $page, 'register_menu' ) );
		add_action( 'admin_init', array( $page, 'register_settings' ) );
		add_action(
			'admin_enqueue_scripts',
			function ( string $hook ) use ( $page ): void {
				if ( strpos( $hook, 'wpoc-settings' ) === false ) {
					return;
				}
				wp_enqueue_style(
					'wpoc-admin',
					WPOC_PLUGIN_URL . 'assets/css/admin.css',
					array(),
					WPOC_VERSION
				);
				wp_enqueue_script(
					'wpoc-admin',
					WPOC_PLUGIN_URL . 'assets/js/admin.js',
					array(),
					WPOC_VERSION,
					true
				);
			}
		);
	}
}
