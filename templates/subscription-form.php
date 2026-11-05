<?php
/** Shared widget/shortcode markup. Names stay compatible; IDs are unique per render. */
$form_class = $instance['showplaceholder'] ? 'mailchimp_form_placeholder ' : '';
if ( ! $instance['collect_first'] && ! $instance['collect_last'] ) {
	$form_class .= 'mailchimp_form_simple';
}
?>
<form data-error="<?php echo esc_attr( $instance['failure_message'] ); ?>" action="<?php echo esc_url( home_url( '/' ) ); ?>" data-id="<?php echo esc_attr( $form_id ); ?>" data-url="<?php echo esc_url( home_url( '/' ) ); ?>" id="<?php echo esc_attr( $form_id ); ?>" method="post" class="<?php echo esc_attr( $form_class ); ?>">
	<?php echo AN_MC_Form::fields( $instance, $id_base, $type ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php foreach ( array( 'first_name', 'last_name', 'email' ) as $field ) :
		if ( 'first_name' === $field && ! $instance['collect_first'] || 'last_name' === $field && ! $instance['collect_last'] ) {
			continue;
		}
		$label = $instance[ $field . '_text' ];
		$field_id = $form_id . '_' . $field;
		?>
		<p>
			<label for="<?php echo esc_attr( $field_id ); ?>"><?php if ( ! $instance['showplaceholder'] ) { echo esc_html( $label ); } ?></label>
			<input id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $id_base . '_' . $field ); ?>" type="<?php echo 'email' === $field ? 'email' : 'text'; ?>" required aria-label="<?php echo esc_attr( $label ); ?>"<?php if ( $instance['showplaceholder'] ) : ?> placeholder="<?php echo esc_attr( $label ); ?>"<?php endif; ?>/>
		</p>
	<?php endforeach; ?>
	<p><input class="mpam-submit button" type="submit" value="<?php echo esc_attr( $instance['signup_text'] ); ?>"/></p>
</form>
