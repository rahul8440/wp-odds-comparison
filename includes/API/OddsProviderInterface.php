<?php
/**
 * Strategy interface for odds data providers.
 *
 * @package WPOddsComparison\API
 */

declare(strict_types=1);

namespace WPOddsComparison\API;

/**
 * Contract for modular odds sources (API, scraper adapters, mocks).
 */
interface OddsProviderInterface {

	/**
	 * Unique provider identifier.
	 */
	public function get_id(): string;

	/**
	 * Human-readable provider name.
	 */
	public function get_name(): string;

	/**
	 * Whether the provider is configured (e.g. API key present).
	 */
	public function is_configured(): bool;

	/**
	 * List available sports keys.
	 *
	 * @return array<int, array{key: string, title: string, group: string}>
	 */
	public function get_sports(): array;

	/**
	 * Fetch odds for a sport and markets.
	 *
	 * @param string   $sport_key   Sport identifier.
	 * @param string[] $markets     Market keys (h2h, spreads, totals).
	 * @param string[] $bookmakers  Optional filter of bookmaker keys.
	 * @return array<int, array<string, mixed>>
	 * @throws \RuntimeException On fetch failure.
	 */
	public function get_odds( string $sport_key, array $markets, array $bookmakers = array() ): array;
}
