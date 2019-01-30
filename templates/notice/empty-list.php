<?php
if ( current_user_can( 'manage_options' ) ) { ?>
	<p><?php _e( 'Select at least one MailChim list.', 'another-mailchimp-widget' ) ?></p>
<?php } else { ?>
	<p><?php _e( 'There is an error in subscription form. Contact website administrator please.', 'another-mailchimp-widget' ) ?></p>
<?php }