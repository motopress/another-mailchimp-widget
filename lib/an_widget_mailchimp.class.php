<?php require_once __DIR__ . '/Default_MC.php';

/**
 * Class AN_Widget_MailChimp
 */
class AN_Widget_MailChimp extends Widget_Default_AN_MC {
	
	private $default_failure_message;
	public $default_signup_text;
	public $default_success_message;
	private $successful_signup = false;
	private $subscribe_errors = '';
	public $signup_success_message = '';
	private $an_mc_plugin;
	private $lists;
	
	/**
	 * AN_Widget_MailChimp constructor.
	 */
	public function __construct() {
		$this->default_failure_message = esc_html__( 'There was a problem processing your submission.', 'another-mailchimp-widget' );
		$this->default_signup_text     = esc_html__( 'Subscribe', 'another-mailchimp-widget' );
		$this->default_success_message = esc_html__( 'Thank you for joining our mailing list.', 'another-mailchimp-widget' );
		
		$this->setPrefix( 'an' );
		$this->setClassName( 'widget_an_mailchimp' );
		$this->setName( esc_html__( 'Mailchimp Widget', 'another-mailchimp-widget' ) );
		$this->setDescription( esc_html__( 'Displays a sign-up form for a Mailchimp mailing list.', 'another-mailchimp-widget' ) );
		$this->setIdSuffix( 'mailchimp' );
		$this->an_mc_plugin = AN_MC_Plugin::get_instance();

		parent::__construct();
	}
	
	/**
	 * Widget instance
	 *
	 * @param array $args
	 * @param array $instance
	 *
	 * @return int
	 */
	public function widget( $args, $instance ) {
		$instance = AN_MC_Form::normalize( $instance );
		$args = wp_parse_args( is_array( $args ) ? $args : array(), array(
			'before_widget' => '', 'after_widget' => '', 'before_title' => '', 'after_title' => '',
		) );
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( '' !== $instance['title'] ) {
			echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		AN_MC_Form::render( $instance, $this->id_base, wp_unique_id( $this->id_base . '_form-' . $this->number . '-' ), 'widget' );
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Widget form
	 *
	 * @param array $instance
	 *
	 * @return void
	 */
	public function form( $instance ) {
		$instance = AN_MC_Form::normalize( $instance );
		$api_key = AN_MC_Plugin::get_instance()->is_have_api_key();
		
		if ( ! $api_key ) {
			echo AN_MC_View::get_instance()->get_template_html( '/notice/invalid-api-key' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			
			$mcapi       = $this->an_mc_plugin->get_mcapi();
			$this->lists = $mcapi->get_account_subscribe_lists();
			$defaults    = array(
				'failure_message'      => $this->default_failure_message,
				'signup_text'          => $this->default_signup_text,
				'first_name_text'      => esc_html__( 'First Name', 'another-mailchimp-widget' ),
				'last_name_text'       => esc_html__( 'Last Name', 'another-mailchimp-widget' ),
				'email_text'           => esc_html__( 'Your E-mail', 'another-mailchimp-widget' ),
				'success_message'      => $this->default_success_message,
				'collect_first'        => false,
				'collect_last'         => false,
				'old_markup'           => false,
				'current_mailing_list' => array(),
				'showplaceholder'      => true
			);
			
			
			$vars = wp_parse_args( $instance, $defaults );
			extract( $vars, EXTR_SKIP );
			$title = empty( $instance[ 'title' ] ) ? '' : $instance[ 'title' ];
			
			$current_mailing_list = $this->get_current_selected_list( $current_mailing_list );
			
			?>
			<div class="mpam-widget-wrapper">
				<style type="text/css">
					.mpam-widget-wrapper .mpam-select-list-child {
						padding-left: 15px
					}
				</style>
				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'another-mailchimp-widget' ); ?></label>
					<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>"/>
				</p>
				<?php if ( ! is_array( $this->lists ) || isset( $this->lists['error'] ) ) { ?>
					<p><?php esc_html_e( 'You need to configure Mailchimp settings first.', 'another-mailchimp-widget' ) ?></p>
				<?php } ?>
				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'current_mailing_list' ) ); ?>"><?php esc_html_e( 'Select lists and groups your users will be signed up to', 'another-mailchimp-widget' ); ?></label>
					<select multiple class="widefat" style="min-height: 150px" size="10" id="<?php echo esc_attr( $this->get_field_id( 'current_mailing_list' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'current_mailing_list' ) . '[]' ); ?>">
						<?php AN_MC_View::get_instance()->get_template( 'mailchimp-lists', array( 'lists' => $this->lists, 'current_mailing_list' => $current_mailing_list ) ); ?>
					</select>
					<?php esc_html_e( 'Use ctrl/cmd key to select multiple options.', 'another-mailchimp-widget' ); ?>
				</p>
				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'email_text' ) ); ?>"><?php esc_html_e( 'Email label', 'another-mailchimp-widget' ); ?></label>
					<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( 'email_text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'email_text' ) ); ?>" value="<?php echo esc_attr( $email_text ); ?>"/>
				</p>
				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'signup_text' ) ); ?>"><?php esc_html_e( 'Submit button label', 'another-mailchimp-widget' ); ?></label>
					<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( 'signup_text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'signup_text' ) ); ?>" value="<?php echo esc_attr( $signup_text ); ?>"/>
				</p>
				<p>
					<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'collect_first' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'collect_first' ) ); ?>" <?php echo checked( $collect_first, true, false ); ?> />
					<label for="<?php echo esc_attr( $this->get_field_id( 'collect_first' ) ); ?>"><?php esc_html_e( 'Collect first name', 'another-mailchimp-widget' ); ?></label>
				</p>
				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'first_name_text' ) ); ?>"><?php esc_html_e( 'First name label', 'another-mailchimp-widget' ); ?></label>
					<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( 'first_name_text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'first_name_text' ) ); ?>" value="<?php echo esc_attr( $first_name_text ); ?>"/>
				</p>
				<p>
					<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'collect_last' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'collect_last' ) ); ?>" <?php echo checked( $collect_last, true, false ); ?> />
					<label for="<?php echo esc_attr( $this->get_field_id( 'collect_last' ) ); ?>"><?php esc_html_e( 'Collect last name', 'another-mailchimp-widget' ); ?></label>
				</p>
				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'last_name_text' ) ); ?>"><?php esc_html_e( 'Last name label', 'another-mailchimp-widget' ); ?></label>
					<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( 'last_name_text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'last_name_text' ) ); ?>" value="<?php echo esc_attr( $last_name_text ); ?>"/>
				</p>
				<p>
					<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'showplaceholder' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'showplaceholder' ) ); ?>" <?php echo checked( $showplaceholder, true, false ); ?> />
					<label for="<?php echo esc_attr( $this->get_field_id( 'showplaceholder' ) ); ?>"><?php esc_html_e( 'Display labels as placeholders', 'another-mailchimp-widget' ); ?></label>
				</p>
				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'success_message' ) ); ?>"><?php esc_html_e( 'Success message', 'another-mailchimp-widget' ); ?></label>
					<textarea type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'success_message' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'success_message' ) ); ?>"><?php echo esc_textarea( $success_message) ; ?></textarea>
				</p>
				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'failure_message' ) ); ?>"><?php esc_html_e( 'Failure message', 'another-mailchimp-widget' ); ?></label>
					<textarea type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'failure_message' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'failure_message' ) ); ?>"><?php echo esc_textarea( $failure_message ); ?></textarea>
				</p>
			</div>
			<?php
		}
	}
	
	/**
	 * @param $current_mailing_list
	 *
	 * @return array
	 */
	protected function get_current_selected_list( $current_mailing_list ) {
		if ( empty( $current_mailing_list ) ) {
			$selected_lists = array();
		} elseif ( ! empty( $current_mailing_list ) && is_string( $current_mailing_list ) ) {
			$selected_lists = array( '0' => $current_mailing_list );
		} else {
			$selected_lists = $current_mailing_list;
		}
		
		return $selected_lists;
	}
	
	/**
	 * Process submission
	 *
	 * @return bool
	 */
	public function process_submission() {
		AN_MC_Form::process_submission();
	}

	/**
	 * Request mailing list/maybe widget data
	 *
	 * @param $type
	 *
	 * @param $submission_type
	 * @param $number
	 *
	 * @return mixed|null
	 */
	protected function get_request_mailing_list( $type, $submission_type, $number ) {
		$lists = array();
		if ( $submission_type == 'shortcode' ) {
			$lists = filter_input( $type, $submission_type . '_mailing_list_id', FILTER_DEFAULT );
		} elseif ( $submission_type == 'widget' ) {
			$lists = $this->get_current_mailing_list_id( $number );
		}
		
		return $lists;
	}
	
	/**
	 *
	 * Get current mailing list
	 *
	 * @param null $number
	 *
	 * @return null
	 */
	private function get_current_mailing_list_id( $number = null ) {
		$options = get_option( $this->option_name );
		if ( isset( $options[ $number ][ 'current_mailing_list' ] ) ) {
			return $options[ $number ][ 'current_mailing_list' ];
		}
		
		return null;
	}
	
	/**
	 * Prepare mailChimp
	 *
	 * @param $mailchimp_data
	 * @param $request_data
	 *
	 * @return mixed
	 */
	public function prepare_mailchimp( $mailchimp_data, $request_data ) {
		$mailchimp_data = is_array( $mailchimp_data ) ? $mailchimp_data : array();
		$mailchimp_data['list_ids'] = array();
		$selection = is_array( $request_data ) && isset( $request_data['widget_mailing_list_id'] ) ? $request_data['widget_mailing_list_id'] : array();
		foreach ( AN_MC_Form::lists( $selection ) as $destination ) {
			$parts = explode( '/', $destination );
			if ( ! isset( $mailchimp_data['list_ids'][ $parts[0] ] ) ) {
				$mailchimp_data['list_ids'][ $parts[0] ] = array();
			}
			if ( isset( $parts[1] ) ) {
				$mailchimp_data['list_ids'][ $parts[0] ][ $parts[1] ] = true;
			}
		}
		$options = AN_MC_Plugin::get_instance()->get_options();
		$mailchimp_data['apikey'] = isset( $options['api-key'] ) ? $options['api-key'] : '';
		$mailchimp_data['user_name'] = '';
		return $mailchimp_data;
	}

	/**
	 * Update widget instance
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$new_instance = is_array( $new_instance ) ? $new_instance : array();
		foreach ( array( 'collect_first', 'collect_last', 'showplaceholder' ) as $key ) {
			$new_instance[ $key ] = ! empty( $new_instance[ $key ] );
		}
		return AN_MC_Form::normalize( $new_instance );
	}

	/**
	 * Hash mailing list
	 *
	 * @return string
	 */
	private function hash_mailing_list_id() {
		$options              = get_option( $this->option_name );
		$current_mailing_list = isset( $options[ $this->number ][ 'current_mailing_list' ] ) ? $options[ $this->number ][ 'current_mailing_list' ] : array();
		
		if ( is_array( $current_mailing_list ) ) {
			$hash = md5( serialize( $current_mailing_list ) );
		} elseif ( is_string( $current_mailing_list ) ) {
			$hash = md5( $current_mailing_list );
		} else {
			$hash = '';
		}
		
		return $hash;
	}
}
