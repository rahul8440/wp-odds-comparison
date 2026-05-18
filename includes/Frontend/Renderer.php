<?php
/**
 * Front-end HTML rendering for odds tables.
 *
 * @package WPOddsComparison\Frontend
 */

declare(strict_types=1);

namespace WPOddsComparison\Frontend;

use WPOddsComparison\API\OddsFetcherFactory;
use WPOddsComparison\API\OddsService;
use WPOddsComparison\Core\Cache;
use WPOddsComparison\Core\Settings;

/**
 * Builds accessible comparison markup.
 */
class Renderer {

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
	 * Render block output.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	public function render_block( array $attributes ): string {
		$sport      = sanitize_key( $attributes['sport'] ?? $this->settings->get( 'default_sport', 'soccer_epl' ) );
		$markets    = array_map( 'sanitize_key', (array) ( $attributes['markets'] ?? array( 'h2h' ) ) );
		$bookmakers = array_map( 'sanitize_key', (array) ( $attributes['bookmakers'] ?? array() ) );
		$format     = sanitize_key( $attributes['oddsFormat'] ?? $this->settings->odds_format() );
		$title      = sanitize_text_field( (string) ( $attributes['title'] ?? '' ) );
		$max_events = max( 1, min( 20, (int) ( $attributes['maxEvents'] ?? 5 ) ) );

		if ( empty( $bookmakers ) ) {
			$bookmakers = $this->settings->enabled_bookmakers();
		}

		$wrapper_attrs = get_block_wrapper_attributes(
			array(
				'class'               => 'wpoc-odds-comparison',
				'data-sport'          => $sport,
				'data-markets'        => implode( ',', $markets ),
				'data-bookmakers'     => implode( ',', $bookmakers ),
				'data-odds-format'    => $format,
				'data-max-events'     => (string) $max_events,
			)
		);

		ob_start();

		try {
			$service = new OddsService(
				$this->settings,
				$this->cache,
				new OddsFetcherFactory( $this->settings )
			);
			$data    = $service->get_comparison( $sport, $markets, $bookmakers, $format );
			$events  = array_slice( $data['events'], 0, $max_events );

			echo '<div ' . $wrapper_attrs . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			if ( '' !== $title ) {
				echo '<h3 class="wpoc-title">' . esc_html( $title ) . '</h3>';
			}

			if ( empty( $events ) ) {
				echo '<p class="wpoc-empty">' . esc_html__( 'No odds available for this selection.', 'wp-odds-comparison' ) . '</p>';
			} else {
				foreach ( $events as $event ) {
					$this->render_event( $event, $format );
				}
			}

			echo '<p class="wpoc-updated"><small>' . esc_html__(
				'Odds may change. Verify with the bookmaker.',
				'wp-odds-comparison'
			) . '</small></p>';
			echo '</div>';
		} catch ( \Throwable $e ) {
			echo '<div ' . $wrapper_attrs . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<p class="wpoc-error">' . esc_html( $e->getMessage() ) . '</p>';
			echo '</div>';
		}

		return (string) ob_get_clean();
	}

	/**
	 * @param array<string, mixed> $event  Normalized event.
	 * @param string               $format Odds format label.
	 */
	private function render_event( array $event, string $format ): void {
		$home = esc_html( (string) ( $event['home_team'] ?? '' ) );
		$away = esc_html( (string) ( $event['away_team'] ?? '' ) );

		echo '<article class="wpoc-event">';
		echo '<header class="wpoc-event-header"><h4>' . $home . ' <span class="wpoc-vs">vs</span> ' . $away . '</h4></header>';

		echo '<div class="wpoc-table-wrap"><table class="wpoc-table"><thead><tr>';
		echo '<th scope="col">' . esc_html__( 'Bookmaker', 'wp-odds-comparison' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Selection', 'wp-odds-comparison' ) . '</th>';
		echo '<th scope="col">' . esc_html( strtoupper( $format ) ) . '</th>';
		echo '<th scope="col"></th></tr></thead><tbody>';

		foreach ( (array) ( $event['bookmakers'] ?? array() ) as $bm ) {
			$link  = esc_url( (string) ( $bm['link'] ?? '' ) );
			$title = esc_html( (string) ( $bm['title'] ?? '' ) );

			foreach ( (array) ( $bm['outcomes'] ?? array() ) as $outcome ) {
				echo '<tr>';
				echo '<td class="wpoc-bm">' . $title . '</td>';
				echo '<td>' . esc_html( (string) ( $outcome['name'] ?? '' ) );
				if ( null !== ( $outcome['point'] ?? null ) ) {
					echo ' (' . esc_html( (string) $outcome['point'] ) . ')';
				}
				echo '</td>';
				echo '<td class="wpoc-odds"><strong>' . esc_html( (string) ( $outcome['display'] ?? '' ) ) . '</strong></td>';
				echo '<td>';
				if ( '' !== $link ) {
					printf(
						'<a class="wpoc-cta" href="%s" target="_blank" rel="nofollow sponsored noopener">%s</a>',
						$link,
						esc_html__( 'Bet', 'wp-odds-comparison' )
					);
				}
				echo '</td></tr>';
			}
		}

		echo '</tbody></table></div></article>';
	}
}
