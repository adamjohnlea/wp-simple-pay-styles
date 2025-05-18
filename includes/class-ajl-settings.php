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
 *
 * Handles getting, setting, and managing style meta values for forms.
 * Provides methods for retrieving, saving, and deleting style settings,
 * as well as checking if settings exist and sanitizing values.
 *
 * @since 1.0.0
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
				$value = absint( $value ); // Assuming pixels for now
				// For the border radius, treat empty and 0 consistently
				if ($value === 0 && $default === '') {
					$default = 0;
				}
				break;
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
  * Get all style setting keys.
  *
  * Returns an array of all available style setting keys that can be
  * used with get_setting() and save_setting() methods.
  *
  * @since 1.0.0
  *
  * @return array Array of style setting keys.
  */
	public static function get_style_keys() {
		return [
			'selected_theme',
			'form_container_background_color',
			'background_color',
			'text_color',
			'label_text_color',
			'input_text_color',
			'border_color',
			'primary_color',
			'button_background_color',
			'button_text_color',
			'button_hover_background_color',
			'border_radius',
			'label_font_size',
			'label_font_weight',
			'input_font_size',
		];
	}

 /**
  * Delete a specific style setting for a post.
  *
  * Removes a style setting from the database for the specified post.
  *
  * @since 1.0.0
  *
  * @param int    $post_id The post ID.
  * @param string $key     The setting key (without prefix).
  * @return void
  */
	public static function delete_setting( $post_id, $key ) {
		delete_post_meta( $post_id, self::$meta_prefix . sanitize_key( $key ) );
	}

 /**
  * Check if a specific style setting exists for a post.
  *
  * Determines if a style setting has been saved for the specified post.
  *
  * @since 1.0.0
  *
  * @param int    $post_id The post ID.
  * @param string $key     The setting key (without prefix).
  * @return bool True if the meta key exists, false otherwise.
  */
	public static function setting_exists( $post_id, $key ) {
		return metadata_exists( 'post', $post_id, self::$meta_prefix . sanitize_key( $key ) );
	}

 /**
  * Sanitize a setting value.
  *
  * Sanitizes a setting value based on its key to ensure data integrity
  * and security before use.
  *
  * @since 1.0.0
  *
  * @param string $key   Setting key.
  * @param mixed  $value Setting value.
  * @return mixed Sanitized setting value.
  */
	public static function sanitize_setting( $key, $value ) {
		switch ( $key ) {
			case 'selected_theme':
				return sanitize_text_field( $value );
			case 'form_container_background_color':
			case 'background_color':
			case 'text_color':
			case 'label_text_color':
			case 'input_text_color':
			case 'border_color':
			case 'primary_color':
			case 'button_background_color':
			case 'button_text_color':
			case 'button_hover_background_color':
				return sanitize_hex_color( $value );
			case 'border_radius':
			case 'label_font_size':
			case 'input_font_size':
				return absint( $value );
			case 'label_font_weight':
				$allowed_values = array( 'normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900' );
				return in_array( $value, $allowed_values, true ) ? $value : 'normal';
			default:
				return $value;
		}
	}

} 
