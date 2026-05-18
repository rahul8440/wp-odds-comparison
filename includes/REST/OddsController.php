<?php
/**
 * REST API endpoints for block and frontend.
 *
 * @package WPOddsComparison\REST
 */

declare(strict_types=1);

namespace WPOddsComparison\REST;

use WPOddsComparison\API\OddsFetcherFactory;
use WPOddsComparison\API\OddsService;
use WPOddsComparison\Bookmakers\BookmakerRegistry;
use WPOddsComparison\Core\Cache;
use WPOddsComparison\Core\Settings;
use WPOddsComparison\Odds\OddsConverter;

/**
 * Registers wp-json/wpoc/v1 routes.
 */
class OddsController {

	public const NAMESPACE = 'wpoc/v1';

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
	 * Register routes on rest_api_init.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Define REST routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/odds',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_odds' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'sport'       => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_key',
					),
					'markets'     => array(
						'type'    => 'string',
						'default' => '',
					),
					'bookmakers'  => array(
						'type'    => 'string',
						'default' => '',
					),
					'odds_format' => array(
						'type'              => 'string',
						'default'           => 'decimal',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/bookmakers',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_bookmakers' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/convert',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'convert_odds' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'value'  => array( 'required' => true ),
					'from'   => array( 'default' => 'decimal' ),
					'to'     => array( 'default' => 'decimal' ),
				),
			)
		);
	}

	/**
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_odds( \WP_REST_Request $request ) {
		$sport = $request->get_param( 'sport' ) ?: (string) $this->settings->get( 'default_sport', 'soccer_epl' );

		$markets = $this->parse_list( (string) $request->get_param( 'markets' ) );
		if ( empty( $markets ) ) {
			$markets = $this->settings->enabled_markets();
		}

		$bookmakers = $this->parse_list( (string) $request->get_param( 'bookmakers' ) );
		$format     = $request->get_param( 'odds_format' ) ?: $this->settings->odds_format();

		if ( ! in_array( $format, array( 'decimal', 'fractional', 'american' ), true ) ) {
			$format = 'decimal';
		}

		try {
			$service = new OddsService(
				$this->settings,
				$this->cache,
				new OddsFetcherFactory( $this->settings )
			);

			$data = $service->get_comparison( $sport, $markets, $bookmakers, $format );

			return rest_ensure_response( $data );
		} catch ( \Throwable $e ) {
			return new \WP_Error(
				'wpoc_fetch_error',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * @return \WP_REST_Response
	 */
	public function get_bookmakers() {
		$registry = new BookmakerRegistry( $this->settings );
		$list     = array();

		foreach ( $registry->all() as $key => $label ) {
			$list[] = array(
				'key'   => $key,
				'label' => $label,
				'link'  => $registry->get_link( $key ),
			);
		}

		return rest_ensure_response( $list );
	}

	/**
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function convert_odds( \WP_REST_Request $request ) {
		$converter = new OddsConverter();
		$value     = $request->get_param( 'value' );
		$from      = (string) $request->get_param( 'from' );
		$to        = (string) $request->get_param( 'to' );

		$decimal = $converter->to_decimal( $value, $from );

		return rest_ensure_response(
			array(
				'decimal'    => $decimal,
				'formatted'  => $converter->format( $decimal, $to ),
				'fractional' => $converter->format( $decimal, OddsConverter::FORMAT_FRACTIONAL ),
				'american'   => $converter->format( $decimal, OddsConverter::FORMAT_AMERICAN ),
			)
		);
	}

	/**
	 * @param string $csv Comma-separated list.
	 * @return string[]
	 */
	private function parse_list( string $csv ): array {
		if ( '' === trim( $csv ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map( 'sanitize_key', explode( ',', $csv ) )
			)
		);
	}
}
