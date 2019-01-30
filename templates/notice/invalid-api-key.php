<?php
if ( current_user_can( 'manage_options' ) ) { ?>
	<p><?php _e( 'MailChimp API key is invalid.', 'another-mailchimp-widget' ) ?></p>
<?php }
