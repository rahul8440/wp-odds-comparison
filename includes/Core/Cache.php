<?php
/**
 * Transient-based caching for odds API responses.
 *
 * @package WPOddsComparison\Core
 */

declare(strict_types=1);

namespace WPOddsComparison\Core;

/**
 * Singleton cache manager using WordPress transients.
 */
final class Cache {

	/** @var self|null */
	private static $instance = null;

	public const PREFIX = 'wpoc_';

	/**
	 * Get shared cache instance (optional pattern alongside DI).
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Build a deterministic cache key from parts.
	 *
	 * @param string ...$parts Key segments.
	 * @return string
	 */
	public function key( string ...$parts ): string {
		return self::PREFIX . md5( implode( '|', $parts ) );
	}

	/**
	 * @param string $key Cache key.
	 * @return mixed|null
	 */
	public function get( string $key ) {
		$value = get_transient( $key );

		return false === $value ? null : $value;
	}

	/**
	 * @param string $key   Cache key.
	 * @param mixed  $data  Data to store.
	 * @param int    $ttl   Seconds until expiry.
	 */
	public function set( string $key, $data, int $ttl ): void {
		set_transient( $key, $data, $ttl );
	}

	/**
	 * @param string $key Cache key.
	 */
	public function delete( string $key ): void {
		delete_transient( $key );
	}

	/**
	 * Flush all plugin transients (best-effort).
	 */
	public function flush_all(): void {
		global $wpdb;

		if ( ! isset( $wpdb ) ) {
			return;
		}

		$like = '_transient_' . self::PREFIX . '%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$like,
				'_transient_timeout_' . self::PREFIX . '%'
			)
		);
	}

	/**
	 * Remember pattern: fetch via callback if cache miss.
	 *
	 * @param string   $key      Cache key.
	 * @param int      $ttl      TTL seconds.
	 * @param callable $callback Data producer.
	 * @return mixed
	 */
	public function remember( string $key, int $ttl, callable $callback ) {
		$cached = $this->get( $key );

		if ( null !== $cached ) {
			return $cached;
		}

		$data = $callback();
		$this->set( $key, $data, $ttl );

		return $data;
	}
}
