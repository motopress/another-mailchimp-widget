<?php

/** Shortcode form renderer. Configuration stays in the signed form, not options. */
class AN_Shortcode_MailChimp {
	public $default_signup_text;
	public $default_success_message;
	private $indexShortcode;
	private $id_base = 'an_sh_mailchimp';

	public function __construct( $instance, $index_sh ) {
		$this->indexShortcode = $index_sh;
		$this->default_signup_text = __( 'Subscribe', 'another-mailchimp-widget' );
		$this->default_success_message = __( 'Thank you for joining our mailing list.', 'another-mailchimp-widget' );
	}

	/** Retained for callers explicitly persisting legacy settings; rendering never calls it. */
	public function save_settings( $settings ) {
		$settings = is_array( $settings ) ? $settings : array();
		$settings['_multishortcode'] = 1;
		update_option( 'sh_' . $this->id_base, $settings );
	}

	public function get_MailChimp( $instance ) {
		echo '<div class="an_mailchimp_wrapper">';
		AN_MC_Form::render( $instance, $this->id_base, wp_unique_id( $this->id_base . '_form_' . $this->indexShortcode . '-' ), 'shortcode' );
		echo '</div>';
	}

	public function update( $new_instance, $old_instance ) {
		return AN_MC_Form::normalize( $new_instance );
	}
}
