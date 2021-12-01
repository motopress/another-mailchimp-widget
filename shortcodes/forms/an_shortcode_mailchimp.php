<?php

$an_mc_plugin = AN_MC_Plugin::get_instance();
$an_mc_api_key      = $an_mc_plugin->is_have_api_key();
$an_mc_lists        = array();

if ( $an_mc_api_key ) {
	$an_mc_mcapi = $an_mc_plugin->get_mcapi();
	$an_mc_lists = $an_mc_mcapi->get_account_subscribe_lists();
}

$includes_url = includes_url();
$an_mc_plugin_url = Another_mailChimp_widget::get_instance()->get_plugin_url();

?>
<!doctype html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<head>
	<title><?php esc_html_e( 'Insert Mailchimp Subscription Form', 'another-mailchimp-widget' ); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>

	<script language="javascript" type="text/javascript" src="<?php echo esc_url( $includes_url ); ?>/js/tinymce/tiny_mce_popup.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo esc_url( $includes_url ); ?>/js/jquery/jquery.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo esc_url( $an_mc_plugin_url ); ?>assets/js/shortcode-popup.js"></script>
	<base target="_self"/>

	<style type="text/css">
		.mpam-select-list-child {
			padding-left: 15px
		}
	</style>
</head>
<body onload="an_mc_init();">
<?php
if ( ! $an_mc_api_key ) {
	AN_MC_View::get_instance()->get_template( '/notice/change-settings', array( 'target' => 'target="_blank"' ) );
} elseif ( ! is_array( $an_mc_lists ) ) { ?>
	<p><?php esc_html_e( 'You need to configure Mailchimp settings first.', 'another-mailchimp-widget' ) ?></p>
<?php } else { ?>
	<form name="buttons" action="#">
		<label for="mpam_list_ids"><?php esc_html_e( 'Select lists and groups your users will be signed up to', 'another-mailchimp-widget' ); ?></label>
		<div class="mpmc-box-wrapper" id="mpmc-box-wrapper">
			<select multiple class="properties" style="min-height: 150px" size="8" id="mpam_list_ids" name="mpam_list_ids[]">
				<?php AN_MC_View::get_instance()->get_template( 'mailchimp-lists', array( 'lists' => $an_mc_lists, 'current_mailing_list' => array() ) ); ?>
			</select>
			<?php esc_html_e( 'Use ctrl/cmd key to select multiple options.', 'another-mailchimp-widget' ); ?>
		</div>
		<p>
			<label for="email_text"><?php esc_html_e( 'Email label', 'another-mailchimp-widget' ); ?></label>
			<input class="properties" id="email_text" name="email_text" value="<?php esc_attr_e( 'Your E-mail', 'another-mailchimp-widget' ); ?>"/>
		</p>
		<p>
			<label for="signup_text"><?php esc_html_e( 'Submit button label', 'another-mailchimp-widget' ); ?></label>
			<input class="properties" id="signup_text" name="signup_text" value="<?php esc_attr_e( 'Subscribe', 'another-mailchimp-widget' ); ?>"/>
		</p>
		<p>
			<input type="checkbox" class="checkbox" id="collect_first" name="collect_first"/>
			<label for="collect_first"><?php esc_html_e( 'Collect first name', 'another-mailchimp-widget' ); ?></label>
		</p>
		<p>
			<label for="first_name_text"><?php esc_html_e( 'First name label', 'another-mailchimp-widget' ); ?></label>
			<input class="properties" id="first_name_text" name="first_name_text" value="<?php esc_attr_e( 'First Name', 'another-mailchimp-widget' ); ?>"/>
		</p>
		<p>
			<input type="checkbox" class="checkbox" id="collect_last" name="collect_last"/>
			<label for="collect_last"><?php esc_html_e( 'Collect last name', 'another-mailchimp-widget' ); ?></label>
		</p>
		<p>
			<label for="last_name_text"><?php esc_html_e( 'Last name label', 'another-mailchimp-widget' ); ?></label>
			<input class="properties" id="last_name_text" name="last_name_text" value="<?php esc_attr_e( 'Last Name', 'another-mailchimp-widget' ); ?>"/>
		</p>
		<p>
			<input type="checkbox" class="checkbox" id="showplaceholder" name="showplaceholder" checked="checked"/>
			<label for="showplaceholder"><?php esc_html_e( 'Display labels as placeholders', 'another-mailchimp-widget' ); ?></label>
		</p>
		<p>
			<label for="success_message"><?php esc_html_e( 'Success message', 'another-mailchimp-widget' ); ?></label>
			<textarea class="properties" id="success_message" name="success_message"><?php esc_html_e( 'Thank you for joining our mailing list.', 'another-mailchimp-widget' ); ?></textarea>
		</p>
		<p>
			<label for="failure_message"><?php esc_html_e( 'Failure message', 'another-mailchimp-widget' ); ?></label>
			<textarea class="properties" id="failure_message" name="failure_message"><?php esc_html_e( 'There was a problem processing your submission.', 'another-mailchimp-widget' ); ?></textarea>
		</p>
		<p><input type="submit" id="insert" name="insert" value="<?php esc_attr_e( 'Insert', 'another-mailchimp-widget' ); ?>" onClick="an_mc_submit();"/></p>
	</form>
<?php } ?>
</body>
</html>