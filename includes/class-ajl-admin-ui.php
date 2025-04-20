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
			
			// Enqueue color picker alpha addon if available
			if ( ! wp_script_is( 'wp-color-picker-alpha', 'registered' ) ) {
				wp_register_script(
					'wp-color-picker-alpha',
					AJL_WPSPS_URL . 'assets/js/wp-color-picker-alpha.min.js',
					array( 'wp-color-picker' ),
					'3.0.0',
					true
				);
			}
			wp_enqueue_script( 'wp-color-picker-alpha' );
			
			// Enqueue custom admin styles and scripts
			wp_enqueue_style( 
				'ajl-wpsps-admin-css', 
				AJL_WPSPS_URL . 'assets/css/ajl-admin.css', 
				[], 
				AJL_WPSPS_VERSION 
			);
			
			wp_enqueue_script( 
				'ajl-wpsps-admin-js', 
				AJL_WPSPS_URL . 'assets/js/ajl-admin.js', 
				['jquery', 'wp-color-picker', 'wp-color-picker-alpha'], 
				AJL_WPSPS_VERSION, 
				true 
			);

			// Pass localized data to the JS
			wp_localize_script(
				'ajl-wpsps-admin-js',
				'ajlWpspsData',
				[
					'resetConfirmMessage' => __( 'Are you sure you want to reset all style settings to default values?', 'ajl-wp-simple-pay-styles' ),
				]
			);
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
		
		// Start the modern tabbed interface
		?>
		<div class="ajl-wpsps-tabs-container">
			<!-- Tabs Navigation -->
			<div class="ajl-wpsps-tabs-nav">
				<button type="button" class="ajl-wpsps-tab-button active" data-tab="colors">
					<span class="dashicons dashicons-art"></span>
					<?php esc_html_e( 'Colors', 'ajl-wp-simple-pay-styles' ); ?>
				</button>
				<button type="button" class="ajl-wpsps-tab-button" data-tab="typography">
					<span class="dashicons dashicons-editor-textcolor"></span>
					<?php esc_html_e( 'Typography', 'ajl-wp-simple-pay-styles' ); ?>
				</button>
				<button type="button" class="ajl-wpsps-tab-button" data-tab="layout">
					<span class="dashicons dashicons-layout"></span>
					<?php esc_html_e( 'Layout', 'ajl-wp-simple-pay-styles' ); ?>
				</button>
				<button type="button" class="ajl-wpsps-tab-button" data-tab="buttons">
					<span class="dashicons dashicons-button"></span>
					<?php esc_html_e( 'Buttons', 'ajl-wp-simple-pay-styles' ); ?>
				</button>
			</div>

			<!-- Tabs Content -->
			<div class="ajl-wpsps-tabs-content">
				<!-- Colors Tab -->
				<div class="ajl-wpsps-tab-panel active" data-tab-content="colors">
					<div class="ajl-wpsps-form-grid">
						<!-- Form Container Background Color -->
						<div class="ajl-wpsps-form-field">
							<label for="ajl_wpsps_form_container_background_color">
								<?php esc_html_e( 'Form Background', 'ajl-wp-simple-pay-styles' ); ?>
							</label>
							<div class="ajl-wpsps-color-preview-wrap">
								<input 
									type="text" 
									id="ajl_wpsps_form_container_background_color" 
									name="ajl_wpsps[form_container_background_color]" 
									value="<?php echo esc_attr( AJL_Settings::get_setting( $post_id, 'form_container_background_color' ) ); ?>" 
									class="ajl-color-picker" 
									data-alpha-enabled="true"
								/>
								<div class="ajl-wpsps-color-preview-label"><?php esc_html_e( 'Form', 'ajl-wp-simple-pay-styles' ); ?></div>
							</div>
							<p class="ajl-wpsps-field-description">
								<?php esc_html_e( 'Background color of the entire form container', 'ajl-wp-simple-pay-styles' ); ?>
							</p>
						</div>

						<!-- Input Background Color -->
						<div class="ajl-wpsps-form-field">
							<label for="ajl_wpsps_background_color">
								<?php esc_html_e( 'Input Background', 'ajl-wp-simple-pay-styles' ); ?>
							</label>
							<div class="ajl-wpsps-color-preview-wrap">
								<input 
									type="text" 
									id="ajl_wpsps_background_color" 
									name="ajl_wpsps[background_color]" 
									value="<?php echo esc_attr( AJL_Settings::get_setting( $post_id, 'background_color' ) ); ?>" 
									class="ajl-color-picker" 
									data-alpha-enabled="true"
								/>
								<div class="ajl-wpsps-color-preview-label"><?php esc_html_e( 'Input', 'ajl-wp-simple-pay-styles' ); ?></div>
							</div>
							<p class="ajl-wpsps-field-description">
								<?php esc_html_e( 'Background color of input fields', 'ajl-wp-simple-pay-styles' ); ?>
							</p>
						</div>

						<!-- Text Color -->
						<div class="ajl-wpsps-form-field">
							<label for="ajl_wpsps_text_color">
								<?php esc_html_e( 'Text Color', 'ajl-wp-simple-pay-styles' ); ?>
							</label>
							<div class="ajl-wpsps-color-preview-wrap">
								<input 
									type="text" 
									id="ajl_wpsps_text_color" 
									name="ajl_wpsps[text_color]" 
									value="<?php echo esc_attr( AJL_Settings::get_setting( $post_id, 'text_color' ) ); ?>" 
									class="ajl-color-picker"
								/>
								<div class="ajl-wpsps-color-preview-label"><?php esc_html_e( 'Text', 'ajl-wp-simple-pay-styles' ); ?></div>
							</div>
							<p class="ajl-wpsps-field-description">
								<?php esc_html_e( 'Color for labels and input text', 'ajl-wp-simple-pay-styles' ); ?>
							</p>
						</div>

						<!-- Primary Color (Accent) -->
						<div class="ajl-wpsps-form-field">
							<label for="ajl_wpsps_primary_color">
								<?php esc_html_e( 'Primary (Accent) Color', 'ajl-wp-simple-pay-styles' ); ?>
							</label>
							<div class="ajl-wpsps-color-preview-wrap">
								<input 
									type="text" 
									id="ajl_wpsps_primary_color" 
									name="ajl_wpsps[primary_color]" 
									value="<?php echo esc_attr( AJL_Settings::get_setting( $post_id, 'primary_color' ) ); ?>" 
									class="ajl-color-picker"
								/>
								<div class="ajl-wpsps-color-preview-label"><?php esc_html_e( 'Accent', 'ajl-wp-simple-pay-styles' ); ?></div>
							</div>
							<p class="ajl-wpsps-field-description">
								<?php esc_html_e( 'Used for focus states and highlights', 'ajl-wp-simple-pay-styles' ); ?>
							</p>
						</div>
					</div>
				</div>

				<!-- Typography Tab -->
				<div class="ajl-wpsps-tab-panel" data-tab-content="typography">
					<div class="ajl-wpsps-form-grid">
						<!-- Label Font Size -->
						<div class="ajl-wpsps-form-field">
							<label for="ajl_wpsps_label_font_size">
								<?php esc_html_e( 'Label Font Size', 'ajl-wp-simple-pay-styles' ); ?>
							</label>
							<div class="ajl-wpsps-input-with-unit">
								<input 
									type="number" 
									id="ajl_wpsps_label_font_size" 
									name="ajl_wpsps[label_font_size]" 
									value="<?php echo esc_attr( AJL_Settings::get_setting( $post_id, 'label_font_size', '14' ) ); ?>" 
									step="1"
								/>
								<span class="ajl-wpsps-unit">px</span>
							</div>
							<p class="ajl-wpsps-field-description">
								<?php esc_html_e( 'Size of form field labels', 'ajl-wp-simple-pay-styles' ); ?>
							</p>
						</div>

						<!-- Label Font Weight -->
						<div class="ajl-wpsps-form-field">
							<label for="ajl_wpsps_label_font_weight">
								<?php esc_html_e( 'Label Font Weight', 'ajl-wp-simple-pay-styles' ); ?>
							</label>
							<select id="ajl_wpsps_label_font_weight" name="ajl_wpsps[label_font_weight]" class="ajl-wpsps-select">
								<?php
								$current_weight = AJL_Settings::get_setting( $post_id, 'label_font_weight' );
								$weight_options = [
									'' => __( 'Theme Default', 'ajl-wp-simple-pay-styles' ),
									'normal' => __( 'Normal', 'ajl-wp-simple-pay-styles' ),
									'bold' => __( 'Bold', 'ajl-wp-simple-pay-styles' ),
									'100' => '100 (Thin)',
									'200' => '200 (Extra Light)',
									'300' => '300 (Light)',
									'400' => '400 (Normal)',
									'500' => '500 (Medium)',
									'600' => '600 (Semi Bold)',
									'700' => '700 (Bold)',
									'800' => '800 (Extra Bold)',
									'900' => '900 (Black)',
								];
								
								foreach ( $weight_options as $value => $label ) {
									printf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $value ),
										selected( $current_weight, $value, false ),
										esc_html( $label )
									);
								}
								?>
							</select>
							<p class="ajl-wpsps-field-description">
								<?php esc_html_e( 'Font weight for form labels', 'ajl-wp-simple-pay-styles' ); ?>
							</p>
						</div>

						<!-- Input Font Size -->
						<div class="ajl-wpsps-form-field">
							<label for="ajl_wpsps_input_font_size">
								<?php esc_html_e( 'Input Font Size', 'ajl-wp-simple-pay-styles' ); ?>
							</label>
							<div class="ajl-wpsps-input-with-unit">
								<input 
									type="number" 
									id="ajl_wpsps_input_font_size" 
									name="ajl_wpsps[input_font_size]" 
									value="<?php echo esc_attr( AJL_Settings::get_setting( $post_id, 'input_font_size', '16' ) ); ?>" 
									step="1"
								/>
								<span class="ajl-wpsps-unit">px</span>
							</div>
							<p class="ajl-wpsps-field-description">
								<?php esc_html_e( 'Size of text in form inputs', 'ajl-wp-simple-pay-styles' ); ?>
							</p>
						</div>
					</div>
				</div>

				<!-- Layout Tab -->
				<div class="ajl-wpsps-tab-panel" data-tab-content="layout">
					<div class="ajl-wpsps-form-grid">
						<!-- Border Radius -->
						<div class="ajl-wpsps-form-field">
							<label for="ajl_wpsps_border_radius">
								<?php esc_html_e( 'Border Radius', 'ajl-wp-simple-pay-styles' ); ?>
							</label>
							<div class="ajl-wpsps-input-with-unit">
								<input 
									type="number" 
									id="ajl_wpsps_border_radius" 
									name="ajl_wpsps[border_radius]" 
									value="<?php echo esc_attr( AJL_Settings::get_setting( $post_id, 'border_radius', 0 ) ); ?>" 
									step="1"
								/>
								<span class="ajl-wpsps-unit">px</span>
							</div>
							<div class="ajl-wpsps-radius-preview">
								<div class="ajl-wpsps-radius-box" style="border-radius: <?php echo esc_attr( AJL_Settings::get_setting( $post_id, 'border_radius', 0 ) ); ?>px;"></div>
							</div>
							<p class="ajl-wpsps-field-description">
								<?php esc_html_e( 'Rounded corners for inputs and buttons', 'ajl-wp-simple-pay-styles' ); ?>
							</p>
						</div>
					</div>
				</div>

				<!-- Buttons Tab -->
				<div class="ajl-wpsps-tab-panel" data-tab-content="buttons">
					<div class="ajl-wpsps-form-grid">
						<!-- Button Background Color -->
						<div class="ajl-wpsps-form-field">
							<label for="ajl_wpsps_button_background_color">
								<?php esc_html_e( 'Button Background', 'ajl-wp-simple-pay-styles' ); ?>
							</label>
							<div class="ajl-wpsps-color-preview-wrap">
								<input 
									type="text" 
									id="ajl_wpsps_button_background_color" 
									name="ajl_wpsps[button_background_color]" 
									value="<?php echo esc_attr( AJL_Settings::get_setting( $post_id, 'button_background_color' ) ); ?>" 
									class="ajl-color-picker"
								/>
								<div class="ajl-wpsps-button-preview" style="background-color: <?php echo esc_attr( AJL_Settings::get_setting( $post_id, 'button_background_color', '#0f8569' ) ); ?>; color: <?php echo esc_attr( AJL_Settings::get_setting( $post_id, 'button_text_color', '#ffffff' ) ); ?>">
									<?php esc_html_e( 'Button Preview', 'ajl-wp-simple-pay-styles' ); ?>
								</div>
							</div>
						</div>

						<!-- Button Text Color -->
						<div class="ajl-wpsps-form-field">
							<label for="ajl_wpsps_button_text_color">
								<?php esc_html_e( 'Button Text Color', 'ajl-wp-simple-pay-styles' ); ?>
							</label>
							<div class="ajl-wpsps-color-preview-wrap">
								<input 
									type="text" 
									id="ajl_wpsps_button_text_color" 
									name="ajl_wpsps[button_text_color]" 
									value="<?php echo esc_attr( AJL_Settings::get_setting( $post_id, 'button_text_color' ) ); ?>" 
									class="ajl-color-picker"
								/>
							</div>
						</div>

						<!-- Button Hover Background Color -->
						<div class="ajl-wpsps-form-field">
							<label for="ajl_wpsps_button_hover_background_color">
								<?php esc_html_e( 'Button Hover Color', 'ajl-wp-simple-pay-styles' ); ?>
							</label>
							<div class="ajl-wpsps-color-preview-wrap">
								<input 
									type="text" 
									id="ajl_wpsps_button_hover_background_color" 
									name="ajl_wpsps[button_hover_background_color]" 
									value="<?php echo esc_attr( AJL_Settings::get_setting( $post_id, 'button_hover_background_color' ) ); ?>" 
									class="ajl-color-picker"
								/>
								<div class="ajl-wpsps-button-preview ajl-wpsps-button-hover" style="background-color: <?php echo esc_attr( AJL_Settings::get_setting( $post_id, 'button_hover_background_color', '#0e7c62' ) ); ?>; color: <?php echo esc_attr( AJL_Settings::get_setting( $post_id, 'button_text_color', '#ffffff' ) ); ?>">
									<?php esc_html_e( 'Hover Preview', 'ajl-wp-simple-pay-styles' ); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Reset Button and Action Area -->
			<div class="ajl-wpsps-actions">
				<button type="button" id="ajl-wpsps-reset-styles" class="button button-secondary">
					<span class="dashicons dashicons-image-rotate"></span>
					<?php esc_html_e( 'Reset Styles', 'ajl-wp-simple-pay-styles' ); ?>
				</button>
				<p class="ajl-wpsps-reset-description">
					<?php esc_html_e( 'Resets all style settings to default. This action cannot be undone.', 'ajl-wp-simple-pay-styles' ); ?>
				</p>
			</div>
		</div>
		<?php
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

		// Check if reset action was triggered
		$is_reset = isset( $_POST['ajl_wpsps_reset'] ) && $_POST['ajl_wpsps_reset'] === 'true';
		
		if ( $is_reset ) {
			// Delete all style settings if reset was requested
			$style_keys = AJL_Settings::get_style_keys();
			foreach ( $style_keys as $key ) {
				AJL_Settings::delete_setting( $post_id, $key );
			}
			return;
		}

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
							// Allow rgba values (for transparent backgrounds)
							if ( empty( $sanitized_value ) && preg_match( '/rgba?\((\s*\d+\s*,\s*\d+\s*,\s*\d+\s*,?\s*[\d\.]*\s*)\)/', $settings_data[ $key ] ) ) {
								$sanitized_value = $settings_data[ $key ];
							}
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