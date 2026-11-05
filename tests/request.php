<?php
/** Subprocess fixture: exercise the actual parse_request callback and JSON exit. */
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = $argv[1];
$request = json_decode( base64_decode( $argv[2] ), true );
if ( 'POST' === $argv[1] ) {
	$_POST = $request;
} else {
	$_GET = $request;
}
define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true );
require getenv( 'AN_MC_WP_ROOT' ) . '/wp-load.php';
add_filter( 'pre_http_request', function () {
	return array( 'headers' => array(), 'body' => '{"id":"member"}', 'response' => array( 'code' => 200, 'message' => 'OK' ), 'cookies' => array() );
} );
do_action( 'parse_request' );
echo 'NOT_HANDLED';
