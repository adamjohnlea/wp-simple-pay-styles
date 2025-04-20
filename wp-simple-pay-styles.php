<?php
/**
 * Plugin Name:       WP Simple Pay Styles
 * Plugin URI:        https://ajohnlea.com/plugins/wp-simple-pay-styles/
 * Description:       Customize the appearance of WP Simple Pay on-site forms.
 * Version:           1.0.0
 * Author:            Your Name Here
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-simple-pay-styles
 * Domain Path:       /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define constants.
 */
define( 'AJL_WPSPS_BASENAME', plugin_basename( __FILE__ ) );
define( 'AJL_WPSPS_VERSION', '1.0.0' );
define( 'AJL_WPSPS_PATH', plugin_dir_path( __FILE__ ) );
define( 'AJL_WPSPS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load the main plugin class.
 */
require_once AJL_WPSPS_PATH . 'includes/class-ajl-styler.php';

/**
 * Initialize the plugin.
 */
function ajl_wpsps_init() {
    // Check if WP Simple Pay Pro is active
    if ( ! class_exists( '\SimplePay\Pro\SimplePayPro' ) && ! class_exists( '\SimplePay\Core\SimplePay' ) ) {
        add_action( 'admin_notices', 'ajl_wpsps_admin_notice_missing_wpsp' );
        return;
    }
	// Check if WP Simple Pay version is sufficient (Requires UPE/Elements features)
    // Minimum version check might be needed here depending on hooks used. For now, let's assume recent enough.
    // if ( defined( 'SIMPLE_PAY_VERSION' ) && version_compare( SIMPLE_PAY_VERSION, '4.7.0', '<' ) ) {
    //     add_action( 'admin_notices', 'ajl_wpsps_admin_notice_wpsp_version' );
    //     return;
    // }


	AJL_WP_Simple_Pay_Styles\AJL_Styler::get_instance();
}
add_action( 'plugins_loaded', 'ajl_wpsps_init' );

/**
 * Admin notice if WP Simple Pay is missing.
 */
function ajl_wpsps_admin_notice_missing_wpsp() {
	?>
	<div class="notice notice-error is-dismissible">
		<p><?php esc_html_e( 'WP Simple Pay Styles requires WP Simple Pay Pro or Lite to be installed and active.', 'ajl-wp-simple-pay-styles' ); ?></p>
	</div>
	<?php
}

// Removed the commented-out ajl_wpsps_admin_notice_wpsp_version function entirely. 