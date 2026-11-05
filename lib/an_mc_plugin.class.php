<?php

/**
 * Class AN_MC_Plugin
 */
class AN_MC_Plugin {
	private static $instance;
	private static $mcapi;
	private static $name = 'AN_MC_Plugin';
	private static $prefix = 'ns_mc';
	private static $public_option = 'no';
	private static $textdomain = 'another-mailchimp-widget';
	private $options;
	private $donate_link = '';
	
	/**
	 * AN_MC_Plugin constructor.
	 */
	private function __construct() {

		// Fetch the options, and, if they haven't been set up yet, display a notice to the user.
		$this->get_options();
	}
	
	/**
	 * Load text domain
	 */
	public function load_text_domain() {
		load_plugin_textdomain( self::$textdomain, null, str_replace( 'lib', 'languages', dirname( plugin_basename( __FILE__ ) ) ) );
	}
	
	/**
	 * Get option
	 *
	 * @return mixed
	 */
	public function get_options() {
		$options = get_option( self::$prefix . '_options' );
		$this->options = is_array( $options ) ? $options : array();
		
		return $this->options;
	}
	
	/**
	 * @return AN_MC_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 *  Init action
	 */
	public function init_action() {
		add_action( 'init', array( $this, 'load_text_domain' ), 0 );
		add_action( 'parse_request', array( 'AN_MC_Form', 'process_submission' ) );
		add_action( 'update_option_' . self::$prefix . '_options', array( $this, 'credentials_changed' ), 10, 2 );
		// Set up the settings.
		add_action( 'admin_init', array( &$this, 'register_settings' ) );
		// Set up the administration page.
		add_action( 'admin_menu', array( &$this, 'set_up_admin_page' ) );
		add_action( 'widgets_init', array( $this, 'widgets_init' ) );
		add_action( 'admin_notices', array( $this, 'admin_notice' ), 10, 1 );

		add_action( 'plugin_action_links_' . plugin_basename( AN_MC_PLUGIN_FILE ), array( $this, 'action_links' ) );
	}

	public function action_links( $links ) {

		$settings_page_url = admin_url( 'options-general.php?page=another-mailchimp-widget/lib/an_mc_plugin.class.php' );

		$plugin_links = array(
			'<a href="' . esc_url( $settings_page_url ) . '">' . esc_html__( 'Settings', 'another-mailchimp-widget' ) . '</a>'
		);

		return array_merge( $links, $plugin_links );
	}

	public function widgets_init() {
		register_widget("AN_Widget_MailChimp");
	}
	
	/**
	 * Admin notice
	 *
	 */
	public function admin_notice() {
		$screen = get_current_screen();
		/*
		 * notice-error – error message displayed with a red border
		 * notice-warning – warning message displayed with a yellow border
		 * notice-success – success message displayed with a green border
		 * notice-info – info message displayed with a blue border
		*/
		
		if ( ! is_wp_error( $screen ) && ! empty( $screen ) && ( $screen->id === 'settings_page_another-mailchimp-widget/lib/an_mc_plugin.class' ) ) {
			
			$last_query = get_transient( 'mp-am-last-action-data' );
			
			if ( is_array( $last_query ) && ! empty( $last_query[ 'mpam_sync' ] ) ) {
				if ( ! empty( $last_query[ 'errorMessage' ] ) ) {
					$class = 'notice-error';
				} else {
					$class = 'notice-success';
				}
				
				AN_MC_View::get_instance()->get_template( '/admin/notice', array( 'class' => $class, 'data' => $last_query ) );
				delete_transient( 'mp-am-last-action-data' );
			}
			
		}
	}
	
	/**
	 * Get prefix
	 *
	 * @return string
	 */
	public function get_prefix() {
		return self::$prefix;
	}
	
	/**
	 * Dismiss admin Notice
	 */
	public function dismiss_admin_notice() {
		if ( ! current_user_can( 'manage_options' ) || ! isset( $_GET['dismiss_another-mailchimp'], $_GET['_wpnonce'] )
			|| 'yes' !== $_GET['dismiss_another-mailchimp'] || ! is_string( $_GET['_wpnonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'an_mc_dismiss_notice' ) ) {
			return;
		}
		add_user_meta( get_current_user_id(), 'ignore_error_notice', 'yes', true );
	}

	/**
	 * Admin notice
	 */
	public function admin_notices() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		global $current_user;
		$userid = $current_user->ID;
		if ( ! get_user_meta( $userid, 'ignore_error_notice' ) ) {
			echo '<div class="notice error fade  is-dismissible">' . $this->get_admin_notices() . '<p><a href="' . esc_url( wp_nonce_url( add_query_arg( 'dismiss_another-mailchimp', 'yes' ), 'an_mc_dismiss_notice' ) ) . '">' . esc_html__( 'Dismiss this notice', 'another-mailchimp-widget' ) . '</a></p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
	
	/**
	 * Get admin notices
	 *
	 * @return string
	 */
	public function get_admin_notices() {
		
		$notice = AN_MC_View::get_instance()->get_template_html( '/notice/change-settings' );
		
		return $notice;
	}
	
	/**
	 * Add admin page
	 *
	 * @return bool|void
	 */
	public function admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage these settings.', 'another-mailchimp-widget' ) );
		}
		$this->get_options();
		$api_key = $this->get_api_key();
		$HTTP_Code = 0;
		$mcapi = false;
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br/></div>
			<h2><?php esc_html_e( 'Mailchimp Settings', 'another-mailchimp-widget' ); ?></h2>
			<p><?php echo wp_kses_post( __( 'Enter a valid <a href="http://kb.mailchimp.com/accounts/management/about-api-keys#Find-or-Generate-Your-API-Key" target="_blank">Mailchimp API key</a> here to get started. You will need to have at least one Mailchimp list set up.', 'another-mailchimp-widget' ) ); ?></p>
			<form action="options.php" method="post">
				<?php settings_fields( self::$prefix . '_options' ); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><label for="<?php echo esc_attr( self::$prefix . '-api-key' ); ?>"><?php esc_html_e( 'Mailchimp API Key', 'another-mailchimp-widget' ); ?></label></th>
						<td>
							<?php if ( ! empty( $api_key ) ):
								$mcapi = $this->get_mcapi();
								$mcapi->get_lists();
								$HTTP_Code = $mcapi->HTTP_Code;
							endif; ?>
							<input class="regular-text" id="<?php echo esc_attr( self::$prefix ); ?>-api-key" name="<?php echo esc_attr( self::$prefix ); ?>_options[api-key]" type="password" value="<?php echo esc_attr( $api_key ); ?>" autocomplete="new-password"/>
							
							<?php if ( $mcapi && $mcapi->errors ): ?>
								<br/>
								<i><?php echo wp_kses_post( $mcapi->get_response_message() ); ?></i>
							<?php endif; ?>

						</td>
					</tr>
					<?php if ( ! empty( $api_key ) && empty( $mcapi->errorMessage ) ) { ?>
						<tr valign="top">
							<th scope="row">
								<label for="<?php echo esc_attr( self::$prefix . '-mpam-sync' ); ?>"><?php esc_html_e( 'Update Lists', 'another-mailchimp-widget' ); ?></label>
							</th>
							<td>
								<input class="button" id="<?php echo esc_attr( self::$prefix ); ?>-mpam-sync" name="<?php echo esc_attr( self::$prefix ); ?>_options[mpam_sync]" type="Submit" value="<?php esc_html_e( 'Synchronize', 'another-mailchimp-widget' ); ?>"/>
							</td>
						</tr>
						<?php
							$lists = $mcapi->get_account_subscribe_lists();
							if ( is_array( $lists ) && ! empty( $lists ) && ! isset( $lists['error'] ) ) {?>
							<tr valign="top">
								<th scope="row">
									<?php esc_html_e( 'Mailchimp Lists and Groups', 'another-mailchimp-widget' ); ?>
								</th>
								<td><?php
									
									if ( is_array( $lists ) && ! empty( $lists ) && ! isset( $lists['error'] ) ) {?>
										<p class="description"><?php esc_html_e( 'Use these List ID / Group ID in the shortcode as described below. ', 'another-mailchimp-widget' ); ?></p>
										<ul>
										<?php
										foreach ($lists as $list) {

											echo '<li><code><small><strong>' . esc_html__( 'List', 'another-mailchimp-widget' ) .'</strong></small></code> ' . esc_html( $list['title'] ) . ' - <code>' . esc_html( $list['id'] ) . '</code></li>';

											if ( !empty ($list['categories']) ) {
												foreach ($list['categories'] as $category) {

													if ( !empty($category['interests']) ) {?>
														<li><ul>
														<?php
														foreach ($category['interests'] as $interests) {

															echo '<li style="margin-left:1em;"><code><small><strong>' . esc_html__( 'Group', 'another-mailchimp-widget' ) .'</strong></small></code> ' . esc_html( $interests['title'] ) . ' - <code>' . esc_html( $interests['id'] ) . '</code></li>';
														} ?>
														</ul></li><?php
													}
												}
											}
										}?>
										</ul>
									<?php } ?>

								</td>
							</tr>
						<?php } ?>
					<?php } ?>
				</table>
				<p class="submit"><input type="submit" name="Submit" class="button-primary" value="<?php esc_html_e( 'Save Changes', 'another-mailchimp-widget' ); ?>"/></p>
			</form>
			<h4><?php esc_html_e( 'Shortcode example:', 'another-mailchimp-widget' ); ?></h4>
			<code>[mp-mc-form list="list_id/group_id" button="Subscribe" email_text="Your E-mail" first_name_text="First Name" last_name_text="Last Name" placeholder="true" firstname="false" lastname="false" success="Thank you for joining our mailing list." failure="There was a problem processing your submission." ]</code>
			<h4><?php esc_html_e( 'Shortcode attributes:', 'another-mailchimp-widget' ); ?></h4>
			<ul>
				<li><?php echo wp_kses_post( sprintf( __( '%s - Mailchimp <kbd>list_id</kbd> or <kbd>list_id/group_id</kbd> if you want to subscribe to specific group. To subscribe to several lists and groups separate them by comma.', 'another-mailchimp-widget' ), '<code>list</code>' ) ); ?></li>
				<li><?php echo wp_kses_post( sprintf( __( '%s - button label.', 'another-mailchimp-widget' ), '<code>button</code>' ) ); ?></li>
				<li><?php echo wp_kses_post( sprintf( __( '%s - label of the email address field.', 'another-mailchimp-widget' ), '<code>email_text</code>' ) ); ?></li>
				<li><?php echo wp_kses_post( sprintf( __( '%s - label of the first name field.', 'another-mailchimp-widget' ), '<code>first_name_text</code>' ) ); ?></li>
				<li><?php echo wp_kses_post( sprintf( __( '%s - label of the last name field.', 'another-mailchimp-widget' ), '<code>last_name_text</code>' ) ); ?></li>
				<li><?php echo wp_kses_post( sprintf( __( '%s - true or false; set true to display labels as placeholders.', 'another-mailchimp-widget' ), '<code>placeholder</code>' ) ); ?></li>
				<li><?php echo wp_kses_post( sprintf( __( '%s - true or false; set true if first name is required.', 'another-mailchimp-widget' ), '<code>firstname</code>' ) ); ?></li>
				<li><?php echo wp_kses_post( sprintf( __( '%s - true or false; set true if last name is required.', 'another-mailchimp-widget' ), '<code>lastname</code>' ) ); ?></li>
				<li><?php echo wp_kses_post( sprintf( __( '%s - success message.', 'another-mailchimp-widget' ), '<code>success</code>' ) ); ?></li>
				<li><?php echo wp_kses_post( sprintf( __( '%s - failure message.', 'another-mailchimp-widget' ), '<code>failure</code>' ) ); ?></li>
			</ul>
			<hr>
			<p><?php echo wp_kses_post(
				sprintf(
					__( 'If you like this plugin please leave us a <a href="%s" target="_blank">★★★★★ rating</a>.', 'another-mailchimp-widget' ),
					'https://wordpress.org/support/plugin/another-mailchimp-widget/reviews/'
				)
			); ?></p>
			<p></p>
		</div>
	<?php }
	
	/**
	 * Get MailChimp API3
	 *
	 * @return bool|MailChimp_API
	 */
	public function get_mcapi() {
		$this->get_options();
		$api_key = $this->get_api_key();
		if ( false == $api_key ) {
			return false;
		} else {
			if ( empty( self::$mcapi ) || self::$mcapi->api_key !== $api_key ) {
				self::$mcapi = new MailChimp_API( $api_key );
			}
			
			return self::$mcapi;
		}
	}
	
	/**
	 * Get MailChimp api Key
	 *
	 * @return bool|mixed
	 */
	private function get_api_key() {
		if ( is_array( $this->options ) && ! empty( $this->options[ 'api-key' ] ) && is_string( $this->options[ 'api-key' ] ) ) {
			return $this->options[ 'api-key' ];
		} else {
			return false;
		}
	}
	
	/**
	 *  Check exists api key
	 * @return bool
	 */
	public function is_have_api_key() {
		$api_key = $this->get_api_key();
		
		return (bool) $api_key;
	}
	
	/**
	 * Get admin error notices
	 *
	 * @param $mcapi
	 *
	 * @return string
	 */
	public function get_admin_error_notices( $mcapi ) {
		global $blog_id;
		$error = $mcapi->HTTP_Code;
		if ( $error === 104 || $error == 106 ) {
			$notice = AN_MC_View::get_instance()->get_template_html( '/notice/invalid-api-key' );
		} else {
			$notice = '<p>' . esc_html( $mcapi->errorMessage ) . '</p>';
		}
		
		return $notice;
	}
	
	/**
	 * Register setting
	 */
	public function register_settings() {
		register_setting( self::$prefix . '_options', self::$prefix . '_options', array( $this, 'save_settings' ) );
	}
	
	/**
	 * Remove option
	 */
	public function remove_options() {
		delete_option( self::$prefix . '_options' );
	}
	
	/**
	 * Add submenu page
	 */
	public function set_up_admin_page() {
		add_submenu_page( 'options-general.php', esc_html__( 'Mailchimp', 'another-mailchimp-widget' ), esc_html__( 'Mailchimp', 'another-mailchimp-widget' ),
			'manage_options', __FILE__, array( &$this, 'admin_page' ) );
	}
	
	/**
	 * Set option
	 */
	public function set_up_options() {
		add_option( self::$prefix . '_options', '', '', self::$public_option );
	}
	
	/**
	 * Add script
	 */
	public function add_scripts() {
		if ( ! is_admin() ) {
			wp_enqueue_script( 'ns-mc-widget', AN_MC_PLUGIN_URL . 'assets/js/another-mailchimp.min.js', array( 'jquery' ), AN_MC_PLUGIN_VERSION . '.' . filemtime( __DIR__ . '/../assets/js/another-mailchimp.min.js' ), true );
		}
	}
	
	public function add_style() {
		wp_enqueue_style( 'mp-am-widget', AN_MC_PLUGIN_URL . 'assets/css/style.css', array(), AN_MC_PLUGIN_VERSION );
	}
	
	/**
	 * Save settings
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function save_settings( $settings ) {
		if ( ! is_array( $settings ) || ! isset( $settings['api-key'] ) || ! is_string( $settings['api-key'] ) ) {
			return $this->get_options();
		}
		$api_key = sanitize_text_field( $settings['api-key'] );
		$api_key = trim( $api_key );
		if ( '' !== $api_key && ! preg_match( '/^[a-zA-Z0-9]+-us[0-9]+$/D', $api_key ) ) {
			if ( function_exists( 'add_settings_error' ) ) {
				add_settings_error( self::$prefix . '_options', 'invalid_api_key', __( 'Invalid Mailchimp API key', 'another-mailchimp-widget' ) );
			}
			return $this->get_options();
		}
		$old = $this->get_options();
		$changed = $api_key !== ( isset( $old['api-key'] ) ? $old['api-key'] : '' );
		if ( $changed ) {
			$this->clear_list_cache();
		}
		if ( ! empty( $settings['mpam_sync'] ) ) {
			$data = array( 'mpam_sync' => true, 'errorMessage' => '' );
			$mcapi = new MailChimp_API( $api_key );
			$lists = $mcapi->get_account_subscribe_lists( true );
			$data['HTTP_Code'] = $mcapi->HTTP_Code;
			if ( isset( $lists['error'] ) ) {
				$data['errorMessage'] = $lists['error'];
			} else {
				$data['list_count'] = count( $lists );
				$data['message'] = sprintf( _n( '%s list updated.', '%s lists updated.', count( $lists ), 'another-mailchimp-widget' ), count( $lists ) );
			}
			set_transient( 'mp-am-last-action-data', $data, 5 * MINUTE_IN_SECONDS );
		}
		return array( 'api-key' => $api_key );
	}

	private function clear_list_cache() {
		delete_option( self::$prefix . '_account_subscribe_lists' );
		delete_option( self::$prefix . '_account_subscribe_lists_key' );
	}

	/** Also handle credential changes made outside the settings screen. */
	public function credentials_changed( $old, $new ) {
		$old_key = is_array( $old ) && isset( $old['api-key'] ) ? $old['api-key'] : '';
		$new_key = is_array( $new ) && isset( $new['api-key'] ) ? $new['api-key'] : '';
		if ( $old_key !== $new_key ) {
			self::$mcapi = null;
			// A synchronization during sanitization may already have populated the new cache.
			if ( ! is_string( $new_key ) || hash( 'sha256', $new_key ) !== get_option( self::$prefix . '_account_subscribe_lists_key' ) ) {
				$this->clear_list_cache();
			}
		}
		$this->get_options();
	}
}
