<?php
/**
 * Handles getting and setting style meta values.
 *
 * @package AJL_WP_Simple_Pay_Styles
 */

namespace AJL_WP_Simple_Pay_Styles;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings handler class.
 */
class AJL_Settings {

	/**
	 * The prefix for all style meta keys.
	 *
	 * @var string
	 */
	private static $meta_prefix = '_ajl_style_';

	/**
	 * Get a specific style setting for a form.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $form_id The WP Simple Pay form ID (Post ID).
	 * @param string $key     The setting key (e.g., 'primary_color').
	 * @param mixed  $default Optional. The default value if the setting is not found.
	 * @return mixed The setting value.
	 */
	public static function get_setting( $form_id, $key, $default = '' ) {
		$meta_key = self::$meta_prefix . sanitize_key( $key );
		$value = get_post_meta( $form_id, $meta_key, true );

		// Basic sanitization based on expected type (can be expanded)
		switch ( $key ) {
			case 'primary_color':
			case 'background_color':
			case 'text_color':
			case 'button_background_color':
			case 'button_text_color':
			case 'button_hover_background_color':
				$value = sanitize_hex_color( $value );
				break;
			case 'border_radius':
			case 'label_font_size':
			case 'input_font_size':
				$value = absint( $value ); // Assuming pixels for now
				break;
			case 'label_font_weight':
				$value = sanitize_text_field( $value ); // e.g., 'normal', 'bold', '400', '700'
				break;
			default:
				$value = sanitize_text_field( $value ); // Generic fallback
		}

		return ( '' !== $value ) ? $value : $default;
	}

	/**
	 * Save a specific style setting for a form.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $form_id The WP Simple Pay form ID (Post ID).
	 * @param string $key     The setting key (e.g., 'primary_color').
	 * @param mixed  $value   The value to save.
	 * @return bool|int Meta ID if the key didn't exist, true on successful update, false on failure.
	 */
	public static function save_setting( $form_id, $key, $value ) {
		$meta_key = self::$meta_prefix . sanitize_key( $key );

		// Basic sanitization before saving (can be expanded)
		switch ( $key ) {
			case 'primary_color':
			case 'background_color':
			case 'text_color':
			case 'button_background_color':
			case 'button_text_color':
			case 'button_hover_background_color':
				$value = sanitize_hex_color( $value );
				break;
			case 'border_radius':
			case 'label_font_size':
			case 'input_font_size':
				$value = absint( $value );
				break;
			case 'label_font_weight':
				// Allow specific values or numeric weights
				$allowed_weights = [ 'normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900' ];
				if ( ! in_array( (string) $value, $allowed_weights, true ) ) {
					$value = 'normal'; // Default if invalid
				}
				break;
			default:
				$value = sanitize_text_field( $value );
		}

		return update_post_meta( $form_id, $meta_key, $value );
	}

	/**
	 * Get all defined style keys.
	 *
	 * @since 1.0.0
	 * @return array List of style keys.
	 */
	public static function get_style_keys() {
		return [
			'primary_color',
			'background_color',
			'text_color',
			'border_radius',
			'button_background_color',
			'button_text_color',
			'button_hover_background_color',
			'label_font_size',
			'label_font_weight',
			'input_font_size',
		];
	}

	/**
	 * Delete a specific style setting for a post.
	 *
	 * @param int    $post_id The post ID.
	 * @param string $key     The setting key (without prefix).
	 */
	public static function delete_setting( $post_id, $key ) {
		delete_post_meta( $post_id, self::$meta_prefix . sanitize_key( $key ) );
	}

	/**
	 * Check if a specific style setting exists for a post.
	 *
	 * @param int    $post_id The post ID.
	 * @param string $key     The setting key (without prefix).
	 * @return bool True if the meta key exists, false otherwise.
	 */
	public static function setting_exists( $post_id, $key ) {
		return metadata_exists( 'post', $post_id, self::$meta_prefix . sanitize_key( $key ) );
	}

} 