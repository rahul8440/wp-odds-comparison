<?php
/**
 * The Odds API (https://the-odds-api.com/) provider implementation.
 *
 * @package WPOddsComparison\API
 */

declare(strict_types=1);

namespace WPOddsComparison\API;

use WPOddsComparison\Core\Settings;

/**
 * Fetches live odds via The Odds API REST service.
 */
class TheOddsApiProvider implements OddsProviderInterface {

	private const BASE_URL = 'https://api.the-odds-api.com/v4';

	/** @var Settings */
	private $settings;

	/**
	 * @param Settings $settings Plugin settings.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_id(): string {
		return 'the_odds_api';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return 'The Odds API';
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_configured(): bool {
		return '' !== $this->settings->api_key();
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_sports(): array {
		$body = $this->request( '/sports', array() );

		if ( ! is_array( $body ) ) {
			return array();
		}

		$out = array();
		foreach ( $body as $sport ) {
			if ( empty( $sport['key'] ) ) {
				continue;
			}
			$out[] = array(
				'key'   => (string) $sport['key'],
				'title' => (string) ( $sport['title'] ?? $sport['key'] ),
				'group' => (string) ( $sport['group'] ?? '' ),
			);
		}

		return $out;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_odds( string $sport_key, array $markets, array $bookmakers = array() ): array {
		$params = array(
			'regions'  => 'uk,eu,us',
			'markets'  => implode( ',', $markets ),
			'oddsFormat' => 'decimal',
		);

		if ( ! empty( $bookmakers ) ) {
			$params['bookmakers'] = implode( ',', $bookmakers );
		}

		$path = '/sports/' . rawurlencode( $sport_key ) . '/odds';
		$body = $this->request( $path, $params );

		return is_array( $body ) ? $body : array();
	}

	/**
	 * Perform authenticated GET request.
	 *
	 * @param string               $path   API path.
	 * @param array<string, string> $query Query parameters.
	 * @return mixed Decoded JSON.
	 */
	private function request( string $path, array $query ) {
		if ( ! $this->is_configured() ) {
			throw new \RuntimeException(
				__( 'The Odds API key is not configured.', 'wp-odds-comparison' )
			);
		}

		$query['apiKey'] = $this->settings->api_key();
		$url             = self::BASE_URL . $path . '?' . http_build_query( $query );

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new \RuntimeException( $response->get_error_message() );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( 429 === $code ) {
			throw new \RuntimeException(
				__( 'Rate limit exceeded. Try again later or increase cache TTL.', 'wp-odds-comparison' )
			);
		}

		if ( $code < 200 || $code >= 300 ) {
			$message = is_array( $data ) && isset( $data['message'] )
				? (string) $data['message']
				: __( 'Unable to fetch odds from provider.', 'wp-odds-comparison' );
			throw new \RuntimeException( $message );
		}

		/**
		 * Allow observers to react to API responses (logging, metrics).
		 *
		 * @param mixed  $data     Response body.
		 * @param string $path     Request path.
		 * @param int    $code     HTTP status.
		 */
		do_action( 'wpoc_api_response', $data, $path, $code );

		return $data;
	}
}
