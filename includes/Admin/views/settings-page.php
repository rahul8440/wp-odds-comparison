<?php
/**
 * Admin settings page view.
 *
 * @var array<string, mixed> $settings
 * @var array<string, string> $bookmakers
 * @var array<string, string> $markets
 *
 * @package WPOddsComparison
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap wpoc-admin">
	<h1><?php esc_html_e( 'Odds Comparison Settings', 'wp-odds-comparison' ); ?></h1>

	<?php if ( isset( $_GET['settings-updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved.', 'wp-odds-comparison' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="options.php">
		<?php settings_fields( 'wpoc_settings_group' ); ?>

		<h2 class="title"><?php esc_html_e( 'API Configuration', 'wp-odds-comparison' ); ?></h2>
		<p class="description">
			<?php
			printf(
				/* translators: %s: The Odds API URL */
				esc_html__( 'Obtain a free API key from %s. Live odds are fetched via their REST API (recommended over scraping for reliability and compliance).', 'wp-odds-comparison' ),
				'<a href="https://the-odds-api.com/" target="_blank" rel="noopener noreferrer">The Odds API</a>'
			);
			?>
		</p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="wpoc_api_key"><?php esc_html_e( 'API Key', 'wp-odds-comparison' ); ?></label></th>
				<td>
					<input type="password" id="wpoc_api_key" name="<?php echo esc_attr( \WPOddsComparison\Core\Settings::OPTION_KEY ); ?>[api_key]" value="<?php echo esc_attr( $settings['api_key'] ?? '' ); ?>" class="regular-text" autocomplete="off" />
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="wpoc_cache_ttl"><?php esc_html_e( 'Cache TTL (seconds)', 'wp-odds-comparison' ); ?></label></th>
				<td>
					<input type="number" min="60" step="60" id="wpoc_cache_ttl" name="<?php echo esc_attr( \WPOddsComparison\Core\Settings::OPTION_KEY ); ?>[cache_ttl]" value="<?php echo esc_attr( (string) ( $settings['cache_ttl'] ?? 300 ) ); ?>" />
					<p class="description"><?php esc_html_e( 'Higher values reduce API usage and improve performance.', 'wp-odds-comparison' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="wpoc_default_sport"><?php esc_html_e( 'Default Sport Key', 'wp-odds-comparison' ); ?></label></th>
				<td>
					<input type="text" id="wpoc_default_sport" name="<?php echo esc_attr( \WPOddsComparison\Core\Settings::OPTION_KEY ); ?>[default_sport]" value="<?php echo esc_attr( $settings['default_sport'] ?? 'soccer_epl' ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'e.g. soccer_epl, basketball_nba', 'wp-odds-comparison' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Default Odds Format', 'wp-odds-comparison' ); ?></th>
				<td>
					<select name="<?php echo esc_attr( \WPOddsComparison\Core\Settings::OPTION_KEY ); ?>[default_odds_format]">
						<option value="decimal" <?php selected( $settings['default_odds_format'] ?? '', 'decimal' ); ?>><?php esc_html_e( 'Decimal', 'wp-odds-comparison' ); ?></option>
						<option value="fractional" <?php selected( $settings['default_odds_format'] ?? '', 'fractional' ); ?>><?php esc_html_e( 'Fractional', 'wp-odds-comparison' ); ?></option>
						<option value="american" <?php selected( $settings['default_odds_format'] ?? '', 'american' ); ?>><?php esc_html_e( 'American', 'wp-odds-comparison' ); ?></option>
					</select>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Markets', 'wp-odds-comparison' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enabled Markets', 'wp-odds-comparison' ); ?></th>
				<td>
					<?php foreach ( $markets as $key => $label ) : ?>
						<label style="display:block;margin-bottom:6px;">
							<input type="checkbox" name="<?php echo esc_attr( \WPOddsComparison\Core\Settings::OPTION_KEY ); ?>[enabled_markets][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, (array) ( $settings['enabled_markets'] ?? array() ), true ) ); ?> />
							<?php echo esc_html( $label ); ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Bookmakers', 'wp-odds-comparison' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Select bookmakers to show site-wide and configure outbound links.', 'wp-odds-comparison' ); ?></p>

		<table class="widefat striped wpoc-bookmaker-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Enable', 'wp-odds-comparison' ); ?></th>
					<th><?php esc_html_e( 'Key', 'wp-odds-comparison' ); ?></th>
					<th><?php esc_html_e( 'Display Label', 'wp-odds-comparison' ); ?></th>
					<th><?php esc_html_e( 'Affiliate / Outbound URL', 'wp-odds-comparison' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$enabled = (array) ( $settings['enabled_bookmakers'] ?? array() );
				$links   = (array) ( $settings['bookmaker_links'] ?? array() );
				$labels  = (array) ( $settings['bookmaker_labels'] ?? array() );
				foreach ( $bookmakers as $key => $default_label ) :
					?>
					<tr>
						<td>
							<input type="checkbox" name="<?php echo esc_attr( \WPOddsComparison\Core\Settings::OPTION_KEY ); ?>[enabled_bookmakers][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $enabled, true ) ); ?> />
						</td>
						<td><code><?php echo esc_html( $key ); ?></code></td>
						<td>
							<input type="text" class="regular-text" name="<?php echo esc_attr( \WPOddsComparison\Core\Settings::OPTION_KEY ); ?>[bookmaker_labels][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $labels[ $key ] ?? '' ); ?>" placeholder="<?php echo esc_attr( $default_label ); ?>" />
						</td>
						<td>
							<input type="url" class="large-text" name="<?php echo esc_attr( \WPOddsComparison\Core\Settings::OPTION_KEY ); ?>[bookmaker_links][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $links[ $key ] ?? '' ); ?>" placeholder="https://" />
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php submit_button(); ?>
	</form>
</div>
