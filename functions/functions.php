<?php

/**
 * Register shortcode
 */
function an_mc_register_shortcodes() {
	$shortcodeDir = Another_mailChimp_widget::get_plugin_dir() . 'shortcodes/items/';
	$shortcodes   = array(
		$shortcodeDir . 'an_shortcode_mailchimp.php',
	);
	
	foreach ( $shortcodes as $sc ) {
		require_once $sc;
	}
}

/**
 * row Button
 */
function an_mc_row_button() {
	if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	
	if ( get_user_option( 'rich_editing' ) == 'true' ) {
		add_filter( 'mce_external_plugins', 'an_mc_add_plugin' );
		add_filter( 'mce_buttons', 'an_mc_register_button' );
	}
}

/**
 * @param $buttons
 *
 * @return mixed
 */
function an_mc_register_button( $buttons ) {
	array_push( $buttons, 'mp-mc-form' );
	
	return $buttons;
}

/**
 * @param $plugin_array
 *
 * @return mixed
 */
function an_mc_add_plugin( $plugin_array ) {
	
	$plugin_array[ 'ar_buttons' ] = Another_mailChimp_widget::get_instance()->get_plugin_url() . "shortcodes/js/shortcodes.js";
	
	return $plugin_array;
}