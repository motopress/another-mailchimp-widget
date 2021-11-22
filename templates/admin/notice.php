<?php
$class = empty( $class ) ? 'notice-success' : $class;

if ( empty( $data[ 'errorMessage' ] ) ) {
	$message = empty( $data[ 'message' ] ) ? esc_html__( 'Settings saved.', 'another-mailchimp-widget' ) : $data[ 'message' ];
} else {
	$message = $data[ 'errorMessage' ];
}

?>
<div class="notice <?php echo esc_attr( $class ); ?> is-dismissible">
	<p><strong><?php echo wp_kses_post( $message ); ?></strong></p>
</div>