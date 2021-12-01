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

		// display dialog window
		add_action( 'admin_head', 'an_mc_admin_head' );
		add_action( 'admin_post_an_mc_get_modal_content', 'an_mc_get_modal_content' );
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
	
	$plugin_array[ 'ar_buttons' ] = add_query_arg(
		'ver',
		AN_MC_PLUGIN_VERSION,
		Another_mailChimp_widget::get_instance()->get_plugin_url() . "shortcodes/js/shortcodes.js"
	);
	
	return $plugin_array;
}

function an_mc_admin_head() {
	?>
	<script type="text/javascript">
		var an_mc_dialog_url = "<?php
			echo esc_url(
				add_query_arg(
					array(
						'action' => 'an_mc_get_modal_content',
						'_wpnonce' => wp_create_nonce( 'an_mc_get_modal_content' )
					),
					admin_url( 'admin-post.php' )
				)
			);
		?>";
	</script>
	<?php
}
 
function an_mc_get_modal_content() {

	check_admin_referer( 'an_mc_get_modal_content' );

	include Another_mailChimp_widget::get_plugin_dir() . '/shortcodes/forms/an_shortcode_mailchimp.php';
	exit;
}
