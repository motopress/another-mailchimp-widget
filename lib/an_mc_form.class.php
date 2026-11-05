<?php

/** Shared, stateless configuration and submission handling for both form types. */
class AN_MC_Form {

	public static function render( $instance, $id_base, $form_id, $type ) {
		$plugin = AN_MC_Plugin::get_instance();
		$plugin->add_scripts();
		$plugin->add_style();
		$instance = self::normalize( $instance );
		if ( ! $plugin->is_have_api_key() ) {
			AN_MC_View::get_instance()->get_template( '/notice/change-settings' );
		} elseif ( empty( $instance['current_mailing_list'] ) ) {
			AN_MC_View::get_instance()->get_template( '/notice/empty-list' );
		} else {
			AN_MC_View::get_instance()->get_template( 'subscription-form', array(
				'instance' => $instance, 'id_base' => $id_base, 'form_id' => $form_id, 'type' => $type,
			) );
		}
	}

	public static function defaults() {
		return array(
			'title' => '',
			'current_mailing_list' => array(),
			'failure_message' => __( 'There was a problem processing your submission.', 'another-mailchimp-widget' ),
			'success_message' => __( 'Thank you for joining our mailing list.', 'another-mailchimp-widget' ),
			'signup_text' => __( 'Subscribe', 'another-mailchimp-widget' ),
			'email_text' => __( 'Your E-mail', 'another-mailchimp-widget' ),
			'first_name_text' => __( 'First Name', 'another-mailchimp-widget' ),
			'last_name_text' => __( 'Last Name', 'another-mailchimp-widget' ),
			'collect_first' => false,
			'collect_last' => false,
			'showplaceholder' => true,
		);
	}

	public static function boolean( $value, $default = false ) {
		if ( ! is_scalar( $value ) ) {
			return $default;
		}
		$parsed = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
		return null === $parsed ? $default : $parsed;
	}

	/** Return an empty selection for any malformed destination. */
	public static function lists( $value ) {
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}
		if ( ! is_array( $value ) ) {
			return array();
		}
		$lists = array();
		foreach ( $value as $list ) {
			if ( ! is_string( $list ) ) {
				return array();
			}
			$list = trim( $list );
			if ( '' === $list ) {
				continue;
			}
			if ( ! preg_match( '~^[a-zA-Z0-9_-]+(?:/[a-zA-Z0-9_-]+)?$~D', $list ) ) {
				return array();
			}
			$lists[] = $list;
		}
		return array_values( array_unique( $lists ) );
	}

	public static function normalize( $instance ) {
		$defaults = self::defaults();
		$instance = is_array( $instance ) ? $instance : array();
		$result = array();
		foreach ( $defaults as $key => $default ) {
			$value = isset( $instance[ $key ] ) ? $instance[ $key ] : $default;
			if ( is_bool( $default ) ) {
				$result[ $key ] = self::boolean( $value, $default );
			} elseif ( 'current_mailing_list' === $key ) {
				$result[ $key ] = self::lists( $value );
			} else {
				$value = is_string( $value ) ? $value : $default;
				$result[ $key ] = in_array( $key, array( 'success_message', 'failure_message' ), true )
					? wp_kses_post( $value ) : sanitize_text_field( $value );
			}
		}
		return $result;
	}

	private static function signature( $payload ) {
		// Multisite blogs share salts, so bind configurations to the current blog too.
		return hash_hmac( 'sha256', 'an-mc-form:' . get_current_blog_id() . ':' . $payload, wp_salt( 'auth' ) );
	}

	public static function fields( $instance, $id, $type ) {
		$config = self::normalize( $instance );
		$config['version'] = 1;
		$config['id'] = $id;
		$config['type'] = $type;
		$payload = base64_encode( wp_json_encode( $config ) );
		return '<input type="hidden" name="an_mc_submit" value="1"/>'
			. '<input type="hidden" name="an_mc_config" value="' . esc_attr( $payload ) . '"/>'
			. '<input type="hidden" name="an_mc_signature" value="' . esc_attr( self::signature( $payload ) ) . '"/>';
	}

	/** Only explicitly marked requests belong to this plugin. */
	public static function process_submission() {
		if ( ! isset( $_POST['an_mc_submit'] ) && ! isset( $_GET['an_mc_submit'] )
			&& ! isset( $_POST['mp_am_type'] ) && ! isset( $_GET['mp_am_type'] ) ) {
			return;
		}
		nocache_headers();
		$request = isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD']
			? wp_unslash( $_POST ) : array();
		wp_send_json( self::submit( $request ) );
	}

	/** Return a result separately from HTTP output for regression/integration tests. */
	public static function submit( $request ) {
		$result = array( 'success' => false, 'error' => self::defaults()['failure_message'] );
		if ( ! is_array( $request ) ) {
			return $result;
		}
		$payload = isset( $request['an_mc_config'] ) ? $request['an_mc_config'] : null;
		$signature = isset( $request['an_mc_signature'] ) ? $request['an_mc_signature'] : null;
		if ( ! is_string( $payload ) || strlen( $payload ) > 32768 || ! is_string( $signature )
			|| ! hash_equals( self::signature( $payload ), $signature ) ) {
			return $result;
		}
		$decoded = base64_decode( $payload, true );
		$config = false === $decoded ? null : json_decode( $decoded, true );
		if ( ! is_array( $config ) || ! isset( $config['version'], $config['id'], $config['type'] )
			|| 1 !== $config['version'] || ! in_array( $config['type'], array( 'widget', 'shortcode' ), true )
			|| ! is_string( $config['id'] ) || ! preg_match( '/^[a-zA-Z0-9_-]+$/D', $config['id'] ) ) {
			return $result;
		}
		$id = $config['id'];
		$config = self::normalize( $config );
		$result['error'] = $config['failure_message'];
		$email = isset( $request[ $id . '_email' ] ) ? $request[ $id . '_email' ] : null;
		if ( ! is_string( $email ) || ! is_email( $email ) || empty( $config['current_mailing_list'] ) ) {
			return $result;
		}
		$merge_fields = array();
		foreach ( array( 'first' => 'FNAME', 'last' => 'LNAME' ) as $field => $merge_key ) {
			$value = isset( $request[ $id . '_' . $field . '_name' ] ) ? $request[ $id . '_' . $field . '_name' ] : '';
			if ( ! is_string( $value ) ) {
				return $result;
			}
			$value = sanitize_text_field( $value );
			if ( $config[ 'collect_' . $field ] ) {
				if ( '' === trim( $value ) ) {
					return $result;
				}
				$merge_fields[ $merge_key ] = $value;
			}
		}
		$plugin = AN_MC_Plugin::get_instance();
		$api = $plugin->get_mcapi();
		if ( ! $api ) {
			return $result;
		}
		$options = $plugin->get_options();
		$settings = array( 'apikey' => $options['api-key'], 'user_name' => '', 'list_ids' => array(), 'merge_fields' => $merge_fields );
		foreach ( $config['current_mailing_list'] as $destination ) {
			$parts = explode( '/', $destination );
			if ( ! isset( $settings['list_ids'][ $parts[0] ] ) ) {
				$settings['list_ids'][ $parts[0] ] = array();
			}
			if ( isset( $parts[1] ) ) {
				$settings['list_ids'][ $parts[0] ][ $parts[1] ] = true;
			}
		}
		$api->add_to_list( $email, $settings );
		if ( $api->errors ) {
			return $result;
		}
		return array( 'success' => true, 'error' => '', 'success_message' => $config['success_message'] );
	}
}
