<?php require_once 'Default_MC.php';

/**
 * Class AN_Widget_MailChimp
 */
class AN_Widget_MailChimp extends Widget_Default_AN_MC {
	
	private $default_failure_message;
	private $default_button_text;
	private $successful_signup = false;
	private $subscribe_errors;
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
		
		
		add_action( 'parse_request', array( &$this, 'process_submission' ) );
		
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
		AN_MC_Plugin::get_instance()->add_scripts();
		AN_MC_Plugin::get_instance()->add_style();
		
		$api_key = AN_MC_Plugin::get_instance()->is_have_api_key();
		
		$defaults = array(
			'failure_message' => $this->default_failure_message,
			'signup_text'     => $this->default_signup_text,
			'success_message' => $this->default_success_message,
			'first_name_text' => esc_html__( 'First Name', 'another-mailchimp-widget' ),
			'last_name_text'  => esc_html__( 'Last Name', 'another-mailchimp-widget' ),
			'email_text'      => esc_html__( 'Your E-mail', 'another-mailchimp-widget' ),
			'collect_first'   => false,
			'collect_last'    => false,
			'old_markup'      => false,
			'showplaceholder' => true,
		);

		extract( wp_parse_args( $instance, $defaults ) );
		extract( $args );

		echo $before_widget; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		echo empty( $instance[ 'title' ] ) ? '' : $before_title . esc_html( $instance[ 'title' ] ) . $after_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( $this->successful_signup ) {
			echo wp_kses_post( $this->signup_success_message );
		} elseif ( ! $api_key ) {
			AN_MC_View::get_instance()->get_template( '/notice/change-settings' );
		} elseif ( empty( $instance[ 'current_mailing_list' ] ) ) {
			AN_MC_View::get_instance()->get_template( '/notice/empty-list' );
		} else {
			$data = array(
				'id'  => $this->id_base . '_form-' . $this->number,
				'url' => sanitize_text_field( wp_unslash( $_SERVER[ 'REQUEST_URI' ] ) )
			)
			?>
			<form action="<?php echo esc_url( sanitize_text_field( wp_unslash( $_SERVER[ 'REQUEST_URI' ] ) ) ); ?>"
			      data-id="<?php echo esc_attr( $data[ 'id' ] ); ?>"
			      data-url="<?php echo esc_url( $data[ 'url' ] ); ?>"

			      id="<?php echo esc_attr( $data[ 'id' ] ); ?>" method="post" class="<?php
			if ( $showplaceholder ) {
				echo 'mailchimp_form_placeholder ';
			}
			if ( ! $collect_first && ! $collect_last ) {
				echo 'mailchimp_form_simple';
			}
			?>">
				<?php echo wp_kses_post( $this->subscribe_errors );
				if ( $collect_first ) { ?>
					<?php if ( $showplaceholder ) { ?>
						<p>
							<label>
								<input placeholder="<?php echo esc_attr( $first_name_text ); ?>" type="text" id="<?php echo esc_attr( $this->id_base . '_first_name' ); ?>" required name="<?php echo esc_attr( $this->id_base . '_first_name' ); ?>"/>
							</label>
						</p>
					<?php } else { ?>
						<p>
							<label for="<?php echo esc_attr( $this->id_base . '_first_name' . $this->number ); ?>"><?php echo esc_html( $first_name_text ); ?></label>
							<input type="text" id="<?php echo esc_attr( $this->id_base . '_first_name' . $this->number ); ?>" required name="<?php echo esc_attr( $this->id_base . '_first_name' ); ?>"/>
						</p>
						<?php
					}
				}
				if ( $collect_last ) {
					if ( $showplaceholder ) { ?>
						<p>
							<label>
								<input placeholder="<?php echo esc_attr( $last_name_text ); ?>" type="text" id="<?php echo esc_attr( $this->id_base . '_last_name' ); ?>" required name="<?php echo esc_attr( $this->id_base . '_last_name' ); ?>"/>
							</label>
						</p>
					<?php } else { ?>
						<p>
							<label for="<?php echo esc_attr( $this->id_base . '_last_name' . $this->number ); ?>"><?php echo esc_html( $last_name_text ); ?></label>
							<input type="text" id="<?php echo esc_attr( $this->id_base . '_last_name' . $this->number ); ?>" required name="<?php echo esc_attr( $this->id_base . '_last_name' ); ?>"/>
						</p>
						<?php
					}
				} ?>
				<input type="hidden" name="widget_error" value="<?php echo esc_attr( $failure_message ); ?>"/>
				<input type="hidden" name="widget_success" value="<?php echo esc_attr( $success_message ); ?>"/>
				<input type="hidden" name="widget_id" value="<?php echo esc_attr( $this->id_base ); ?>"/>
				<input type="hidden" name="widget_number" value="<?php echo esc_attr( $this->number ); ?>"/>
				<input type="hidden" name="mp_am_type" value="widget"/>
				<?php if ( $showplaceholder ) { ?>
					<p>
						<label>
							<input placeholder="<?php echo esc_attr( $email_text ); ?>" id="<?php echo esc_attr( $this->id_base . '-email-' . $this->number ); ?>" type="email" required name="<?php echo esc_attr( $this->id_base . '_email' ); ?>"/>
						</label>
					</p>
				<?php } else { ?>
					<p>
						<label for="<?php echo esc_attr( $this->id_base . '-email-' . $this->number ); ?>"><?php echo esc_html( $email_text ); ?></label>
						<input required id="<?php echo esc_attr( $this->id_base . '-email-' . $this->number ); ?>" type="email" name="<?php echo esc_attr( $this->id_base . '_email' ); ?>"/>
					</p>
				<?php } ?>
				<p>
					<input class="mpam-submit button" type="submit" name="<?php echo esc_attr( strtolower( $signup_text ) ); ?>" value="<?php echo esc_attr( $signup_text ); ?>"/>
				</p>
			</form>
		
		<?php }
		
		echo $after_widget; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	
	/**
	 * Widget form
	 *
	 * @param array $instance
	 *
	 * @return void
	 */
	public function form( $instance ) {
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
			extract( $vars );
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
				<?php if ( ! is_array( $this->lists ) ) { ?>
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
		$data = array();
		$type = ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) ? INPUT_POST : INPUT_GET;
		
		$data[ 'widgetId' ]               = filter_input( $type, 'widget_id' );
		$data[ 'submission_type' ]        = filter_input( $type, 'mp_am_type' );
		$data[ 'widget_number' ]          = filter_input( $type, 'widget_number' );
		$data[ 'widget_error' ]           = filter_input( $type, 'widget_error' );
		$data[ 'widget_success' ]         = filter_input( $type, 'widget_success' );
		$data[ 'widget_mailing_list_id' ] = $this->get_request_mailing_list( $type, $data[ 'submission_type' ], $data[ 'widget_number' ] );
		
		$errorMessage   = ( isset( $_GET[ 'widget_error' ] ) && ! empty( $_GET[ 'widget_error' ] ) ) ?
			sanitize_text_field( wp_unslash( $_GET[ 'widget_error' ] ) ) :
			esc_html__( 'There was a problem processing your submission.', 'another-mailchimp-widget' );

		$successMessage = ( isset( $_GET[ 'widget_success' ] ) && ! empty( $_GET[ 'widget_success' ] ) ) ?
			sanitize_text_field( wp_unslash( $_GET[ 'widget_success' ] ) ) :
			esc_html__( 'Thank you for joining our mailing list.', 'another-mailchimp-widget' );
		
		if ( $data[ 'widgetId' ] ) {
			header( "Content-Type: application/json" );
			
			$result     = array( 'success' => false, 'error' => $errorMessage );
			$merge_vars = array();
			$email      = filter_input( $type, $data[ 'widgetId' ] . '_email', FILTER_VALIDATE_EMAIL );
			
			if ( ! $email ) { //Use WordPress's built-in is_email function to validate input.
				$response = json_encode( $result ); //If it's not a valid email address, just encode the defaults.
			} else {
				
				$mcapi = $this->an_mc_plugin->get_mcapi();
				
				if ( false == $this->an_mc_plugin ) {
					
					$response = json_encode( $result );
				} else {
					$merge_vars[ 'merge_fields' ] = array();
					
					$first_name = filter_input( $type, $data[ 'widgetId' ] . '_first_name' );
					$last_name  = filter_input( $type, $data[ 'widgetId' ] . '_last_name' );
					
					if ( $first_name ) {
						$merge_vars[ 'merge_fields' ][ 'FNAME' ] = $first_name;
					}
					
					if ( $last_name ) {
						$merge_vars[ 'merge_fields' ][ 'LNAME' ] = $last_name;
					}
					
					$merge_vars = $this->prepare_mailchimp( $merge_vars, $data );
					
					$mcapi->add_to_list( $email, $merge_vars );
					
					if ( $mcapi->errors ) {
						$result   = array( 'success' => false, 'error' => $errorMessage );
						$response = json_encode( $result );
					} else {
						$result[ 'success' ]         = true;
						$result[ 'error' ]           = '';
						$result[ 'success_message' ] = $successMessage;
						$response                    = json_encode( $result );
					}
				}
			}
			
			exit( $response ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
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
		$mailchimp_data[ 'list_ids' ] = array();
		
		if ( ! empty( $request_data[ 'widget_mailing_list_id' ] ) ) {
			
			$request_data[ 'widget_mailing_list_id' ];
			if ( is_string( $request_data[ 'widget_mailing_list_id' ] ) ) {
				$temp_list_ids = explode( ',', $request_data[ 'widget_mailing_list_id' ] );
			} elseif ( is_array( $request_data[ 'widget_mailing_list_id' ] ) ) {
				$temp_list_ids = $request_data[ 'widget_mailing_list_id' ];
			}
			
			foreach ( $temp_list_ids as $list ) {
				$list = explode( '/', $list );
				if ( is_array( $list ) ) {
					
					$list_id     = $list[ 0 ];
					$interest_id = empty( $list[ 1 ] ) ? '' : $list[ 1 ];
					
					if ( ! isset( $mailchimp_data[ 'list_ids' ][ $list_id ] ) ) {
						$mailchimp_data[ 'list_ids' ][ $list_id ] = array();
					}
					
					if ( ! empty( $interest_id ) && ! array_key_exists( $interest_id, $mailchimp_data[ 'list_ids' ][ $list_id ] ) ) {
						$mailchimp_data[ 'list_ids' ][ $list_id ][ $interest_id ] = true;
					}
				}
				
			}
		}
		
		$options                       = AN_MC_Plugin::get_instance()->get_options();
		$mailchimp_data[ 'apikey' ]    = $options[ 'api-key' ];
		$mailchimp_data[ 'user_name' ] = '';
		
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
		$instance                           = $old_instance;
		$instance[ 'collect_first' ]        = ! empty( $new_instance[ 'collect_first' ] );
		$instance[ 'collect_last' ]         = ! empty( $new_instance[ 'collect_last' ] );
		$instance[ 'showplaceholder' ]      = ! empty( $new_instance[ 'showplaceholder' ] );
		$instance[ 'current_mailing_list' ] = empty( $new_instance[ 'current_mailing_list' ] ) ? array() : $new_instance[ 'current_mailing_list' ];
		$instance[ 'failure_message' ]      = esc_attr( $new_instance[ 'failure_message' ] );
		$instance[ 'signup_text' ]          = esc_attr( $new_instance[ 'signup_text' ] );
		$instance[ 'first_name_text' ]      = esc_attr( $new_instance[ 'first_name_text' ] );
		$instance[ 'last_name_text' ]       = esc_attr( $new_instance[ 'last_name_text' ] );
		$instance[ 'email_text' ]           = esc_attr( $new_instance[ 'email_text' ] );
		$instance[ 'success_message' ]      = esc_attr( $new_instance[ 'success_message' ] );
		$instance[ 'title' ]                = esc_attr( $new_instance[ 'title' ] );
		
		return $instance;
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

function an_widget_mailchimp_class_widgets_init() {
	register_widget( "AN_Widget_MailChimp" );
}

add_action( 'widgets_init', 'an_widget_mailchimp_class_widgets_init' );