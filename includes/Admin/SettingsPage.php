<?php
/**
 * Plugin settings admin page.
 *
 * @package WPOddsComparison\Admin
 */

declare(strict_types=1);

namespace WPOddsComparison\Admin;

use WPOddsComparison\Bookmakers\BookmakerRegistry;
use WPOddsComparison\Core\Cache;
use WPOddsComparison\Core\Settings;

/**
 * Renders and saves odds comparison settings.
 */
class SettingsPage {

	/** @var Settings */
	private $settings;

	/**
	 * @param Settings $settings Plugin settings.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Add options page under Settings.
	 */
	public function register_menu(): void {
		add_options_page(
			__( 'Odds Comparison', 'wp-odds-comparison' ),
			__( 'Odds Comparison', 'wp-odds-comparison' ),
			'manage_options',
			'wpoc-settings',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register setting with sanitization callback.
	 */
	public function register_settings(): void {
		register_setting(
			'wpoc_settings_group',
			Settings::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
			)
		);
	}

	/**
	 * @param array<string, mixed> $input Raw POST data.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ): array {
		if ( ! is_array( $input ) ) {
			return $this->settings->all();
		}

		$clean = $this->settings->all();

		$clean['api_key']             = sanitize_text_field( $input['api_key'] ?? '' );
		$clean['cache_ttl']           = max( 60, absint( $input['cache_ttl'] ?? 300 ) );
		$clean['default_odds_format'] = in_array(
			$input['default_odds_format'] ?? 'decimal',
			array( 'decimal', 'fractional', 'american' ),
			true
		) ? $input['default_odds_format'] : 'decimal';
		$clean['default_sport']       = sanitize_key( $input['default_sport'] ?? 'soccer_epl' );

		$clean['enabled_bookmakers'] = array_map(
			'sanitize_key',
			array_filter( (array) ( $input['enabled_bookmakers'] ?? array() ) )
		);
		$clean['enabled_markets'] = array_map(
			'sanitize_key',
			array_filter( (array) ( $input['enabled_markets'] ?? array( 'h2h' ) ) )
		);

		$links  = array();
		$labels = array();
		$raw_links  = (array) ( $input['bookmaker_links'] ?? array() );
		$raw_labels = (array) ( $input['bookmaker_labels'] ?? array() );

		foreach ( BookmakerRegistry::default_catalog() as $key => $default_label ) {
			if ( isset( $raw_links[ $key ] ) ) {
				$links[ $key ] = esc_url_raw( $raw_links[ $key ] );
			}
			if ( isset( $raw_labels[ $key ] ) && '' !== trim( (string) $raw_labels[ $key ] ) ) {
				$labels[ $key ] = sanitize_text_field( $raw_labels[ $key ] );
			}
		}

		$clean['bookmaker_links']  = $links;
		$clean['bookmaker_labels'] = $labels;

		// Invalidate API caches when settings change (Observer-style hook).
		Cache::instance()->flush_all();
		$this->settings->refresh();

		return $clean;
	}

	/**
	 * Render settings template.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings  = $this->settings->all();
		$registry  = new BookmakerRegistry( $this->settings );
		$bookmakers = $registry->all();
		$markets    = array(
			'h2h'     => __( 'Head to Head (Moneyline)', 'wp-odds-comparison' ),
			'spreads' => __( 'Spreads', 'wp-odds-comparison' ),
			'totals'  => __( 'Totals (Over/Under)', 'wp-odds-comparison' ),
		);

		include WPOC_PLUGIN_DIR . 'includes/Admin/views/settings-page.php';
	}
}
