<?php

if ( current_user_can( 'manage_options' ) ) { ?>
	<p><?php esc_html_e( 'You need to configure Mailchimp settings first.', 'another-mailchimp-widget' ) ?></p>
<?php } else { ?>
	<p><?php esc_html_e( 'There is an error in subscription form. Contact website administrator please.', 'another-mailchimp-widget' ) ?></p>
<?php }