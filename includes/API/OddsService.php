<?php
/**
 * High-level odds retrieval with caching and normalization.
 *
 * @package WPOddsComparison\API
 */

declare(strict_types=1);

namespace WPOddsComparison\API;

use WPOddsComparison\Bookmakers\BookmakerRegistry;
use WPOddsComparison\Core\Cache;
use WPOddsComparison\Core\Settings;
use WPOddsComparison\Odds\OddsConverter;

/**
 * Coordinates provider, cache, and presentation-ready structures.
 */
class OddsService {

	/** @var OddsFetcherFactory */
	private $factory;

	/** @var Settings */
	private $settings;

	/** @var Cache */
	private $cache;

	/** @var BookmakerRegistry */
	private $registry;

	/** @var OddsConverter */
	private $converter;

	/**
	 * @param Settings           $settings  Settings.
	 * @param Cache              $cache     Cache manager.
	 * @param OddsFetcherFactory $factory   Provider factory.
	 */
	public function __construct( Settings $settings, Cache $cache, OddsFetcherFactory $factory ) {
		$this->settings  = $settings;
		$this->cache     = $cache;
		$this->factory   = $factory;
		$this->registry  = new BookmakerRegistry( $settings );
		$this->converter = new OddsConverter();
	}

	/**
	 * Get normalized comparison rows for frontend.
	 *
	 * @param string   $sport_key    Sport key.
	 * @param string[] $markets      Markets to include.
	 * @param string[] $bookmakers   Bookmaker keys (empty = use enabled from settings).
	 * @param string   $odds_format  decimal|fractional|american.
	 * @return array{events: array<int, array<string, mixed>>, meta: array<string, mixed>}
	 */
	public function get_comparison(
		string $sport_key,
		array $markets,
		array $bookmakers = array(),
		string $odds_format = 'decimal'
	): array {
		$bookmakers = ! empty( $bookmakers ) ? $bookmakers : $this->settings->enabled_bookmakers();
		$markets    = ! empty( $markets ) ? $markets : $this->settings->enabled_markets();

		$cache_key = $this->cache->key( 'odds', $sport_key, implode( ',', $markets ), implode( ',', $bookmakers ) );
		$ttl       = $this->settings->cache_ttl();

		$raw = $this->cache->remember(
			$cache_key,
			$ttl,
			function () use ( $sport_key, $markets, $bookmakers ) {
				$provider = $this->factory->create_default();

				return $provider->get_odds( $sport_key, $markets, $bookmakers );
			}
		);

		$events = $this->normalize_events( $raw, $bookmakers, $odds_format );

		return array(
			'events' => $events,
			'meta'   => array(
				'sport'         => $sport_key,
				'markets'       => $markets,
				'bookmakers'    => $bookmakers,
				'odds_format'   => $odds_format,
				'cached_until'  => time() + $ttl,
			),
		);
	}

	/**
	 * Transform API payload into comparison-friendly rows.
	 *
	 * @param array<int, array<string, mixed>> $raw         API events.
	 * @param string[]                         $bookmakers  Allowed bookmakers.
	 * @param string                           $odds_format Display format.
	 * @return array<int, array<string, mixed>>
	 */
	private function normalize_events( array $raw, array $bookmakers, string $odds_format ): array {
		$events = array();

		foreach ( $raw as $event ) {
			if ( empty( $event['id'] ) ) {
				continue;
			}

			$row = array(
				'id'           => (string) $event['id'],
				'sport_key'    => (string) ( $event['sport_key'] ?? '' ),
				'home_team'    => (string) ( $event['home_team'] ?? '' ),
				'away_team'    => (string) ( $event['away_team'] ?? '' ),
				'commence_time'=> (string) ( $event['commence_time'] ?? '' ),
				'bookmakers'   => array(),
			);

			foreach ( (array) ( $event['bookmakers'] ?? array() ) as $bm ) {
				$key = (string) ( $bm['key'] ?? '' );
				if ( '' === $key || ( ! empty( $bookmakers ) && ! in_array( $key, $bookmakers, true ) ) ) {
					continue;
				}

				$outcomes = array();
				foreach ( (array) ( $bm['markets'] ?? array() ) as $market ) {
					$market_key = (string) ( $market['key'] ?? '' );
					foreach ( (array) ( $market['outcomes'] ?? array() ) as $outcome ) {
						$decimal = isset( $outcome['price'] ) ? (float) $outcome['price'] : 0.0;
						if ( $decimal <= 1.0 ) {
							continue;
						}
						$outcomes[] = array(
							'market'  => $market_key,
							'name'    => (string) ( $outcome['name'] ?? '' ),
							'point'   => isset( $outcome['point'] ) ? (float) $outcome['point'] : null,
							'decimal' => $decimal,
							'display' => $this->converter->format( $decimal, $odds_format ),
						);
					}
				}

				if ( empty( $outcomes ) ) {
					continue;
				}

				$row['bookmakers'][ $key ] = array(
					'key'   => $key,
					'title' => $this->registry->get_label( $key ),
					'link'  => $this->registry->get_link( $key ),
					'outcomes' => $outcomes,
				);
			}

			if ( ! empty( $row['bookmakers'] ) ) {
				$events[] = $row;
			}
		}

		/**
		 * Filter normalized events before output.
		 *
		 * @param array $events Normalized events.
		 */
		return apply_filters( 'wpoc_normalized_events', $events );
	}

	/**
	 * @return array<int, array{key: string, title: string}>
	 */
	public function get_sports(): array {
		$cache_key = $this->cache->key( 'sports' );
		$ttl       = max( 3600, $this->settings->cache_ttl() );

		return $this->cache->remember(
			$cache_key,
			$ttl,
			function () {
				$provider = $this->factory->create_default();

				return $provider->get_sports();
			}
		);
	}
}
