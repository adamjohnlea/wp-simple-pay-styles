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
		}

		$background_color = AJL_Settings::get_setting( $form_id, 'background_color' );
		if ( $background_color ) {
			$config['appearance']['variables']['colorBackground'] = $background_color;
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
		}

		$border_radius = AJL_Settings::get_setting( $form_id, 'border_radius' );
		if ( $border_radius !== '' ) { // Allow 0
			$config['appearance']['variables']['borderRadius'] = $border_radius . 'px';
		}

		$input_font_size = AJL_Settings::get_setting( $form_id, 'input_font_size' );
		if ( $input_font_size ) {
			$config['appearance']['rules']['.Input']['fontSize'] = $input_font_size . 'px';
            $config['appearance']['rules']['.CodeInput']['fontSize'] = $input_font_size . 'px';
            $config['appearance']['rules']['.PickerItem']['fontSize'] = $input_font_size . 'px';
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

		foreach ( self::$rendered_form_ids as $form_id ) {
			$display_type = get_post_meta( $form_id, '_form_display_type', true );
			if ( ! in_array( $display_type, [ 'embedded', 'overlay' ], true ) ) {
				continue;
			}

			$form_selector_prefix = "#simpay-form-{$form_id} ";
			$input_selector_base = "#simpay-form-{$form_id} .simpay-form-control ";

			// --- Generate CSS --- 
			$background_color = AJL_Settings::get_setting( $form_id, 'background_color' );
			if ( $background_color ) {
				$css .= "{$input_selector_base}input[type=\"text\"],
                         {$input_selector_base}input[type=\"email\"],
                         {$input_selector_base}input[type=\"tel\"],
                         {$input_selector_base}input[type=\"number\"],
                         {$input_selector_base}select,
                         {$input_selector_base}textarea
                         { background-color: " . esc_attr( $background_color ) . " !important; }\n";
			}

			$text_color = AJL_Settings::get_setting( $form_id, 'text_color' );
			if ( $text_color ) {
				$css .= "{$form_selector_prefix}.simpay-label,
                         {$form_selector_prefix}label,
                         {$input_selector_base}input[type=\"text\"],
                         {$input_selector_base}input[type=\"email\"],
                         {$input_selector_base}input[type=\"tel\"],
                         {$input_selector_base}input[type=\"number\"],
                         {$input_selector_base}select,
                         {$input_selector_base}textarea,
                         {$form_selector_prefix}.simpay-total-amount
                         { color: " . esc_attr( $text_color ) . " !important; }\n";
			}

			$border_radius = AJL_Settings::get_setting( $form_id, 'border_radius' );
			if ( $border_radius !== '' ) {
				$css .= "{$input_selector_base}input[type=\"text\"],
                         {$input_selector_base}input[type=\"email\"],
                         {$input_selector_base}input[type=\"tel\"],
                         {$input_selector_base}input[type=\"number\"],
                         {$input_selector_base}input[type=\"radio\"],
                         {$input_selector_base}input[type=\"checkbox\"],
                         {$input_selector_base}select,
                         {$input_selector_base}textarea,
                         {$form_selector_prefix}.simpay-checkout-btn
                         { border-radius: " . esc_attr( $border_radius ) . "px !important; }\n";
			}

			$label_font_size = AJL_Settings::get_setting( $form_id, 'label_font_size' );
			if ( $label_font_size ) {
				$css .= "{$form_selector_prefix}.simpay-label,
                         {$form_selector_prefix}label
                         { font-size: " . esc_attr( $label_font_size ) . "px !important; }\n";
			}

			$label_font_weight = AJL_Settings::get_setting( $form_id, 'label_font_weight' );
			if ( $label_font_weight ) {
				$css .= "{$form_selector_prefix}.simpay-label,
                         {$form_selector_prefix}label
                         { font-weight: " . esc_attr( $label_font_weight ) . " !important; }\n";
			}

			$input_font_size = AJL_Settings::get_setting( $form_id, 'input_font_size' );
			if ( $input_font_size ) {
				$css .= "{$input_selector_base}input[type=\"text\"],
                         {$input_selector_base}input[type=\"email\"],
                         {$input_selector_base}input[type=\"tel\"],
                         {$input_selector_base}input[type=\"number\"],
                         {$input_selector_base}select,
                         {$input_selector_base}textarea
                         { font-size: " . esc_attr( $input_font_size ) . "px !important; }\n";
			}

			$button_bg = AJL_Settings::get_setting( $form_id, 'button_background_color' );
			if ( $button_bg ) {
				$css .= "{$form_selector_prefix}.simpay-checkout-btn { background-color: " . esc_attr( $button_bg ) . " !important; border-color: " . esc_attr( $button_bg ) . " !important; }\n";
			}

			$button_text = AJL_Settings::get_setting( $form_id, 'button_text_color' );
			if ( $button_text ) {
				$css .= "{$form_selector_prefix}.simpay-checkout-btn { color: " . esc_attr( $button_text ) . " !important; }\n";
			}

			$button_hover_bg = AJL_Settings::get_setting( $form_id, 'button_hover_background_color' );
			if ( $button_hover_bg ) {
				$css .= "{$form_selector_prefix}.simpay-checkout-btn:hover { background-color: " . esc_attr( $button_hover_bg ) . " !important; border-color: " . esc_attr( $button_hover_bg ) . " !important; }\n";
			}
			// --- End Generate CSS ---
		}

		if ( ! empty( $css ) ) {
			// Directly print the CSS in the footer
			echo "\n<style id=\"ajl-wpsps-inline-styles-late\">\n";
			echo $css; // WPCS: XSS okay. CSS is generated from sanitized settings.
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
} 