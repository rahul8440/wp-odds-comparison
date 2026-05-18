<?php
/**
 * Known bookmakers and admin-configured metadata.
 *
 * @package WPOddsComparison\Bookmakers
 */

declare(strict_types=1);

namespace WPOddsComparison\Bookmakers;

use WPOddsComparison\Core\Settings;

/**
 * Registry of bookmaker keys, labels, and affiliate links.
 */
class BookmakerRegistry {

	/** @var Settings */
	private $settings;

	/**
	 * Common keys returned by The Odds API (extend via filter).
	 *
	 * @return array<string, string> key => default label
	 */
	public static function default_catalog(): array {
		return array(
			'bet365'           => 'Bet365',
			'betfair_ex_uk'    => 'Betfair Exchange',
			'betfair_sb_uk'    => 'Betfair Sportsbook',
			'betmgm'           => 'BetMGM',
			'betrivers'        => 'BetRivers',
			'betway'           => 'Betway',
			'bovada'           => 'Bovada',
			'draftkings'       => 'DraftKings',
			'fanduel'          => 'FanDuel',
			'ladbrokes_uk'     => 'Ladbrokes',
			'pinnacle'         => 'Pinnacle',
			'pointsbetus'      => 'PointsBet',
			'skybet'           => 'Sky Bet',
			'unibet_uk'        => 'Unibet',
			'williamhill'      => 'William Hill',
		);
	}

	/**
	 * @param Settings $settings Plugin settings.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * @return array<string, string>
	 */
	public function all(): array {
		/**
		 * Filter available bookmakers in admin and block UI.
		 *
		 * @param array<string, string> $catalog key => label.
		 */
		return apply_filters( 'wpoc_bookmaker_catalog', self::default_catalog() );
	}

	/**
	 * @param string $key Bookmaker slug.
	 */
	public function get_label( string $key ): string {
		$catalog = $this->all();

		if ( isset( $catalog[ $key ] ) ) {
			$custom = $this->settings->bookmaker_label( $key );
			if ( $custom !== ucwords( str_replace( array( '_', '-' ), ' ', $key ) ) ) {
				return $custom;
			}
			return $catalog[ $key ];
		}

		return $this->settings->bookmaker_label( $key );
	}

	/**
	 * @param string $key Bookmaker slug.
	 */
	public function get_link( string $key ): string {
		$link = $this->settings->bookmaker_link( $key );

		if ( '' !== $link ) {
			return $link;
		}

		/**
		 * Default outbound URL when admin has not set a custom link.
		 *
		 * @param string $url Default URL (empty = no link).
		 * @param string $key Bookmaker key.
		 */
		return apply_filters( 'wpoc_default_bookmaker_link', '', $key );
	}
}
