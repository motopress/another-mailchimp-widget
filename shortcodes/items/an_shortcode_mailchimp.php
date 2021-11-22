<?php

include_once Another_mailChimp_widget::get_plugin_dir() . 'shortcodes/items/an_shortcode_mailchimp_class.php';

add_shortcode( 'mp-mc-form', 'an_shortcode_mailchimp_function' );

/**
 * @param $params
 * @param null $content
 *
 * @return string
 */
function an_shortcode_mailchimp_function( $params, $content = null ) {
	
	STATIC $index_sh = 1;
	
	extract( shortcode_atts( array(
		'list'            => '',
		'button'          => esc_html__( 'Subscribe', 'another-mailchimp-widget' ),
		'first_name_text' => esc_html__( 'First Name', 'another-mailchimp-widget' ),
		'last_name_text'  => esc_html__( 'Last Name', 'another-mailchimp-widget' ),
		'email_text'      => esc_html__( 'Your E-mail', 'another-mailchimp-widget' ),
		'placeholder'     => true,
		'firstname'       => false,
		'lastname'        => false,
		'success'         => esc_html__( 'Thank you for joining our mailing list.', 'another-mailchimp-widget' ),
		'failure'         => esc_html__( 'There was a problem processing your submission.', 'another-mailchimp-widget' )
	), $params ) );
	
	ob_start();
	
	$instance = array(
		'current_mailing_list' => $list,
		'signup_text'          => $button,
		'first_name_text'      => $first_name_text,
		'last_name_text'       => $last_name_text,
		'email_text'           => $email_text,
		'showplaceholder'      => ( $placeholder == 'false' ) ? false : true,
		'collect_first'        => ( $firstname == 'false' ) ? false : true,
		'collect_last'         => ( $lastname == 'false' ) ? false : true,
		'success_message'      => $success,
		'failure_message'      => $failure
	);
	
	$shMailChimp = new AN_Shortcode_MailChimp( $instance, $index_sh );
	
	$shMailChimp->get_MailChimp( $instance );
	
	$output = ob_get_contents();
	
	ob_end_clean();
	
	$index_sh ++;
	
	return $output;
}
