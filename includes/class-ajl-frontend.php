<?php
/**
 * Handles frontend filtering and style application.
 *
 * @package AJL_WP_Simple_Pay_Styles
 */

namespace AJL_WP_Simple_Pay_Styles;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend handler class.
 */
class AJL_Frontend {

	/**
	 * The single instance of the class.
	 *
	 * @var AJL_Frontend
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Stores the IDs of forms rendered on the current page.
	 *
	 * @var array<int>
	 */
	private static $rendered_form_ids = [];

	/**
	 * Get the singleton instance.
	 *
	 * @return AJL_Frontend
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function hooks() {
		// Hook unconditionally to modify Elements config.
		add_filter( 'simpay_elements_config', [ $this, 'modify_elements_config' ], 10, 1 );

		// Hook to capture rendered form IDs (for inline CSS and for Elements config).
		add_action( 'simpay_form_before_form_top', [ $this, 'capture_rendered_form_id' ], 10, 1 ); // Only need form ID

		// Hook late to print inline CSS for non-Stripe elements.
		add_action( 'wp_print_footer_scripts', [ $this, 'print_late_frontend_styles' ], 100 ); // Using a late hook
	}

	/**
	 * Filters the Stripe Elements configuration array based on saved settings.
	 *
	 * @param array $config The original Elements configuration.
	 * @return array The modified Elements configuration.
	 */
	public function modify_elements_config( $config ) {
		// Try to get the relevant form ID from the rendered list.
        if ( empty( self::$rendered_form_ids ) ) {
            return $config;
        }
        $form_id = end( self::$rendered_form_ids );
        if ( ! $form_id ) {
             return $config;
        }

        // Check if styling is applicable for this form type
        $display_type = get_post_meta( $form_id, '_form_display_type', true );
        if ( ! in_array( $display_type, [ 'embedded', 'overlay' ], true ) ) {
            return $config;
        }

		// --- Apply styles --- 
		// Ensure appearance key exists
		if ( ! isset( $config['appearance'] ) ) {
			$config['appearance'] = [];
		}
		if ( ! isset( $config['appearance']['variables'] ) ) {
			$config['appearance']['variables'] = [];
		}
		if ( ! isset( $config['appearance']['rules'] ) ) {
			$config['appearance']['rules'] = [];
		}

		// Apply saved styles (Logic remains the same as before)
		$primary_color = AJL_Settings::get_setting( $form_id, 'primary_color' );
		if ( $primary_color ) {
			$config['appearance']['variables']['colorPrimary'] = $primary_color;
            $config['appearance']['rules']['.Tab:focus']['boxShadow'] = 'inset 0 -4px ' . $primary_color;
            $config['appearance']['rules']['.Tab:hover']['boxShadow'] = 'inset 0 -4px ' . $primary_color;
            $config['appearance']['rules']['.Tab--selected']['boxShadow'] = 'inset 0 -2px ' . $primary_color;
            $config['appearance']['rules']['.Tab--selected:focus']['boxShadow'] = 'inset 0 -4px ' . $primary_color;
            $config['appearance']['rules']['.Input:focus']['boxShadow'] = sprintf(
                '0 0 0 1px %1$s, 0 0 0 3px %2$s, 0 1px 2px rgba(0, 0, 0, 0.05)',
                $primary_color,
                self::hex_to_rgba($primary_color, 0.15)
            );
            $config['appearance']['rules']['.CodeInput:focus']['boxShadow'] = $config['appearance']['rules']['.Input:focus']['boxShadow'];
            $config['appearance']['rules']['.CheckboxInput:focus']['boxShadow'] = $config['appearance']['rules']['.Input:focus']['boxShadow'];
            $config['appearance']['rules']['.PickerItem--selected']['boxShadow'] = $config['appearance']['rules']['.Input:focus']['boxShadow'];
            
            // Add focus styling for dropdown selects
            $config['appearance']['rules']['.p-Select-select:focus']['boxShadow'] = $config['appearance']['rules']['.Input:focus']['boxShadow'];
            $config['appearance']['rules']['.p-Select-select:focus']['borderColor'] = $primary_color;
		}

		$background_color = AJL_Settings::get_setting( $form_id, 'background_color' );
		if ( $background_color ) {
			$config['appearance']['variables']['colorBackground'] = $background_color;
            
            // Add explicit background color for select dropdowns
            $config['appearance']['rules']['.p-Select-select']['backgroundColor'] = $background_color;
            $config['appearance']['rules']['.p-Select-option']['backgroundColor'] = $background_color;
		}

		$text_color = AJL_Settings::get_setting( $form_id, 'text_color' );
		if ( $text_color ) {
			$config['appearance']['variables']['colorText'] = $text_color;
            $config['appearance']['rules']['.TabLabel']['color'] = $text_color;
            $config['appearance']['rules']['.Label']['color'] = $text_color;
            $config['appearance']['rules']['.Input']['color'] = $text_color;
            $config['appearance']['rules']['.CodeInput']['color'] = $text_color;
            $config['appearance']['rules']['.PickerItem']['color'] = $text_color;
            $config['appearance']['rules']['.DropdownItem']['color'] = $text_color;
            $config['appearance']['rules']['.TabIcon--selected']['fill'] = $text_color;
            
            // Add text color for select dropdown and options
            $config['appearance']['rules']['.p-Select-select']['color'] = $text_color;
            $config['appearance']['rules']['.p-Select-option']['color'] = $text_color;
            $config['appearance']['rules']['option']['color'] = $text_color;
		}
		
		// Apply label text color (overrides general text color for labels)
		$label_text_color = AJL_Settings::get_setting( $form_id, 'label_text_color' );
		if ( $label_text_color ) {
		    $config['appearance']['rules']['.Label']['color'] = $label_text_color;
		    $config['appearance']['rules']['.TabLabel']['color'] = $label_text_color;
		}
		
		// Apply input text color (overrides general text color for inputs)
		$input_text_color = AJL_Settings::get_setting( $form_id, 'input_text_color' );
		if ( $input_text_color ) {
		    $config['appearance']['rules']['.Input']['color'] = $input_text_color;
		    $config['appearance']['rules']['.CodeInput']['color'] = $input_text_color;
		    $config['appearance']['rules']['.PickerItem']['color'] = $input_text_color;
		    $config['appearance']['rules']['.DropdownItem']['color'] = $input_text_color;
		    $config['appearance']['rules']['.p-Select-select']['color'] = $input_text_color;
		    $config['appearance']['rules']['.p-Select-option']['color'] = $input_text_color;
		    $config['appearance']['rules']['option']['color'] = $input_text_color;
		    $config['appearance']['rules']['.p-FauxInput']['color'] = $input_text_color;
		}

		// Apply border color
		$border_color = AJL_Settings::get_setting( $form_id, 'border_color' );
		if ( $border_color ) {
		    // Add border color to inputs
		    $config['appearance']['rules']['.Input']['boxShadow'] = '0 0 0 1px ' . $border_color . ', 0 1px 2px rgba(0, 0, 0, 0.05)';
		    $config['appearance']['rules']['.CodeInput']['boxShadow'] = $config['appearance']['rules']['.Input']['boxShadow'];
		    $config['appearance']['rules']['.CheckboxInput']['boxShadow'] = $config['appearance']['rules']['.Input']['boxShadow'];
		    $config['appearance']['rules']['.p-Select-select']['boxShadow'] = $config['appearance']['rules']['.Input']['boxShadow'];
		    $config['appearance']['rules']['.p-Select-select']['borderColor'] = $border_color;
		}

		$border_radius = AJL_Settings::get_setting( $form_id, 'border_radius', 0 );
		if ( $border_radius !== '' ) { // Allow 0
			$config['appearance']['variables']['borderRadius'] = $border_radius . 'px';
            
            // Add explicit border radius for select elements
            $config['appearance']['rules']['.p-Select-select']['borderRadius'] = $border_radius . 'px';
		}

		$input_font_size = AJL_Settings::get_setting( $form_id, 'input_font_size' );
		if ( $input_font_size ) {
			$config['appearance']['rules']['.Input']['fontSize'] = $input_font_size . 'px';
            $config['appearance']['rules']['.CodeInput']['fontSize'] = $input_font_size . 'px';
            $config['appearance']['rules']['.PickerItem']['fontSize'] = $input_font_size . 'px';
            
            // Add font size for select elements
            $config['appearance']['rules']['.p-Select-select']['fontSize'] = $input_font_size . 'px';
            $config['appearance']['rules']['.p-Select-option']['fontSize'] = $input_font_size . 'px';
            $config['appearance']['rules']['option']['fontSize'] = $input_font_size . 'px';
		}

        $label_font_size = AJL_Settings::get_setting( $form_id, 'label_font_size' );
        if ( $label_font_size ) {
            $config['appearance']['rules']['.Label']['fontSize'] = $label_font_size . 'px';
            $config['appearance']['rules']['.TabLabel']['fontSize'] = $label_font_size . 'px';
        }

        $label_font_weight = AJL_Settings::get_setting( $form_id, 'label_font_weight' );
        if ( $label_font_weight ) {
             $config['appearance']['rules']['.Label']['fontWeight'] = $label_font_weight;
             $config['appearance']['rules']['.TabLabel']['fontWeight'] = $label_font_weight;
        }
		// --- End Apply styles ---

		return $config;
	}

	/**
	 * Capture the ID of a form being rendered on the page.
	 *
	 * @param int $form_id The ID of the form being rendered.
	 */
	public function capture_rendered_form_id( $form_id ) {
		$form_id = absint( $form_id );
		if ( $form_id > 0 && ! in_array( $form_id, self::$rendered_form_ids, true ) ) {
			self::$rendered_form_ids[] = $form_id;
		}
	}

	/**
	 * Prints inline CSS late in the footer for non-Elements form parts.
	 */
	public function print_late_frontend_styles() {
		if ( empty( self::$rendered_form_ids ) ) {
			return;
		}

		$css = "";
		$preview_css = ""; // Separate CSS for preview wrapper
		
		// Add global button reset for all forms to override WP Simple Pay defaults
		$css .= "
		/* Reset button border radius to 0px by default to override WP Simple Pay's 4px default */
		body .simpay-form-wrap .simpay-payment-btn,
		body .simpay-form .simpay-checkout-btn,
		body .simpay-form .simpay-apply-coupon,
		body .simpay-form button.simpay-payment-btn,
		body .simpay-form button.simpay-checkout-btn,
		body .simpay-form button.simpay-apply-coupon {
			border-radius: 0px !important;
		}
		";

		foreach ( self::$rendered_form_ids as $form_id ) {
			$display_type = get_post_meta( $form_id, '_form_display_type', true );
			if ( ! in_array( $display_type, [ 'embedded', 'overlay' ], true ) ) {
				continue;
			}

			// Get the base CSS from our generator method
			$form_css = $this->generate_custom_css($form_id);
			$css .= $form_css;

			// Add legacy CSS selectors for backwards compatibility
			$form_selector_prefix = "#simpay-form-{$form_id} ";
			$live_wrapper_selector = "#simpay-embedded-form-wrap-{$form_id}"; // Target the outer wrapper div by ID

			// --- Form Container Background Color & Padding
			$form_bg_color = AJL_Settings::get_setting( $form_id, 'form_container_background_color' );
			if ( $form_bg_color ) {
				// Apply background to the specific live wrapper ID (no padding)
				$css .= "{$live_wrapper_selector} { 
					background-color: " . esc_attr( $form_bg_color ) . " !important; 
					border-radius: 0 !important; 
					margin: 0 auto !important; 
				}\n"; 
				// Apply background only to preview wrapper
				$preview_css .= "body.simpay-form-preview .simpay-form-preview-wrap { background: " . esc_attr( $form_bg_color ) . " !important; }\n";
			}
		}

		// Combine live CSS and preview CSS
		$final_css = $preview_css . $css;

		// Output the combined CSS
		if ( ! empty( $final_css ) ) {
			// Directly print the CSS in the footer
			echo "\n<style id=\"ajl-wpsps-inline-styles-late\">\n";
			echo $final_css; // WPCS: XSS okay.
			echo "</style>\n";
		}
	}

    /**
     * Helper function to convert hex color to rgba.
     */
    private static function hex_to_rgba( $color, $opacity = 1 ) {
        $color = ltrim( $color, '#' );
        if ( strlen( $color ) === 3 ) {
            $r = hexdec( substr( $color, 0, 1 ) . substr( $color, 0, 1 ) );
            $g = hexdec( substr( $color, 1, 1 ) . substr( $color, 1, 1 ) );
            $b = hexdec( substr( $color, 2, 1 ) . substr( $color, 2, 1 ) );
        } elseif ( strlen( $color ) === 6 ) {
            $r = hexdec( substr( $color, 0, 2 ) );
            $g = hexdec( substr( $color, 2, 2 ) );
            $b = hexdec( substr( $color, 4, 2 ) );
        } else {
            return 'rgba(0,0,0,0)'; // Invalid color
        }
        $opacity = max( 0, min( 1, $opacity ) );
        return sprintf( 'rgba(%d,%d,%d,%.2f)', $r, $g, $b, $opacity );
    }

	/**
	 * Generate the custom CSS for a form based on its style settings.
	 *
	 * @param int $form_id The form ID.
	 * @return string The generated CSS.
	 */
	private function generate_custom_css( $form_id ) {
		$css = '';

		// Only add container width to make sure the form doesn't get squeezed
		$css .= "
		/* Form container width */
		#simpay-embedded-form-wrap-{$form_id} {
			max-width: none !important;
		}";
		
		// Now add the theme-specific styles
		// Add form container background color
		$form_bg_color = AJL_Settings::get_setting( $form_id, 'form_container_background_color' );
		if ( ! empty( $form_bg_color ) ) {
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] { background-color: {$form_bg_color} !important; }\n";
			$css .= "#simpay-form-{$form_id} { background-color: {$form_bg_color} !important; }\n";
		}

		// Add form background color (input fields)
		$bg_color = AJL_Settings::get_setting( $form_id, 'background_color' );
		if ( ! empty( $bg_color ) ) {
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .simpay-form-control { background-color: {$bg_color} !important; }\n";
			$css .= "#simpay-form-{$form_id} .simpay-form-control { background-color: {$bg_color} !important; }\n";
			
			// Target Stripe Elements inputs including selects
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement .Input { background-color: {$bg_color} !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement select.Input { background-color: {$bg_color} !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement .p-Select-select { background-color: {$bg_color} !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement input { background-color: {$bg_color} !important; }\n";
		}

		// Add text color
		$text_color = AJL_Settings::get_setting( $form_id, 'text_color' );
		if ( ! empty( $text_color ) ) {
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] { color: {$text_color} !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .simpay-form-control { color: {$text_color} !important; }\n";
			$css .= "#simpay-form-{$form_id} { color: {$text_color} !important; }\n";
			$css .= "#simpay-form-{$form_id} .simpay-form-control { color: {$text_color} !important; }\n";
			
			// Target Stripe Elements text colors - including dropdowns, inputs and labels
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement .Input { color: {$text_color} !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement select.Input { color: {$text_color} !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement .p-Select-select { color: {$text_color} !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement .Label { color: {$text_color} !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement input { color: {$text_color} !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement option { color: {$text_color} !important; }\n";
		}
		
		// Add label text color (overrides text color for labels)
		$label_text_color = AJL_Settings::get_setting( $form_id, 'label_text_color' );
		if ( ! empty( $label_text_color ) ) {
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] label { color: {$label_text_color} !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .simpay-label { color: {$label_text_color} !important; }\n";
			$css .= "#simpay-form-{$form_id} label { color: {$label_text_color} !important; }\n";
			$css .= "#simpay-form-{$form_id} .simpay-label { color: {$label_text_color} !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement .Label { color: {$label_text_color} !important; }\n";
		}
		
		// Add input text color (overrides text color for inputs)
		$input_text_color = AJL_Settings::get_setting( $form_id, 'input_text_color' );
		if ( ! empty( $input_text_color ) ) {
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] input { color: {$input_text_color} !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] select { color: {$input_text_color} !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] textarea { color: {$input_text_color} !important; }\n";
			$css .= "#simpay-form-{$form_id} input { color: {$input_text_color} !important; }\n";
			$css .= "#simpay-form-{$form_id} select { color: {$input_text_color} !important; }\n";
			$css .= "#simpay-form-{$form_id} textarea { color: {$input_text_color} !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement .Input { color: {$input_text_color} !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement select.Input { color: {$input_text_color} !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement .p-Select-select { color: {$input_text_color} !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement input { color: {$input_text_color} !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement option { color: {$input_text_color} !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement .p-FauxInput { color: {$input_text_color} !important; }\n";
		}

		// Add border color
		$border_color = AJL_Settings::get_setting( $form_id, 'border_color' );
		if ( ! empty( $border_color ) ) {
		    $css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .simpay-form-control { border-color: {$border_color} !important; }\n";
		    $css .= "#simpay-form-{$form_id} .simpay-form-control { border-color: {$border_color} !important; }\n";
		    $css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] input:not([type='submit']) { border-color: {$border_color} !important; }\n";
		    $css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] select { border-color: {$border_color} !important; }\n";
		    $css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] textarea { border-color: {$border_color} !important; }\n";
		    
		    // Add specific style for Stripe elements
		    $css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement { border-color: {$border_color} !important; }\n";
		    $css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement .Input { box-shadow: 0 0 0 1px {$border_color}, 0 1px 2px rgba(0, 0, 0, 0.05) !important; }\n";
		    $css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement .CodeInput { box-shadow: 0 0 0 1px {$border_color}, 0 1px 2px rgba(0, 0, 0, 0.05) !important; }\n";
		}

		// Add primary color (used for focus states, etc.)
		$primary_color = AJL_Settings::get_setting( $form_id, 'primary_color' );
		if ( ! empty( $primary_color ) ) {
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .simpay-form-control:focus { border-color: {$primary_color} !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] a { color: {$primary_color} !important; }\n";
			$css .= "#simpay-form-{$form_id} .simpay-form-control:focus { border-color: {$primary_color} !important; }\n";
			$css .= "#simpay-form-{$form_id} a { color: {$primary_color} !important; }\n";
			
			// Target Stripe Elements focus states
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement .Input:focus { border-color: {$primary_color} !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement select.Input:focus { border-color: {$primary_color} !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement .p-Select-select:focus { border-color: {$primary_color} !important; }\n";
		}

		// Add border radius - ALWAYS set button border radius to ensure we override WP Simple Pay defaults
		$border_radius = AJL_Settings::get_setting( $form_id, 'border_radius', 0 );
		
		// For form controls
		if ( '' !== $border_radius ) {
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .simpay-form-control { border-radius: {$border_radius}px !important; }\n";
			$css .= "#simpay-form-{$form_id} .simpay-form-control { border-radius: {$border_radius}px !important; }\n";
			
			// Target Stripe Elements border radius
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement .Input { border-radius: {$border_radius}px !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement select.Input { border-radius: {$border_radius}px !important; }\n";
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .StripeElement .p-Select-select { border-radius: {$border_radius}px !important; }\n";
		}
		
		// Always explicitly set button border radius to override WP Simple Pay's default 4px
		$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .simpay-payment-btn { border-radius: {$border_radius}px !important; }\n";
		$css .= "#simpay-form-{$form_id} .simpay-payment-btn { border-radius: {$border_radius}px !important; }\n";
		$css .= "#simpay-form-{$form_id} .simpay-checkout-btn { border-radius: {$border_radius}px !important; }\n";
		$css .= "#simpay-form-{$form_id} .simpay-apply-coupon { border-radius: {$border_radius}px !important; }\n";

		// Target the buttons with higher specificity to override WP Simple Pay defaults
		$css .= "body .simpay-form-wrap[data-form-id=\"{$form_id}\"] button.simpay-payment-btn { border-radius: {$border_radius}px !important; }\n";
		$css .= "body #simpay-form-{$form_id} button.simpay-checkout-btn { border-radius: {$border_radius}px !important; }\n";
		$css .= "body #simpay-form-{$form_id} button.simpay-apply-coupon { border-radius: {$border_radius}px !important; }\n";

		// Add button background color
		$button_bg_color = AJL_Settings::get_setting( $form_id, 'button_background_color', '#0f8569' );
		if ( ! empty( $button_bg_color ) ) {
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .simpay-payment-btn { background-color: {$button_bg_color} !important; border-color: {$button_bg_color} !important; }\n";
			$css .= "#simpay-form-{$form_id} .simpay-payment-btn { background-color: {$button_bg_color} !important; border-color: {$button_bg_color} !important; }\n";
			$css .= "#simpay-form-{$form_id} .simpay-checkout-btn { background-color: {$button_bg_color} !important; border-color: {$button_bg_color} !important; }\n";
			$css .= "#simpay-form-{$form_id} .simpay-apply-coupon { background-color: {$button_bg_color} !important; border-color: {$button_bg_color} !important; }\n";
		}

		// Add button text color
		$button_text_color = AJL_Settings::get_setting( $form_id, 'button_text_color', '#ffffff' );
		if ( ! empty( $button_text_color ) ) {
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .simpay-payment-btn { color: {$button_text_color} !important; }\n";
			$css .= "#simpay-form-{$form_id} .simpay-payment-btn { color: {$button_text_color} !important; }\n";
			$css .= "#simpay-form-{$form_id} .simpay-checkout-btn { color: {$button_text_color} !important; }\n";
			$css .= "#simpay-form-{$form_id} .simpay-apply-coupon { color: {$button_text_color} !important; }\n";
		}

		// Add button hover background color
		$button_hover_bg_color = AJL_Settings::get_setting( $form_id, 'button_hover_background_color', '#0e7c62' );
		if ( ! empty( $button_hover_bg_color ) ) {
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .simpay-payment-btn:hover { background-color: {$button_hover_bg_color} !important; border-color: {$button_hover_bg_color} !important; }\n";
			$css .= "#simpay-form-{$form_id} .simpay-payment-btn:hover { background-color: {$button_hover_bg_color} !important; border-color: {$button_hover_bg_color} !important; }\n";
			$css .= "#simpay-form-{$form_id} .simpay-checkout-btn:hover { background-color: {$button_hover_bg_color} !important; border-color: {$button_hover_bg_color} !important; }\n";
			$css .= "#simpay-form-{$form_id} .simpay-apply-coupon:hover { background-color: {$button_hover_bg_color} !important; border-color: {$button_hover_bg_color} !important; }\n";
		}

		// Add font sizes
		$label_font_size = AJL_Settings::get_setting( $form_id, 'label_font_size' );
		if ( ! empty( $label_font_size ) ) {
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] label.simpay-form-control { font-size: {$label_font_size}px !important; }\n";
			$css .= "#simpay-form-{$form_id} label.simpay-form-control { font-size: {$label_font_size}px !important; }\n";
			$css .= "#simpay-form-{$form_id} .simpay-label { font-size: {$label_font_size}px !important; }\n";
		}

		$input_font_size = AJL_Settings::get_setting( $form_id, 'input_font_size' );
		if ( ! empty( $input_font_size ) ) {
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] .simpay-form-control { font-size: {$input_font_size}px !important; }\n";
			$css .= "#simpay-form-{$form_id} .simpay-form-control { font-size: {$input_font_size}px !important; }\n";
		}

		// Add font weights
		$label_font_weight = AJL_Settings::get_setting( $form_id, 'label_font_weight' );
		if ( ! empty( $label_font_weight ) ) {
			$css .= ".simpay-form-wrap[data-form-id=\"{$form_id}\"] label.simpay-form-control { font-weight: {$label_font_weight} !important; }\n";
			$css .= "#simpay-form-{$form_id} label.simpay-form-control { font-weight: {$label_font_weight} !important; }\n";
			$css .= "#simpay-form-{$form_id} .simpay-label { font-weight: {$label_font_weight} !important; }\n";
		}

		return $css;
	}
} 