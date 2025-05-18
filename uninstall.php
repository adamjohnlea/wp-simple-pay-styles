<?php
/**
 * Uninstall WP Simple Pay Styles
 *
 * Deletes all plugin data when uninstalled.
 *
 * @package AJL_WP_Simple_Pay_Styles
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Define the meta prefix used by the plugin.
$meta_prefix = '_ajl_style_';

// Get all style setting keys.
$style_keys = array(
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
);

// Get all WP Simple Pay forms.
$forms = get_posts(
	array(
		'post_type'      => 'simple-pay',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
	)
);

// Delete all plugin meta data for each form.
if ( ! empty( $forms ) && is_array( $forms ) ) {
	foreach ( $forms as $form_id ) {
		foreach ( $style_keys as $key ) {
			delete_post_meta( $form_id, $meta_prefix . $key );
		}
	}
}

// Clean up is complete.