<?php
if ( class_exists( 'MailChimp_API' ) ) {
	return;
}

/**
 * Class MailChimp_API
 */
class MailChimp_API {
	
	protected static $instance;
	/**
	 * Cache the user api_key so we only have to log in once per client instantiation
	 */
	var $api_key;
	var $url;
	var $username;
	var $dc;
	var $version = '3.0';
	var $errors = false;
	var $HTTP_Code;
	var $default_error_HTTP_Code = 400;
	var $errorMessage;
	var $show_all_params = 'count=100&offset=0';
	
	/**
	 * Connect to the MailChimp API for a given list.
	 *
	 * @param string $apikey Your MailChimp apikey $secure Whether or not this should use a secure connection
	 */
	public function __construct( $apikey = '' ) {
		$this->setup_api_key( $apikey );
	}
	
	/** Setup api key
	 *
	 * @param $apikey
	 */
	protected function setup_api_key( $apikey ) {
		$this->api_key = $apikey;
		$this->dc      = substr( $apikey, strpos( $apikey, '-' ) + 1 );;
		$this->url = "https://{$this->dc}.api.mailchimp.com/{$this->version}/";
	}
	
	/**
	 * @return Mailchimp_API
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Get all Lists => Categories => Interest By the API Key
	 *
	 * @param bool $sync
	 *
	 * @return array|mixed
	 */
	public function get_account_subscribe_lists( $sync = false ) {
		$prefix = AN_MC_Plugin::get_instance()->get_prefix();
		
		if ( ! $sync ) {
			$result = get_option( $prefix . '_account_subscribe_lists' );
		}
		
		if ( $sync || empty( $result ) ) {
			
			$result = array();
			
			$list = $this->get_lists();
			
			if ( isset( $list[ 'error' ] ) ) {
				return $list[ 'error' ];
			}
			
			if ( count( $list ) > 0 ) {
				$result = $list;
				
				foreach ( $list as $key => $list_item ) {
					$categories = $this->get_interest_categories( $list_item[ 'id' ] );
					if ( isset( $categories[ 'error' ] ) ) {
						return $categories;
					}
					
					$result[ $key ][ 'categories' ] = $categories;
					foreach ( $result[ $key ][ 'categories' ] as $k => $category_item ) {
						$interests = $this->get_interests( $list_item[ 'id' ], $category_item[ 'id' ] );
						if ( isset( $interests[ 'error' ] ) ) {
							return $interests;
						}
						
						$result[ $key ][ 'categories' ][ $k ][ 'interests' ] = $interests;
					}
				}
			}
			
			if ( ! empty( $result ) ) {
				update_option( $prefix . '_account_subscribe_lists', $result );
			}
		}
		
		return $result;
	}
	
	/**
	 * Get Mailchimp account lists by username & api_key
	 *
	 * @return array|mixed|object|string
	 */
	public function get_lists() {
		
		$response = wp_remote_request( $this->url . "lists" . "?" . $this->show_all_params, array(
			'headers'   => array(
				'Authorization' => 'Basic ' . base64_encode( $this->username . ':' . $this->api_key ),
			),
			'sslverify' => false
		) );
		
		// set transient live
		if ( ! is_wp_error( $response ) ) {
			$this->HTTP_Code = wp_remote_retrieve_response_code( $response );
		} else {
			$this->HTTP_Code = $this->default_error_HTTP_Code;
		}
		
		if ( $this->HTTP_Code == 200 ) {
			
			$body = wp_remote_retrieve_body( $response );
			$body = json_decode( $body, true );
			
			if ( isset( $body[ 'lists' ] ) ) {
				$body = array_map( function ( $item ) {
					return array( 'id' => $item[ 'id' ], 'title' => $item[ 'name' ] );
				}, $body[ 'lists' ] );
			} else {
				$body = array();
			}
		} else {
			return $this->get_errors( $response );
		}
		
		return $body;
	}
	
	/**
	 * Get error request
	 *
	 * @param $request
	 *
	 * @return array('error' => 'message)
	 */
	private function get_errors( $request ) {
		
		if ( is_wp_error( $request ) ) {
			$this->errorMessage = $content = $request->get_error_message();
		} else {
			$content            = json_decode( $request[ 'body' ], true );
			$this->errorMessage = $content = ( isset( $content[ 'detail' ] ) ) ? $content[ 'detail' ] : esc_html__( 'Data format error.', 'another-mailchimp-widget' );
		}
		
		return array( 'error' => $content );
	}
	
	/**
	 * Get MailChimp account categories by list as array('id' => 'id', 'title' => 'title')
	 *
	 * @param $list_id
	 *
	 * @return array|mixed
	 */
	public function get_interest_categories( $list_id ) {
		
		$request = wp_remote_get( $this->url . "lists/{$list_id}/interest-categories" . "?" . $this->show_all_params, array(
			'headers'   => array(
				'Authorization' => 'Basic ' . base64_encode( $this->username . ':' . $this->api_key ),
			),
			'sslverify' => false
		) );
		
		if ( wp_remote_retrieve_response_code( $request ) == 200 ) {
			
			$body = wp_remote_retrieve_body( $request );
			
			$body = json_decode( $body, true );
			
			if ( isset( $body[ 'categories' ] ) ) {
				$body = array_map( function ( $item ) {
					return array( 'id' => $item[ 'id' ], 'title' => $item[ 'title' ] );
				}, $body[ 'categories' ] );
			} else {
				$body = array();
			}
		} else {
			return $this->get_errors( $request );
		}
		
		return $body;
	}
	
	/**
	 * Get list interests
	 *
	 * @param $list_id
	 * @param $category_id
	 *
	 * @return array|mixed
	 */
	public function get_interests( $list_id, $category_id ) {
		
		$request = wp_remote_get( $this->url . "lists/{$list_id}/interest-categories/{$category_id}/interests" . "?" . $this->show_all_params, array(
			'headers'   => array(
				'Authorization' => 'Basic ' . base64_encode( $this->username . ':' . $this->api_key ),
			),
			'sslverify' => false
		) );
		
		if ( wp_remote_retrieve_response_code( $request ) == 200 ) {
			$body = wp_remote_retrieve_body( $request );
			$body = json_decode( $body, true );
			
			if ( isset( $body[ 'interests' ] ) ) {
				$body = array_map( function ( $item ) {
					return array( 'id' => $item[ 'id' ], 'title' => $item[ 'name' ] );
				}, $body[ 'interests' ] );
			} else {
				$body = array();
			}
		} else {
			return $this->get_errors( $request );
		}
		
		return $body;
	}
	
	/**
	 * Get response message
	 *
	 * @return mixed|string
	 */
	public function get_response_message() {
		$default_message = esc_html__( 'Invalid Mailchimp API key', 'another-mailchimp-widget' );
		
		$messages = array(
			'104' => esc_html__( 'Invalid Mailchimp API key', 'another-mailchimp-widget' ),
			'106' => esc_html__( 'Invalid Mailchimp API key', 'another-mailchimp-widget' ),
			'401' => $this->errorMessage,
			'403' => $this->errorMessage,
			'503' => esc_html__( 'Invalid Mailchimp API key', 'another-mailchimp-widget' ),
		
		);
		
		if ( isset( $messages[ $this->HTTP_Code ] ) ) {
			$message = $messages[ $this->HTTP_Code ];
		}
		
		return empty( $message ) ? $default_message : $message;
	}
	
	/**
	 * Add new member to the list
	 *
	 * @param $email
	 * @param $settings   array( list_id => array ( 0 => array(  ) ) )
	 *
	 * @return array|bool|mixed
	 */
	public function add_to_list( $email, $settings ) {
		
		$this->init_options( $settings );
		$email    = sanitize_email( $email );
		$lists    = $settings[ 'list_ids' ];
		$status   = ( isset( $settings[ 'send_confirm' ] ) && $settings[ 'send_confirm' ] ) ? 'pending' :
			apply_filters('an_mc_default_subscription_status', 'subscribed');
		$response = array();
		
		if ( $email ) {
			
			foreach ( $lists as $list_id => $interests ) {
				
				// basic request params
				$data = array(
					'email_address' => $email,
					'status'        => $status,
				);
				
				// add / remove interests
				if ( is_array( $interests ) && ! empty( $interests ) ) {
					$interests = array_map( function ( $item ) {
						return $item == "true";
					}, (array) $interests );
					
				}
				
				// add fields to request if exists
				if ( ! empty( $settings[ 'merge_fields' ] ) ) {
					$data[ 'merge_fields' ] = $settings[ 'merge_fields' ];
				}
				
				if ( ( isset( $interests[ 0 ] ) && ( $interests[ 0 ] == false ) || empty( $interests ) ) ) {
					$body = json_encode( $data );
				} else {
					$data[ 'interests' ] = $interests;
					$body                = json_encode( $data );
				}
				
				$response = $this->put_user_to_list( $email, $list_id, $body, $response );
			}
		}
		
		$body = array();
		if ( is_array( $response ) ) {
			
			$this->check_response( $response[ 0 ] );
			
			foreach ( $response as $key => $response_item ) {
				if ( is_wp_error( $response_item ) ) {
					$body[ $key ][ 'response' ] = $response_item->get_error_message();
					$body[ $key ][ 'body' ]     = $response_item->get_error_code();
				} else {
					$body[ $key ][ 'response' ] = isset( $response_item[ 'response' ] ) ? $response_item[ 'response' ] : esc_html__( 'Unable to subscribe user.', 'another-mailchimp-widget' );
					$body[ $key ][ 'body' ]     = isset( $response_item[ 'body' ] ) ? $response_item[ 'body' ] : '';
				}
			}
		}
		
		return $body;
	}
	
	/**
	 * Init options
	 *
	 * @param $mailChimp_settings
	 *
	 * @return $this
	 */
	public function init_options( $mailChimp_settings ) {
		$this->set_apikey( $mailChimp_settings[ 'apikey' ] );
		$this->username = $mailChimp_settings[ 'user_name' ];
		
		return $this;
	}
	
	/**
	 * Set $apiKey
	 *
	 * @param $apiKey
	 */
	public function set_apikey( $apiKey ) {
		$this->api_key = $apiKey;
		$this->dc      = substr( $apiKey, strpos( $apiKey, '-' ) + 1 );
		$this->url     = "https://{$this->dc}.api.mailchimp.com/{$this->version}/";
	}
	
	/**
	 * Put user to list
	 *
	 * @param $email
	 * @param $list_id
	 * @param $body
	 * @param $response
	 *
	 * @return array
	 */
	protected function put_user_to_list( $email, $list_id, $body, $response ) {
		$response[] = wp_remote_post( $this->url . "lists/{$list_id}/members/{$this->member_hash($email)}", array(
			'headers'   => array(
				'Authorization' => 'Basic ' . base64_encode( $this->username . ':' . $this->api_key ),
			),
			'body'      => $body,
			'method'    => 'PUT',
			'sslverify' => false
		) );
		
		return $response;
	}
	
	/**
	 * Member hash
	 *
	 * @param $email
	 *
	 * @return string
	 */
	private function member_hash( $email ) {
		return md5( strtolower( $email ) );
	}
	
	/**
	 * Check response
	 *
	 * @param $response
	 */
	protected function check_response( $response ) {
		if ( is_wp_error( $response ) ) {
			$this->HTTP_Code = $this->default_error_HTTP_Code;
		} else {
			$this->HTTP_Code    = wp_remote_retrieve_response_code( $response );
			$this->errorMessage = wp_remote_retrieve_response_message( $response );
			
		}
		$this->errors = ( $this->HTTP_Code !== 200 ) ? true : false;
	}
}