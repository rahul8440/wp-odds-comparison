<?php
/**
 * Public-facing asset registration.
 *
 * @package WPOddsComparison\Frontend
 */

declare(strict_types=1);

namespace WPOddsComparison\Frontend;

/**
 * Enqueues frontend scripts for interactive odds refresh.
 */
class Assets {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue block frontend enhancements when block is present.
	 */
	public function enqueue(): void {
		if ( ! has_block( 'wp-odds-comparison/odds-comparison' ) ) {
			return;
		}

		wp_enqueue_style(
			'wpoc-frontend',
			WPOC_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			WPOC_VERSION
		);

		wp_enqueue_script(
			'wpoc-frontend',
			WPOC_PLUGIN_URL . 'assets/js/frontend.js',
			array(),
			WPOC_VERSION,
			true
		);

		wp_localize_script(
			'wpoc-frontend',
			'wpocFrontend',
			array(
				'restUrl' => rest_url( 'wpoc/v1/' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);
	}
}
