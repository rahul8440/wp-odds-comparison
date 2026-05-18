<?php
/**
 * Gutenberg block registration and server-side render.
 *
 * @package WPOddsComparison\Blocks
 */

declare(strict_types=1);

namespace WPOddsComparison\Blocks;

use WPOddsComparison\Bookmakers\BookmakerRegistry;
use WPOddsComparison\Core\Cache;
use WPOddsComparison\Core\Settings;
use WPOddsComparison\Frontend\Renderer;

/**
 * Registers the odds comparison block.
 */
class OddsComparisonBlock {

	/** @var Settings */
	private $settings;

	/** @var Cache */
	private $cache;

	/**
	 * @param Settings $settings Settings.
	 * @param Cache    $cache    Cache.
	 */
	public function __construct( Settings $settings, Cache $cache ) {
		$this->settings = $settings;
		$this->cache    = $cache;
	}

	/**
	 * Register block type and assets.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Register block from block.json with dynamic render callback.
	 */
	public function register_block(): void {
		$asset_file = WPOC_PLUGIN_DIR . 'build/block/index.asset.php';
		$script     = 'wpoc-block-editor';
		$style      = 'wpoc-block-editor-style';

		$deps    = array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-api-fetch' );
		$version = WPOC_VERSION;

		if ( file_exists( $asset_file ) ) {
			$asset   = require $asset_file;
			$deps    = $asset['dependencies'] ?? $deps;
			$version = $asset['version'] ?? $version;
			wp_register_script(
				$script,
				WPOC_PLUGIN_URL . 'build/block/index.js',
				$deps,
				$version,
				true
			);
		} else {
			wp_register_script(
				$script,
				WPOC_PLUGIN_URL . 'assets/js/block/index.js',
				$deps,
				$version,
				true
			);
		}

		wp_localize_script(
			$script,
			'wpocBlock',
			array(
				'restUrl'       => rest_url( 'wpoc/v1/' ),
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'bookmakers'    => $this->get_bookmaker_options(),
				'defaultSport'  => (string) $this->settings->get( 'default_sport', 'soccer_epl' ),
				'defaultFormat' => $this->settings->odds_format(),
				'markets'       => array(
					array( 'value' => 'h2h', 'label' => __( 'Head to Head', 'wp-odds-comparison' ) ),
					array( 'value' => 'spreads', 'label' => __( 'Spreads', 'wp-odds-comparison' ) ),
					array( 'value' => 'totals', 'label' => __( 'Totals', 'wp-odds-comparison' ) ),
				),
			)
		);

		wp_register_style(
			$style,
			WPOC_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			WPOC_VERSION
		);

		register_block_type(
			WPOC_PLUGIN_DIR . 'block.json',
			array(
				'editor_script'   => $script,
				'editor_style'    => $style,
				'style'           => $style,
				'render_callback' => array( $this, 'render' ),
			)
		);
	}

	/**
	 * Server-side render for SEO and no-JS fallback.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public function render( array $attributes ): string {
		$renderer = new Renderer( $this->settings, $this->cache );

		return $renderer->render_block( $attributes );
	}

	/**
	 * @return array<int, array{value: string, label: string}>
	 */
	private function get_bookmaker_options(): array {
		$registry = new BookmakerRegistry( $this->settings );
		$options  = array();

		foreach ( $registry->all() as $key => $label ) {
			$options[] = array(
				'value' => $key,
				'label' => $label,
			);
		}

		return $options;
	}
}
