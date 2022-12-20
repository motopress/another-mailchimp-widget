<?php
if ( current_user_can( 'manage_options' ) ) { ?>
	<p><?php esc_html_e( 'Select at least one MailChim list.', 'another-mailchimp-widget' ) ?></p>
<?php } else { ?>
	<p><?php esc_html_e( 'There is an error in subscription form. Contact website administrator please.', 'another-mailchimp-widget' ) ?></p>
<?php }