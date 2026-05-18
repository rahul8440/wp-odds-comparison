<?php
/**
 * Plugin Name:       WP Odds Comparison
 * Plugin URI:        
 * Description:       Compare live odds from multiple bookmakers with admin controls and a Gutenberg block.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            WP Odds Comparison (Rahul)
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-odds-comparison
 *
 * @package WPOddsComparison
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPOC_VERSION', '1.0.0' );
define( 'WPOC_PLUGIN_FILE', __FILE__ );
define( 'WPOC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPOC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPOC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once WPOC_PLUGIN_DIR . 'includes/Autoloader.php';

WPOddsComparison\Autoloader::register( 'WPOddsComparison', WPOC_PLUGIN_DIR . 'includes' );

/**
 * Returns the singleton plugin instance.
 *
 * @return \WPOddsComparison\Core\Plugin
 */
function wpoc(): \WPOddsComparison\Core\Plugin {
	return \WPOddsComparison\Core\Plugin::instance();
}

add_action(
	'plugins_loaded',
	static function (): void {
		wpoc()->boot();
	}
);

register_activation_hook(
	__FILE__,
	static function (): void {
		wpoc()->activate();
	}
);

register_deactivation_hook(
	__FILE__,
	static function (): void {
		wpoc()->deactivate();
	}
);
