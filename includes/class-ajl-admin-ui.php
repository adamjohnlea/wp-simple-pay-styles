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
 *
 * Handles the admin user interface for WP Simple Pay Styles.
 * Adds style settings to the WP Simple Pay form editor and handles
 * saving and retrieving style settings.
 *
 * @since 1.0.0
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
  * Loads necessary CSS and JavaScript files for the admin interface,
  * but only on the WP Simple Pay form edit screen.
  *
  * @since 1.0.0
  *
  * @param string $hook_suffix The current admin page.
  * @return void
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
					'selectText' => __( 'Select', 'ajl-wp-simple-pay-styles' ),
					'selectedText' => __( 'Selected', 'ajl-wp-simple-pay-styles' ),
					'themeAppliedMessage' => __( 'Theme "%s" has been applied! Save the form to keep these changes.', 'ajl-wp-simple-pay-styles' ),
				]
			);
		}
	}

 /**
  * Check if this is a new form without any saved style settings
  *
  * Determines if a form has any style settings saved yet.
  * Used to apply default styles to new forms.
  *
  * @since 1.0.0
  * @access private
  *
  * @param int $post_id The post ID to check
  * @return bool True if this is a new form, false otherwise
  */
	private function is_new_form( $post_id ) {
		// Check if any style settings exist for this form
		foreach ( AJL_Settings::get_style_keys() as $key ) {
			if ( AJL_Settings::setting_exists( $post_id, $key ) ) {
				return false;
			}
		}

		return true;
	}

 /**
  * Renders the style settings within the WP Simple Pay 'General' tab.
  *
  * Creates the tabbed interface for style settings in the form editor.
  * Includes tabs for themes, colors, typography, layout, and buttons.
  *
  * @since 1.0.0
  *
  * @param int $post_id The post ID.
  * @return void
  */
	public function render_style_settings_in_tab( $post_id ) {

		// Check if the form type is on-site (embedded or overlay)
		$display_type = get_post_meta( $post_id, '_form_display_type', true );
		if ( ! in_array( $display_type, [ 'embedded', 'overlay' ], true ) ) {
			// Don't show styling options for off-site forms. Add a message.
			echo '<hr><p><i>' . esc_html__( 'WP Simple Pay Styles: Styling is only available for On-Site (Embedded/Overlay) forms.', 'ajl-wp-simple-pay-styles' ) . '</i></p>';
			return;
		}

		// Determine if this is a new form
		$is_new_form = $this->is_new_form( $post_id );

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
				<button type="button" class="ajl-wpsps-tab-button active" data-tab="themes">
					<span class="dashicons dashicons-admin-appearance"></span>
					<?php esc_html_e( 'Themes', 'ajl-wp-simple-pay-styles' ); ?>
				</button>
				<button type="button" class="ajl-wpsps-tab-button" data-tab="colors">
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
				<!-- Themes Tab -->
				<div class="ajl-wpsps-tab-panel active" data-tab-content="themes">
					<div class="ajl-wpsps-theme-grid">
						<?php
						// Get the current selected theme
						$current_theme = AJL_Settings::get_setting( $post_id, 'selected_theme', 'default' );

						// Define theme presets
						$themes = [
							'default' => [
								'name' => __( 'Default', 'ajl-wp-simple-pay-styles' ),
								'colors' => [
									'primary' => '#0f8569',
									'secondary' => '#0e7c62',
									'text' => '#32325d',
									'background' => '#ffffff',
								],
								'description' => __( 'WP Simple Pay\'s default styling', 'ajl-wp-simple-pay-styles' ),
							],
							'midnight' => [
								'name' => __( 'Midnight', 'ajl-wp-simple-pay-styles' ),
								'colors' => [
									'primary' => '#2c3e50',
									'secondary' => '#1a252f',
									'text' => '#ffffff',
									'background' => '#34495e',
								],
								'description' => __( 'Dark theme with cool blue tones', 'ajl-wp-simple-pay-styles' ),
							],
							'sunset' => [
								'name' => __( 'Sunset', 'ajl-wp-simple-pay-styles' ),
								'colors' => [
									'primary' => '#e74c3c',
									'secondary' => '#c0392b',
									'text' => '#2c3e50',
									'background' => '#ecf0f1',
								],
								'description' => __( 'Warm red accents with light background', 'ajl-wp-simple-pay-styles' ),
							],
							'forest' => [
								'name' => __( 'Forest', 'ajl-wp-simple-pay-styles' ),
								'colors' => [
									'primary' => '#27ae60',
									'secondary' => '#219955',
									'text' => '#2c3e50',
									'background' => '#f9f9f9',
								],
								'description' => __( 'Fresh green theme with clean background', 'ajl-wp-simple-pay-styles' ),
							],
							'ocean' => [
								'name' => __( 'Ocean', 'ajl-wp-simple-pay-styles' ),
								'colors' => [
									'primary' => '#3498db',
									'secondary' => '#2980b9',
									'text' => '#2c3e50',
									'background' => '#ecf0f1',
								],
								'description' => __( 'Calming blue palette', 'ajl-wp-simple-pay-styles' ),
							],
							'lavender' => [
								'name' => __( 'Lavender', 'ajl-wp-simple-pay-styles' ),
								'colors' => [
									'primary' => '#9b59b6',
									'secondary' => '#8e44ad',
									'text' => '#2c3e50',
									'background' => '#f5f5f5',
								],
								'description' => __( 'Elegant purple theme', 'ajl-wp-simple-pay-styles' ),
							],
							'monochrome' => [
								'name' => __( 'Monochrome', 'ajl-wp-simple-pay-styles' ),
								'colors' => [
									'primary' => '#333333',
									'secondary' => '#555555',
									'text' => '#333333',
									'background' => '#ffffff',
								],
								'description' => __( 'Simple black and white theme', 'ajl-wp-simple-pay-styles' ),
							],
							'sunshine' => [
								'name' => __( 'Sunshine', 'ajl-wp-simple-pay-styles' ),
								'colors' => [
									'primary' => '#f1c40f',
									'secondary' => '#f39c12',
									'text' => '#34495e',
									'background' => '#ffffff',
								],
								'description' => __( 'Bright and cheerful yellow accents', 'ajl-wp-simple-pay-styles' ),
							],
							'coral' => [
								'name' => __( 'Coral', 'ajl-wp-simple-pay-styles' ),
								'colors' => [
									'primary' => '#e67e22',
									'secondary' => '#d35400',
									'text' => '#2c3e50',
									'background' => '#f9f9f9',
								],
								'description' => __( 'Warm orange palette', 'ajl-wp-simple-pay-styles' ),
							],
							'minimal' => [
								'name' => __( 'Minimal', 'ajl-wp-simple-pay-styles' ),
								'colors' => [
									'primary' => '#bdc3c7',
									'secondary' => '#95a5a6',
									'text' => '#2c3e50',
									'background' => '#ffffff',
								],
								'description' => __( 'Clean, minimalist design', 'ajl-wp-simple-pay-styles' ),
							],
						];

						// Store themes in a hidden field for JavaScript access
						echo '<input type="hidden" id="ajl_wpsps_theme_presets" value="' . esc_attr( json_encode( $themes ) ) . '">';

						// Output theme selection cards
						foreach ( $themes as $theme_id => $theme ) {
							$is_selected = $current_theme === $theme_id;
							?>
							<div class="ajl-wpsps-theme-card <?php echo $is_selected ? 'selected' : ''; ?>" data-theme-id="<?php echo esc_attr( $theme_id ); ?>">
								<div class="ajl-wpsps-theme-preview">
									<div class="ajl-wpsps-theme-colors">
										<span class="ajl-wpsps-theme-color primary" style="background-color: <?php echo esc_attr( $theme['colors']['primary'] ); ?>"></span>
										<span class="ajl-wpsps-theme-color secondary" style="background-color: <?php echo esc_attr( $theme['colors']['secondary'] ); ?>"></span>
										<span class="ajl-wpsps-theme-color text" style="background-color: <?php echo esc_attr( $theme['colors']['text'] ); ?>"></span>
										<span class="ajl-wpsps-theme-color background" style="background-color: <?php echo esc_attr( $theme['colors']['background'] ); ?>"></span>
									</div>
									<div class="ajl-wpsps-theme-button" style="background-color: <?php echo esc_attr( $theme['colors']['primary'] ); ?>; color: #ffffff;">
										<?php esc_html_e( 'Button', 'ajl-wp-simple-pay-styles' ); ?>
									</div>
								</div>
								<div class="ajl-wpsps-theme-info">
									<h4><?php echo esc_html( $theme['name'] ); ?></h4>
									<p><?php echo esc_html( $theme['description'] ); ?></p>
									<input 
										type="radio" 
										name="ajl_wpsps[selected_theme]" 
										value="<?php echo esc_attr( $theme_id ); ?>" 
										id="ajl_wpsps_theme_<?php echo esc_attr( $theme_id ); ?>"
										<?php checked( $current_theme, $theme_id ); ?>
										class="ajl-wpsps-theme-radio"
									>
									<label for="ajl_wpsps_theme_<?php echo esc_attr( $theme_id ); ?>" class="ajl-wpsps-theme-select button button-primary">
										<?php echo $is_selected ? esc_html__( 'Selected', 'ajl-wp-simple-pay-styles' ) : esc_html__( 'Select', 'ajl-wp-simple-pay-styles' ); ?>
									</label>
								</div>
							</div>
							<?php
						}
						?>
					</div>
					<div class="ajl-wpsps-theme-instructions">
						<p><?php esc_html_e( 'Select a theme to instantly apply a complete set of coordinated styles. You can customize individual settings in the other tabs after applying a theme.', 'ajl-wp-simple-pay-styles' ); ?></p>
					</div>
				</div>

				<!-- Colors Tab -->
				<div class="ajl-wpsps-tab-panel" data-tab-content="colors">
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
								<?php esc_html_e( 'Background color of input fields, selects, and dropdowns', 'ajl-wp-simple-pay-styles' ); ?>
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
								<?php esc_html_e( 'Color for labels, inputs, dropdown text and options', 'ajl-wp-simple-pay-styles' ); ?>
							</p>
						</div>

						<!-- Label Text Color (New) -->
						<div class="ajl-wpsps-form-field">
							<label for="ajl_wpsps_label_text_color">
								<?php esc_html_e( 'Label Text Color', 'ajl-wp-simple-pay-styles' ); ?>
							</label>
							<div class="ajl-wpsps-color-preview-wrap">
								<input 
									type="text" 
									id="ajl_wpsps_label_text_color" 
									name="ajl_wpsps[label_text_color]" 
									value="<?php echo esc_attr( AJL_Settings::get_setting( $post_id, 'label_text_color' ) ); ?>" 
									class="ajl-color-picker"
								/>
								<div class="ajl-wpsps-color-preview-label"><?php esc_html_e( 'Labels', 'ajl-wp-simple-pay-styles' ); ?></div>
							</div>
							<p class="ajl-wpsps-field-description">
								<?php esc_html_e( 'Color specifically for field labels (overrides Text Color for labels)', 'ajl-wp-simple-pay-styles' ); ?>
							</p>
						</div>

						<!-- Input Text Color (New) -->
						<div class="ajl-wpsps-form-field">
							<label for="ajl_wpsps_input_text_color">
								<?php esc_html_e( 'Input Text Color', 'ajl-wp-simple-pay-styles' ); ?>
							</label>
							<div class="ajl-wpsps-color-preview-wrap">
								<input 
									type="text" 
									id="ajl_wpsps_input_text_color" 
									name="ajl_wpsps[input_text_color]" 
									value="<?php echo esc_attr( AJL_Settings::get_setting( $post_id, 'input_text_color' ) ); ?>" 
									class="ajl-color-picker"
								/>
								<div class="ajl-wpsps-color-preview-label"><?php esc_html_e( 'Input Text', 'ajl-wp-simple-pay-styles' ); ?></div>
							</div>
							<p class="ajl-wpsps-field-description">
								<?php esc_html_e( 'Color specifically for text in input fields (overrides Text Color for inputs)', 'ajl-wp-simple-pay-styles' ); ?>
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
								<?php esc_html_e( 'Used for focus states, highlights, and links', 'ajl-wp-simple-pay-styles' ); ?>
							</p>
						</div>

						<!-- Border Color (New) -->
						<div class="ajl-wpsps-form-field">
							<label for="ajl_wpsps_border_color">
								<?php esc_html_e( 'Border Color', 'ajl-wp-simple-pay-styles' ); ?>
							</label>
							<div class="ajl-wpsps-color-preview-wrap">
								<input 
									type="text" 
									id="ajl_wpsps_border_color" 
									name="ajl_wpsps[border_color]" 
									value="<?php echo esc_attr( AJL_Settings::get_setting( $post_id, 'border_color' ) ); ?>" 
									class="ajl-color-picker"
									data-alpha-enabled="true"
								/>
								<div class="ajl-wpsps-color-preview-label"><?php esc_html_e( 'Border', 'ajl-wp-simple-pay-styles' ); ?></div>
							</div>
							<p class="ajl-wpsps-field-description">
								<?php esc_html_e( 'Border color for input fields and elements', 'ajl-wp-simple-pay-styles' ); ?>
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
									value="<?php echo esc_attr( $is_new_form ? 3 : AJL_Settings::get_setting( $post_id, 'border_radius', 0 ) ); ?>" 
									step="1"
								/>
								<span class="ajl-wpsps-unit">px</span>
							</div>
							<div class="ajl-wpsps-radius-preview">
								<div class="ajl-wpsps-radius-box" style="border-radius: <?php echo esc_attr( $is_new_form ? 3 : AJL_Settings::get_setting( $post_id, 'border_radius', 0 ) ); ?>px;"></div>
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
								<div class="ajl-wpsps-button-preview" style="background-color: <?php echo esc_attr( $is_new_form ? '#0f8569' : AJL_Settings::get_setting( $post_id, 'button_background_color', '#0f8569' ) ); ?>; color: <?php echo esc_attr( $is_new_form ? '#ffffff' : AJL_Settings::get_setting( $post_id, 'button_text_color', '#ffffff' ) ); ?>">
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
								<div class="ajl-wpsps-button-preview ajl-wpsps-button-hover" style="background-color: <?php echo esc_attr( $is_new_form ? '#0e7c62' : AJL_Settings::get_setting( $post_id, 'button_hover_background_color', '#0e7c62' ) ); ?>; color: <?php echo esc_attr( $is_new_form ? '#ffffff' : AJL_Settings::get_setting( $post_id, 'button_text_color', '#ffffff' ) ); ?>">
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
  * Handles validation, sanitization, and saving of style settings
  * when a WP Simple Pay form is saved.
  *
  * @since 1.0.0
  *
  * @param int     $post_id The post ID.
  * @param WP_Post $post    The post object.
  * @return void
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

 /**
  * Render style settings tab template.
  *
  * Creates the HTML template for the style settings tab.
  * This is a legacy method and may not be used in current versions.
  *
  * @since 1.0.0
  *
  * @return string The HTML template.
  */
	public function get_style_settings_tab_template() {
		ob_start();
		?>
		<div class="simpay-panel-setting-row">
			<h3>
				<?php esc_html_e( 'Form Styles', 'wp-simple-pay-styles' ); ?>
			</h3>
		</div>
		<div class="simpay-panel-section" id="ajl-form-styles-tabs">
			<ul class="wp-tab-bar">
				<li class="wp-tab-active">
					<a href="#colors-tab"><?php esc_html_e( 'Colors', 'wp-simple-pay-styles' ); ?></a>
				</li>
				<li>
					<a href="#fonts-tab"><?php esc_html_e( 'Typography', 'wp-simple-pay-styles' ); ?></a>
				</li>
				<li>
					<a href="#buttons-tab"><?php esc_html_e( 'Button Styles', 'wp-simple-pay-styles' ); ?></a>
				</li>
			</ul>
			<div id="colors-tab" class="wp-tab-panel">
				<div class="simpay-panel-setting">
					<label for="ajl_background_color"><?php esc_html_e( 'Form Background', 'wp-simple-pay-styles' ); ?></label>
					<input type="text" id="ajl_background_color" name="_simpay_custom_form[ajl_background_color]" class="ajl-color-picker" value="<?php echo esc_attr( AJL_Settings::get_setting( 'background_color', 'form-styles' ) ); ?>" data-default-color="#ffffff" data-alpha="true" />
				</div>
				<div class="simpay-panel-setting">
					<label for="ajl_text_color"><?php esc_html_e( 'Text Color', 'wp-simple-pay-styles' ); ?></label>
					<input type="text" id="ajl_text_color" name="_simpay_custom_form[ajl_text_color]" class="ajl-color-picker" value="<?php echo esc_attr( AJL_Settings::get_setting( 'text_color', 'form-styles' ) ); ?>" data-default-color="#000000" />
				</div>
				<div class="simpay-panel-setting">
					<label for="ajl_label_text_color"><?php esc_html_e( 'Label Text Color', 'wp-simple-pay-styles' ); ?></label>
					<input type="text" id="ajl_label_text_color" name="_simpay_custom_form[ajl_label_text_color]" class="ajl-color-picker" value="<?php echo esc_attr( AJL_Settings::get_setting( 'label_text_color', 'form-styles' ) ); ?>" data-default-color="#000000" />
				</div>
				<div class="simpay-panel-setting">
					<label for="ajl_input_text_color"><?php esc_html_e( 'Input Text Color', 'wp-simple-pay-styles' ); ?></label>
					<input type="text" id="ajl_input_text_color" name="_simpay_custom_form[ajl_input_text_color]" class="ajl-color-picker" value="<?php echo esc_attr( AJL_Settings::get_setting( 'input_text_color', 'form-styles' ) ); ?>" data-default-color="#32325d" />
				</div>
				<div class="simpay-panel-setting">
					<label for="ajl_border_color"><?php esc_html_e( 'Border Color', 'wp-simple-pay-styles' ); ?></label>
					<input type="text" id="ajl_border_color" name="_simpay_custom_form[ajl_border_color]" class="ajl-color-picker" value="<?php echo esc_attr( AJL_Settings::get_setting( 'border_color', 'form-styles' ) ); ?>" data-default-color="#e6e6e6" />
				</div>
				<div class="simpay-panel-setting">
					<label for="ajl_primary_color"><?php esc_html_e( 'Primary Color', 'wp-simple-pay-styles' ); ?></label>
					<input type="text" id="ajl_primary_color" name="_simpay_custom_form[ajl_primary_color]" class="ajl-color-picker" value="<?php echo esc_attr( AJL_Settings::get_setting( 'primary_color', 'form-styles' ) ); ?>" data-default-color="#0f8569" />
				</div>
			</div>
			<!-- ... Remaining code unchanged ... -->
		<?php
		return ob_get_clean();
	}
} 
