<?php
/**
 * Factory for odds provider instances.
 *
 * @package WPOddsComparison\API
 */

declare(strict_types=1);

namespace WPOddsComparison\API;

use WPOddsComparison\Core\Settings;

/**
 * Creates provider implementations — extend via filter for new sources.
 */
class OddsFetcherFactory {

	/** @var Settings */
	private $settings;

	/**
	 * @param Settings $settings Plugin settings.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Default provider used by the plugin.
	 */
	public function create_default(): OddsProviderInterface {
		return $this->create( 'the_odds_api' );
	}

	/**
	 * @param string $provider_id Provider slug.
	 */
	public function create( string $provider_id ): OddsProviderInterface {
		$map = $this->get_provider_map();

		if ( ! isset( $map[ $provider_id ] ) ) {
			throw new \InvalidArgumentException(
				sprintf( 'Unknown odds provider: %s', $provider_id )
			);
		}

		$class = $map[ $provider_id ];

		return new $class( $this->settings );
	}

	/**
	 * Registered providers (filterable for extensions).
	 *
	 * @return array<string, class-string<OddsProviderInterface>>
	 */
	public function get_provider_map(): array {
		$map = array(
			'the_odds_api' => TheOddsApiProvider::class,
		);

		/**
		 * Register additional odds providers.
		 *
		 * @param array<string, class-string<OddsProviderInterface>> $map Provider map.
		 * @param Settings $settings Settings instance.
		 */
		return apply_filters( 'wpoc_odds_providers', $map, $this->settings );
	}
}
