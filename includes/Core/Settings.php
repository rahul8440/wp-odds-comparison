<?php
/**
 * Plugin settings storage and accessors.
 *
 * @package WPOddsComparison\Core
 */

declare(strict_types=1);

namespace WPOddsComparison\Core;

/**
 * Manages wp_options for plugin configuration.
 */
class Settings {

	public const OPTION_KEY = 'wpoc_settings';

	/** @var array<string, mixed>|null */
	private $cached = null;

	/**
	 * Default configuration applied on activation.
	 *
	 * @return array<string, mixed>
	 */
	public function defaults(): array {
		return array(
			'api_key'              => '',
			'cache_ttl'            => 300,
			'default_odds_format'  => 'decimal',
			'enabled_bookmakers'   => array(),
			'enabled_markets'      => array( 'h2h' ),
			'default_sport'        => 'soccer_epl',
			'bookmaker_links'      => array(),
			'bookmaker_labels'     => array(),
		);
	}

	/**
	 * Ensure options exist in the database.
	 */
	public function ensure_defaults(): void {
		if ( false === get_option( self::OPTION_KEY, false ) ) {
			add_option( self::OPTION_KEY, $this->defaults() );
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	public function all(): array {
		if ( null === $this->cached ) {
			$stored        = get_option( self::OPTION_KEY, array() );
			$this->cached  = array_merge( $this->defaults(), is_array( $stored ) ? $stored : array() );
		}

		return $this->cached;
	}

	/**
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback value.
	 * @return mixed
	 */
	public function get( string $key, $default = null ) {
		$all = $this->all();

		return $all[ $key ] ?? $default;
	}

	/**
	 * Persist settings and bust in-memory cache.
	 *
	 * @param array<string, mixed> $data Settings to merge.
	 */
	public function save( array $data ): void {
		$merged       = array_merge( $this->all(), $data );
		$this->cached = $merged;
		update_option( self::OPTION_KEY, $merged );
	}

	/**
	 * Reset in-memory cache after external updates.
	 */
	public function refresh(): void {
		$this->cached = null;
	}

	/**
	 * @return string
	 */
	public function api_key(): string {
		return (string) $this->get( 'api_key', '' );
	}

	/**
	 * @return int Cache TTL in seconds.
	 */
	public function cache_ttl(): int {
		return max( 60, (int) $this->get( 'cache_ttl', 300 ) );
	}

	/**
	 * @return string decimal|fractional|american
	 */
	public function odds_format(): string {
		return (string) $this->get( 'default_odds_format', 'decimal' );
	}

	/**
	 * @return string[]
	 */
	public function enabled_bookmakers(): array {
		$list = $this->get( 'enabled_bookmakers', array() );

		return is_array( $list ) ? array_values( array_filter( $list ) ) : array();
	}

	/**
	 * @return string[]
	 */
	public function enabled_markets(): array {
		$list = $this->get( 'enabled_markets', array( 'h2h' ) );

		return is_array( $list ) ? array_values( array_filter( $list ) ) : array( 'h2h' );
	}

	/**
	 * Affiliate or outbound URL for a bookmaker key.
	 *
	 * @param string $key Bookmaker slug.
	 * @return string
	 */
	public function bookmaker_link( string $key ): string {
		$links = $this->get( 'bookmaker_links', array() );

		return is_array( $links ) && isset( $links[ $key ] ) ? esc_url( (string) $links[ $key ] ) : '';
	}

	/**
	 * Display label override for bookmaker.
	 *
	 * @param string $key Bookmaker slug.
	 * @return string
	 */
	public function bookmaker_label( string $key ): string {
		$labels = $this->get( 'bookmaker_labels', array() );

		if ( is_array( $labels ) && ! empty( $labels[ $key ] ) ) {
			return (string) $labels[ $key ];
		}

		return ucwords( str_replace( array( '_', '-' ), ' ', $key ) );
	}
}
