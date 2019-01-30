<?php
$class = empty( $class ) ? 'notice-success' : $class;

if ( empty( $data[ 'errorMessage' ] ) ) {
	$message = empty( $data[ 'message' ] ) ? __( 'Settings saved.', 'another-mailchimp-widget' ) : $data[ 'message' ];
} else {
	$message = $data[ 'errorMessage' ];
}

?>
<div class="notice <?php echo $class ?> is-dismissible">
	<p><strong><?php echo $message ?></strong></p>
</div>