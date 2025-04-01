<?php
/**
 * Main plugin orchestrator class.
 *
 * @package AJL_WP_Simple_Pay_Styles
 */

namespace AJL_WP_Simple_Pay_Styles;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class AJL_Styler.
 */
final class AJL_Styler {

	/**
	 * The single instance of the class.
	 *
	 * @var AJL_Styler
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Main AJL_Styler Instance.
	 *
	 * Ensures only one instance of AJL_Styler is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @return AJL_Styler - Main instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function includes() {
		require_once AJL_WPSPS_PATH . 'includes/class-ajl-settings.php';
		require_once AJL_WPSPS_PATH . 'includes/class-ajl-admin-ui.php';
		require_once AJL_WPSPS_PATH . 'includes/class-ajl-frontend.php';
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function init_hooks() {
		// Initialize Admin UI and Frontend handlers
		if ( is_admin() ) {
			AJL_Admin_UI::get_instance();
		} else {
			AJL_Frontend::get_instance();
		}

		// Additional hooks can be added here if needed directly in the main class
	}
} 