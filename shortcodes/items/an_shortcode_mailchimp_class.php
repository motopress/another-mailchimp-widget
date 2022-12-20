<?php

/**
 * Class AN_Shortcode_MailChimp
 */
class AN_Shortcode_MailChimp {
	
	private $default_failure_message;
	private $default_button_text;
	private $successful_signup = false;
	private $subscribe_errors;
	private $an_mc_plugin;
	private $indexShortcode = 1;
	private $id_base = 'an_sh_mailchimp';
	private $option_name;
	
	/**
	 * AN_Shortcode_MailChimp constructor.
	 *
	 * @param $instance
	 * @param $index_sh
	 */
	public function __construct( $instance, $index_sh ) {
		
		$this->indexShortcode          = $index_sh;
		$this->option_name             = 'sh_' . $this->id_base;
		$this->default_failure_message = esc_html__( 'There was a problem processing your submission.', 'another-mailchimp-widget' );
		$this->default_signup_text     = esc_html__( 'Subscribe', 'another-mailchimp-widget' );
		$this->default_success_message = esc_html__( 'Thank you for joining our mailing list.', 'another-mailchimp-widget' );
		$this->an_mc_plugin            = AN_MC_Plugin::get_instance();
		
		add_action( 'parse_request', array( &$this, 'process_submission' ) );
		
		$instance2 = array( $this->indexShortcode => $instance );
		$this->save_settings( $instance2 );
	}
	
	/**
	 * Save settings
	 *
	 * @param $settings
	 */
	public function save_settings( $settings ) {
		$settings[ '_multishortcode' ] = 1;
		update_option( $this->option_name, $settings );
	}
	
	/**
	 * Get MailChimp form
	 *
	 * @param $instance
	 *
	 * @return int
	 */
	public function get_MailChimp( $instance ) {
		AN_MC_Plugin::get_instance()->add_scripts();
		AN_MC_Plugin::get_instance()->add_style();
		$api_key = AN_MC_Plugin::get_instance()->is_have_api_key();
		
		if ( $this->successful_signup ) {
			echo wp_kses_post( $this->signup_success_message );
		} elseif ( ! $api_key ) {
			AN_MC_View::get_instance()->get_template( '/notice/change-settings' );
		} elseif ( empty( $instance[ 'current_mailing_list' ] ) ) {
			AN_MC_View::get_instance()->get_template( '/notice/empty-list' );
		} else { ?>
			<div class="an_mailchimp_wrapper">
				<?php $data = array(
					'id'  => $this->id_base . '_form_' . $this->indexShortcode,
					'url' => sanitize_text_field( wp_unslash( $_SERVER[ 'REQUEST_URI' ] ) )
				) ?>
				<form action="<?php echo esc_url( sanitize_text_field( wp_unslash( $_SERVER[ 'REQUEST_URI' ] ) ) ); ?>"
				      data-id="<?php echo esc_attr( $data[ 'id' ] ); ?>"
				      data-url="<?php echo esc_attr( $data[ 'url' ] ); ?>"
				      id="<?php echo esc_attr( $data[ 'id' ] ); ?>" method="post" class="<?php
				if ( $instance[ 'showplaceholder' ] ) {
					echo 'mailchimp_form_placeholder ';
				}
				if ( ! $instance[ 'collect_first' ] && ! $instance[ 'collect_last' ] ) {
					echo 'mailchimp_form_simple';
				} ?>">
					<?php echo wp_kses_post( $this->subscribe_errors );
					if ( $instance[ 'collect_first' ] ) {
						if ( $instance[ 'showplaceholder' ] ) { ?>
							<p>
								<label>
									<input placeholder="<?php echo esc_attr( $instance[ 'first_name_text' ] ); ?>" type="text" id="<?php echo esc_attr( $this->id_base . '_first_name' ); ?>" required name="<?php echo esc_attr( $this->id_base . '_first_name' ); ?>"/>
								</label>
							</p>
						<?php } else { ?>
							<p>
								<label for="<?php echo esc_attr( $this->id_base . '_first_name' ); ?>"><?php echo esc_html( $instance[ 'first_name_text' ] ); ?></label>
								<input type="text" id="<?php echo esc_attr( $this->id_base . '_first_name' ); ?>" required name="<?php echo esc_attr( $this->id_base . '_first_name' ); ?>"/>
							</p>
						<?php }
					}
					if ( $instance[ 'collect_last' ] ) {
						if ( $instance[ 'showplaceholder' ] ) { ?>
							<p>
								<label>
									<input placeholder="<?php echo esc_attr( $instance[ 'last_name_text' ] ); ?>" required type="text" id="<?php echo esc_attr( $this->id_base . '_last_name' ); ?>" name="<?php echo esc_attr( $this->id_base . '_last_name' ); ?>"/>
								</label>
							</p>
						<?php } else { ?>
							<p>
								<label for="<?php echo esc_attr( $this->id_base . '_last_name' ); ?>"><?php echo esc_html( $instance[ 'last_name_text' ] ); ?></label>
								<input type="text" id="<?php echo esc_attr( $this->id_base . '_last_name' ); ?>" required name="<?php echo esc_attr( $this->id_base . '_last_name' ); ?>"/>
							</p>
						<?php }
					} ?>
					<input type="hidden" name="widget_error" value="<?php echo esc_attr( $instance[ 'failure_message' ] ); ?>"/>
					<input type="hidden" name="widget_success" value="<?php echo esc_attr( $instance[ 'success_message' ] ); ?>"/>
					<input type="hidden" name="widget_id" value="<?php echo esc_attr( $this->id_base ); ?>"/>
					<input type="hidden" name="widget_number" value="<?php echo esc_attr( $this->indexShortcode ); ?>"/>
					<input type="hidden" name="mp_am_type" value="shortcode"/>
					<input type="hidden" name="shortcode_mailing_list_id" value="<?php echo esc_attr( $instance[ 'current_mailing_list' ] ); ?>"/>
					<?php if ( $instance[ 'showplaceholder' ] ) { ?>
						<p>
							<label><input placeholder="<?php echo esc_attr( $instance[ 'email_text' ] ); ?>" required id="<?php echo esc_attr( $this->id_base ); ?>_email_<?php echo esc_attr( $this->indexShortcode ); ?>" type="email" name="<?php echo esc_attr( $this->id_base ); ?>_email"/></label>
						</p>
					<?php } else { ?>
						<p>
							<label for="<?php echo esc_attr( $this->id_base ); ?>_email_<?php echo esc_attr( $this->indexShortcode ); ?>"><?php echo esc_html( $instance[ 'email_text' ] ); ?></label>
							<input id="<?php echo esc_attr( $this->id_base ); ?>_email_<?php echo esc_attr( $this->indexShortcode ); ?>" required type="email" name="<?php echo esc_attr( $this->id_base . '_email' ); ?>"/>
						</p>
					<?php } ?>
					<p><input class="mpam-submit button" type="submit" name="<?php echo esc_attr( strtolower( $instance[ 'signup_text' ] ) ); ?>" value="<?php echo esc_attr( $instance[ 'signup_text' ] ); ?>"/></p>
				</form>
			</div>
		<?php }
	}
	
	/**
	 * Update shortcode instance
	 *
	 * @param $new_instance
	 * @param $old_instance
	 *
	 * @return mixed
	 */
	public function update( $new_instance, $old_instance ) {
		
		$instance                           = $old_instance;
		$instance[ 'collect_first' ]        = ! empty( $new_instance[ 'collect_first' ] );
		$instance[ 'collect_last' ]         = ! empty( $new_instance[ 'collect_last' ] );
		$instance[ 'showplaceholder' ]      = ! empty( $new_instance[ 'showplaceholder' ] );
		$instance[ 'current_mailing_list' ] = esc_attr( $new_instance[ 'current_mailing_list' ] );
		$instance[ 'failure_message' ]      = esc_attr( $new_instance[ 'failure_message' ] );
		$instance[ 'signup_text' ]          = esc_attr( $new_instance[ 'signup_text' ] );
		$instance[ 'email_text' ]           = esc_attr( $new_instance[ 'email_text' ] );
		$instance[ 'first_name_text' ]      = esc_attr( $new_instance[ 'first_name_text' ] );
		$instance[ 'last_name_text' ]       = esc_attr( $new_instance[ 'last_name_text' ] );
		$instance[ 'success_message' ]      = esc_attr( $new_instance[ 'success_message' ] );
		
		return $instance;
	}
	
	/**
	 * Hash mailing list
	 *
	 * @return string
	 */
	private function hash_mailing_list_id() {
		$options = get_option( $this->option_name );
		$hash    = md5( $options[ $this->indexShortcode ][ 'current_mailing_list' ] );
		
		return $hash;
	}
}
