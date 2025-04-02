<?php
/**
 * Admin UI Handler
 *
 * @package AJL_WP_Simple_Pay_Styles
 */

namespace AJL_WP_Simple_Pay_Styles;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class AJL_Admin_UI
 */
class AJL_Admin_UI {

	/**
	 * Instance.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    AJL_Admin_UI The single instance of the class.
	 */
	private static $instance = null;

	/**
	 * Ensures only one instance of AJL_Admin_UI is loaded or can be loaded.
	 *
	 * @since  1.0.0
	 * @access public
	 * @static
	 * @return AJL_Admin_UI An instance of the class.
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
		// Add hooks
		$this->hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function hooks() {
		// Hook into the WP Simple Pay 'General' tab action
		add_action( 'simpay_form_settings_display_options_panel', [ $this, 'render_style_settings_in_tab' ], 20, 1 ); // Priority 20 to appear after core fields

		// Hook into the save action
		add_action( 'save_post_simple-pay', [ $this, 'save_style_settings' ], 10, 2 );

		// Hook into the enqueue action for scripts/styles
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
	}

	/**
	 * Enqueue admin scripts and styles (like the color picker).
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {
		global $post_type;

		// Only load on the simple-pay post type edit screen.
		if ( 'simple-pay' === $post_type && ( 'post.php' === $hook_suffix || 'post-new.php' === $hook_suffix ) ) {
			// Enqueue WordPress color picker scripts and styles.
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );

			// Inline script is simpler for just the color picker init
			wp_add_inline_script( 'wp-color-picker', 'jQuery(document).ready(function($){$(".ajl-color-picker").wpColorPicker();});' );

			// Future: Enqueue custom CSS for the settings layout if needed
			// wp_enqueue_style( 'ajl-wpsps-admin-css', AJL_WPSPS_URL . 'assets/css/ajl-admin.css', [], AJL_WPSPS_VERSION );
		}
	}

	/**
	 * Renders the style settings within the WP Simple Pay 'General' tab.
	 *
	 * @param int $post_id The post ID.
	 */
	public function render_style_settings_in_tab( $post_id ) {

		// Check if the form type is on-site (embedded or overlay)
		$display_type = get_post_meta( $post_id, '_form_display_type', true );
		if ( ! in_array( $display_type, [ 'embedded', 'overlay' ], true ) ) {
			// Don't show styling options for off-site forms. Add a message.
			echo '<hr><p><i>' . esc_html__( 'WP Simple Pay Styles: Styling is only available for On-Site (Embedded/Overlay) forms.', 'ajl-wp-simple-pay-styles' ) . '</i></p>';
			return;
		}

		// Add a section heading
		echo '<hr><h3>' . esc_html__( 'Form Styles (WP Simple Pay Styles)', 'ajl-wp-simple-pay-styles' ) . '</h3>';

		// Add a nonce field for security. Must be inside the form.
		wp_nonce_field( 'ajl_wpsps_save_styles', 'ajl_wpsps_styles_nonce' );

		$style_keys = AJL_Settings::get_style_keys();

		echo '<table class="form-table">'; // Using form-table for standard WordPress styling

		foreach ( $style_keys as $key ) {
			$value = AJL_Settings::get_setting( $post_id, $key );
			$label = ucwords( str_replace( '_', ' ', $key ) ); // Simple label generation
			$label = ( $key === 'background_color' ) ? 'Input Background Color' : $label; // Clarify Input BG
			$label = ( $key === 'form_container_background_color' ) ? 'Form Background Color' : $label; // Better Label

			echo '<tr>';
			echo '<th scope="row"><label for="ajl_wpsps_' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th>';
			echo '<td>';

			// Render different field types based on the key
			switch ( $key ) {
				case 'primary_color':
				case 'background_color': // Input background
				case 'form_container_background_color': // Form background
				case 'text_color':
				case 'button_background_color':
				case 'button_text_color':
				case 'button_hover_background_color':
					printf(
						'<input type="text" id="ajl_wpsps_%1$s" name="ajl_wpsps[%1$s]" value="%2$s" class="ajl-color-picker" data-default-color="%2$s" />',
						esc_attr( $key ),
						esc_attr( $value )
					);
					break;

				case 'border_radius':
				case 'label_font_size':
				case 'input_font_size':
					printf(
						'<input type="number" id="ajl_wpsps_%1$s" name="ajl_wpsps[%1$s]" value="%2$s" class="small-text" min="0" step="1" /> px',
						esc_attr( $key ),
						esc_attr( $value )
					);
					break;

				case 'label_font_weight':
					$options = [
						''       => __( 'Theme Default', 'ajl-wp-simple-pay-styles' ),
						'normal' => __( 'Normal', 'ajl-wp-simple-pay-styles' ),
						'bold'   => __( 'Bold', 'ajl-wp-simple-pay-styles' ),
						'100' => '100', '200' => '200', '300' => '300', '400' => '400', '500' => '500', '600' => '600', '700' => '700', '800' => '800', '900' => '900'
					];
					printf( '<select id="ajl_wpsps_%1$s" name="ajl_wpsps[%1$s]">', esc_attr( $key ) );
					foreach ( $options as $val => $label_text ) {
						printf(
							'<option value="%s" %s>%s</option>',
							esc_attr( $val ),
							selected( $value, $val, false ),
							esc_html( $label_text )
						);
					}
					printf( '</select>' );
					break;

				default:
					// Basic text input as fallback
					printf(
						'<input type="text" id="ajl_wpsps_%1$s" name="ajl_wpsps[%1$s]" value="%2$s" class="regular-text" />',
						esc_attr( $key ),
						esc_attr( $value )
					);
			}

			echo '</td>';
			echo '</tr>';
		}

		echo '</table>';
	}


	/**
	 * Saves the style settings data when the post is saved.
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post    The post object.
	 */
	public function save_style_settings( $post_id, $post ) {
		// Check if our nonce is set.
		if ( ! isset( $_POST['ajl_wpsps_styles_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['ajl_wpsps_styles_nonce'], 'ajl_wpsps_save_styles' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check if the post type is correct.
		if ( 'simple-pay' !== $post->post_type ) {
			return;
		}

		// Check if styling fields should be saved (i.e., form is on-site)
		$display_type = get_post_meta( $post_id, '_form_display_type', true );
		$is_on_site = in_array( $display_type, [ 'embedded', 'overlay' ], true );

		$settings_data = isset( $_POST['ajl_wpsps'] ) && is_array( $_POST['ajl_wpsps'] ) ? $_POST['ajl_wpsps'] : [];
		$style_keys    = AJL_Settings::get_style_keys();

		foreach ( $style_keys as $key ) {
			// Only save/delete if the form is on-site OR if the setting currently exists (to allow deletion when switching form type)
			if ( $is_on_site || AJL_Settings::setting_exists( $post_id, $key ) ) {

				if ( $is_on_site && isset( $settings_data[ $key ] ) ) {
					// Sanitize the data before saving
					$sanitized_value = '';
					switch ( $key ) {
						case 'primary_color':
						case 'background_color': // Input background
						case 'form_container_background_color': // Form background
						case 'text_color':
						case 'button_background_color':
						case 'button_text_color':
						case 'button_hover_background_color':
							$sanitized_value = sanitize_hex_color( $settings_data[ $key ] );
							break;
						case 'border_radius':
						case 'label_font_size':
						case 'input_font_size':
							$sanitized_value = absint( $settings_data[ $key ] );
							break;
						case 'label_font_weight':
							$allowed_weights = ['','normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900'];
							if ( in_array( $settings_data[ $key ], $allowed_weights, true ) ) {
								$sanitized_value = $settings_data[ $key ];
							} else {
								$sanitized_value = ''; // Default if invalid
							}
							break;
						default:
							$sanitized_value = sanitize_text_field( $settings_data[ $key ] );
					}
					AJL_Settings::save_setting( $post_id, $key, $sanitized_value );

				} else {
					// Delete setting if form is not on-site or setting is not submitted for an on-site form
					AJL_Settings::delete_setting( $post_id, $key );
				}
			}
		}
	}
} 