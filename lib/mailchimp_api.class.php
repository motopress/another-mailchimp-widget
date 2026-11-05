<?php
if ( class_exists( 'MailChimp_API' ) ) {
	return;
}

/** Mailchimp Marketing API client. Errors use the existing array('error' => message) contract. */
class MailChimp_API {
	protected static $instance;
	public $api_key = '';
	public $url = '';
	public $username = '';
	public $dc = '';
	public $version = '3.0';
	public $errors = false;
	public $HTTP_Code = 0;
	public $default_error_HTTP_Code = 400;
	public $errorMessage = '';
	public $show_all_params = 'count=100&offset=0';

	public function __construct( $apikey = '' ) {
		$this->setup_api_key( $apikey );
	}

	protected function setup_api_key( $apikey ) {
		$this->set_apikey( $apikey );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function set_apikey( $apikey ) {
		$this->api_key = is_string( $apikey ) ? trim( $apikey ) : '';
		$this->dc = '';
		$this->url = '';
		if ( preg_match( '/^[a-zA-Z0-9]+-(us[0-9]+)$/D', $this->api_key, $matches ) ) {
			$this->dc = $matches[1];
			$this->url = 'https://' . $this->dc . '.api.mailchimp.com/' . $this->version . '/';
		}
	}

	private function reset_errors() {
		$this->errors = false;
		$this->HTTP_Code = 0;
		$this->errorMessage = '';
	}

	private function error( $message ) {
		$this->errors = true;
		$this->errorMessage = $message;
		return array( 'error' => $message );
	}

	private function get_errors( $response ) {
		if ( is_wp_error( $response ) ) {
			$this->HTTP_Code = $this->default_error_HTTP_Code;
			return $this->error( $response->get_error_message() );
		}
		$this->HTTP_Code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$message = is_array( $body ) && isset( $body['detail'] ) && is_string( $body['detail'] )
			? $body['detail'] : __( 'Unable to communicate with Mailchimp.', 'another-mailchimp-widget' );
		return $this->error( $message );
	}

	private function request( $path, $args = array() ) {
		if ( '' === $this->url ) {
			return new WP_Error( 'an_mc_invalid_api_key', __( 'Invalid Mailchimp API key', 'another-mailchimp-widget' ) );
		}
		$args = array_merge( array(
			'timeout' => 15,
			'redirection' => 0,
			'sslverify' => true,
		), $args );
		$args['headers'] = array(
			'Authorization' => 'Basic ' . base64_encode( $this->username . ':' . $this->api_key ),
			'Content-Type' => 'application/json',
		);
		return wp_remote_request( $this->url . $path, $args );
	}

	/** Read every page; never return partial collections after an error. */
	private function collection( $path, $key, $title_key ) {
		$this->reset_errors();
		$offset = 0;
		$items = array();
		do {
			$response = $this->request( $path . '?count=100&offset=' . $offset );
			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				return $this->get_errors( $response );
			}
			$this->HTTP_Code = 200;
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! is_array( $body ) || ! isset( $body[ $key ] ) || ! is_array( $body[ $key ] ) ) {
				return $this->error( __( 'Data format error.', 'another-mailchimp-widget' ) );
			}
			if ( isset( $body['total_items'] ) && ( ! is_int( $body['total_items'] ) || $body['total_items'] < 0 ) ) {
				return $this->error( __( 'Data format error.', 'another-mailchimp-widget' ) );
			}
			$page = $body[ $key ];
			foreach ( $page as $item ) {
				if ( ! is_array( $item ) || ! isset( $item['id'], $item[ $title_key ] )
					|| ! is_string( $item['id'] ) || ! is_string( $item[ $title_key ] ) ) {
					return $this->error( __( 'Data format error.', 'another-mailchimp-widget' ) );
				}
				// Also detects a server repeating a page instead of advancing the offset.
				if ( isset( $items[ $item['id'] ] ) ) {
					return $this->error( __( 'Data format error.', 'another-mailchimp-widget' ) );
				}
				$items[ $item['id'] ] = array( 'id' => $item['id'], 'title' => $item[ $title_key ] );
			}
			$count = count( $page );
			$offset += $count;
			$total = isset( $body['total_items'] ) && is_int( $body['total_items'] ) ? $body['total_items'] : null;
			if ( 0 === $count && null !== $total && $offset < $total ) {
				return $this->error( __( 'Data format error.', 'another-mailchimp-widget' ) );
			}
		} while ( null !== $total ? $offset < $total : 100 === $count );
		return array_values( $items );
	}

	public function get_lists() {
		return $this->collection( 'lists', 'lists', 'name' );
	}

	private function identifier( $value ) {
		return ( is_string( $value ) || is_int( $value ) ) && preg_match( '/^[a-zA-Z0-9_-]+$/D', (string) $value );
	}

	public function get_interest_categories( $list_id ) {
		if ( ! $this->identifier( $list_id ) ) {
			return $this->error( __( 'Invalid mailing list.', 'another-mailchimp-widget' ) );
		}
		return $this->collection( 'lists/' . rawurlencode( $list_id ) . '/interest-categories', 'categories', 'title' );
	}

	public function get_interests( $list_id, $category_id ) {
		if ( ! $this->identifier( $list_id ) || ! $this->identifier( $category_id ) ) {
			return $this->error( __( 'Invalid mailing list.', 'another-mailchimp-widget' ) );
		}
		return $this->collection( 'lists/' . rawurlencode( $list_id ) . '/interest-categories/' . rawurlencode( $category_id ) . '/interests', 'interests', 'name' );
	}

	public function get_account_subscribe_lists( $sync = false ) {
		$option = AN_MC_Plugin::get_instance()->get_prefix() . '_account_subscribe_lists';
		$key_option = $option . '_key';
		$fingerprint = hash( 'sha256', $this->api_key );
		$cached = get_option( $option );
		if ( ! $sync && $fingerprint === get_option( $key_option ) && is_array( $cached ) && ! isset( $cached['error'] ) ) {
			$this->reset_errors();
			return $cached;
		}
		$lists = $this->get_lists();
		if ( isset( $lists['error'] ) ) {
			return $lists;
		}
		foreach ( $lists as &$list ) {
			$categories = $this->get_interest_categories( $list['id'] );
			if ( isset( $categories['error'] ) ) {
				return $categories;
			}
			foreach ( $categories as &$category ) {
				$interests = $this->get_interests( $list['id'], $category['id'] );
				if ( isset( $interests['error'] ) ) {
					return $interests;
				}
				$category['interests'] = $interests;
			}
			unset( $category );
			$list['categories'] = $categories;
		}
		unset( $list );
		update_option( $option, $lists, false );
		update_option( $key_option, $fingerprint, false );
		return $lists;
	}

	public function get_response_message() {
		return $this->errorMessage ?: __( 'Invalid Mailchimp API key', 'another-mailchimp-widget' );
	}

	public function init_options( $settings ) {
		$settings = is_array( $settings ) ? $settings : array();
		$this->set_apikey( isset( $settings['apikey'] ) ? $settings['apikey'] : '' );
		$this->username = isset( $settings['user_name'] ) && is_string( $settings['user_name'] ) ? $settings['user_name'] : '';
		return $this;
	}

	public function add_to_list( $email, $settings ) {
		$this->reset_errors();
		$this->init_options( $settings );
		if ( ! is_string( $email ) || ! is_email( $email ) || ! is_array( $settings )
			|| empty( $settings['list_ids'] ) || ! is_array( $settings['list_ids'] ) || '' === $this->url ) {
			$this->error( __( 'Unable to subscribe user.', 'another-mailchimp-widget' ) );
			return array();
		}
		$status = ! empty( $settings['send_confirm'] ) ? 'pending' : apply_filters( 'an_mc_default_subscription_status', 'subscribed' );
		if ( ! is_string( $status ) || ! in_array( $status, array( 'subscribed', 'pending', 'unsubscribed', 'cleaned', 'transactional' ), true ) ) {
			$this->error( __( 'Invalid subscription status.', 'another-mailchimp-widget' ) );
			return array();
		}
		$bodies = array();
		foreach ( $settings['list_ids'] as $list_id => $interests ) {
			if ( ! $this->identifier( $list_id ) || ! is_array( $interests ) ) {
				$this->error( __( 'Invalid mailing list.', 'another-mailchimp-widget' ) );
				return array();
			}
			$data = array( 'email_address' => $email, 'status' => $status );
			if ( ! empty( $settings['merge_fields'] ) ) {
				if ( ! is_array( $settings['merge_fields'] ) ) {
					$this->error( __( 'Data format error.', 'another-mailchimp-widget' ) );
					return array();
				}
				$data['merge_fields'] = $settings['merge_fields'];
			}
			if ( $interests ) {
				$data['interests'] = array();
				foreach ( $interests as $interest_id => $enabled ) {
					if ( ! $this->identifier( $interest_id ) || ! is_scalar( $enabled ) ) {
						$this->error( __( 'Invalid mailing list.', 'another-mailchimp-widget' ) );
						return array();
					}
					$data['interests'][ $interest_id ] = filter_var( $enabled, FILTER_VALIDATE_BOOLEAN );
				}
			}
			$body = wp_json_encode( $data );
			if ( false === $body ) {
				$this->error( __( 'Data format error.', 'another-mailchimp-widget' ) );
				return array();
			}
			$bodies[ $list_id ] = $body;
		}
		$responses = array();
		foreach ( $bodies as $list_id => $body ) {
			$responses = $this->put_user_to_list( $email, $list_id, $body, $responses );
		}
		if ( empty( $responses ) ) {
			$this->error( __( 'Unable to subscribe user.', 'another-mailchimp-widget' ) );
		}
		$result = array();
		foreach ( $responses as $response ) {
			$this->check_response( $response );
			$result[] = is_wp_error( $response )
				? array( 'response' => $response->get_error_message(), 'body' => $response->get_error_code() )
				: array( 'response' => isset( $response['response'] ) ? $response['response'] : array(), 'body' => wp_remote_retrieve_body( $response ) );
		}
		return $result;
	}

	protected function put_user_to_list( $email, $list_id, $body, $response ) {
		$response[] = $this->request( 'lists/' . rawurlencode( (string) $list_id ) . '/members/' . $this->member_hash( $email ), array( 'method' => 'PUT', 'body' => $body ) );
		return $response;
	}

	private function member_hash( $email ) {
		return md5( strtolower( $email ) );
	}

	protected function check_response( $response ) {
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( is_wp_error( $response ) || $code < 200 || $code >= 300 ) {
			$this->get_errors( $response );
			return;
		}
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || ! isset( $body['id'] ) || ! is_string( $body['id'] ) ) {
			$this->error( __( 'Data format error.', 'another-mailchimp-widget' ) );
			return;
		}
		if ( ! $this->errors ) {
			$this->HTTP_Code = $code;
		}
	}
}
