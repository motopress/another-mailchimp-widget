<?php
/** Integration regressions against real WordPress; all Mailchimp traffic is mocked. */
error_reporting( E_ALL );
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true );
set_exception_handler( function ( $error ) { fwrite( STDERR, (string) $error . "\n" ); exit( 1 ); } );
$root = getenv( 'AN_MC_WP_ROOT' );
if ( ! $root || ! is_file( $root . '/wp-load.php' ) ) {
	throw new RuntimeException( 'Set AN_MC_WP_ROOT to an isolated WordPress test installation; see tests/README.md.' );
}
$early_notices = array();
$GLOBALS['wp_filter']['doing_it_wrong_run'][10][] = array(
	'function' => function ( $function, $message ) use ( &$early_notices ) { $early_notices[] = $function . ': ' . $message; },
	'accepted_args' => 2,
);
require $root . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/admin.php';
set_error_handler( function ( $level, $message, $file, $line ) {
	if ( error_reporting() & $level ) {
		throw new ErrorException( $message, 0, $level, $file, $line );
	}
	return false;
} );
$count = 0;
function verify( $condition, $message ) {
	global $count;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	++$count;
}
function response( $body, $code = 200 ) {
	return array( 'headers' => array(), 'body' => is_string( $body ) ? $body : wp_json_encode( $body ), 'response' => array( 'code' => $code, 'message' => 'Test response' ), 'cookies' => array() );
}
$http = null;
$calls = array();
add_filter( 'pre_http_request', function ( $pre, $args, $url ) use ( &$http, &$calls ) {
	if ( false === strpos( $url, '.api.mailchimp.com/' ) ) {
		return new WP_Error( 'network_disabled', 'Only mocked HTTP is allowed in these tests.' );
	}
	verify( true === $args['sslverify'] && 0 === $args['redirection'], 'TLS verified and credential redirects disabled' );
	$calls[] = array( $url, $args );
	if ( ! $http ) {
		throw new RuntimeException( 'Unexpected Mailchimp request: ' . $url );
	}
	return $http( $url, $args );
}, 10, 3 );
function signed_request( $instance = array(), $type = 'shortcode' ) {
	$html = AN_MC_Form::fields( array_merge( array( 'current_mailing_list' => 'audience/group' ), $instance ), 'form', $type );
	preg_match_all( '/name="([^"]+)" value="([^"]*)"/', $html, $matches, PREG_SET_ORDER );
	$request = array( 'form_email' => 'test@example.com' );
	foreach ( $matches as $match ) {
		$request[ $match[1] ] = html_entity_decode( $match[2], ENT_QUOTES, 'UTF-8' );
	}
	return $request;
}
function reset_key( $key = 'test-us1' ) {
	update_option( 'ns_mc_options', array( 'api-key' => $key ) );
	AN_MC_Plugin::get_instance()->get_options();
}

verify( empty( $early_notices ), 'No incorrect initialization/translation timing notices: ' . implode( '; ', $early_notices ) );
verify( 1 === did_action( 'widgets_init' ), 'WordPress initialized widgets' );
verify( isset( $GLOBALS['wp_widget_factory']->widgets['AN_Widget_MailChimp'] ), 'Widget registered on normal bootstrap' );
$initial_include_path = get_include_path();
Another_mailChimp_widget::get_instance()->buffered_autoloader( 'Unrelated_Class' );
verify( $initial_include_path === get_include_path(), 'Autoloader does not alter global include path' );
verify( false === AN_MC_Form::boolean( false ) && true === AN_MC_Form::boolean( true ), 'Boolean values preserved' );
verify( false === AN_MC_Form::boolean( 'false' ) && true === AN_MC_Form::boolean( 'true' ), 'Boolean strings parsed' );
verify( array() === AN_MC_Form::lists( array( array() ) ), 'Nested destination arrays rejected' );
verify( array() === AN_MC_Form::lists( 'audience/../../other' ), 'Destination traversal rejected' );

reset_key();
$http = function ( $url, $args ) { return response( array( 'id' => 'member' ) ); };
$request = signed_request( array( 'success_message' => '<strong>Custom</strong><script>alert(1)</script>', 'failure_message' => '<em>Try again</em>' ) );
$result = AN_MC_Form::submit( $request );
verify( true === $result['success'] && false !== strpos( $result['success_message'], '<strong>Custom</strong>' ) && false === strpos( $result['success_message'], '<script>' ), 'Valid signed configuration preserves safe message markup' );
$body = json_decode( end( $calls )[1]['body'], true );
verify( true === $body['interests']['group'] && 'subscribed' === $body['status'], 'Group boolean and original subscription status preserved' );
$before = count( $calls );
$bad = $request;
$bad['an_mc_config'] = str_replace( 'audience', 'other', base64_decode( $bad['an_mc_config'] ) );
$bad['an_mc_config'] = base64_encode( $bad['an_mc_config'] );
verify( false === AN_MC_Form::submit( $bad )['success'], 'Tampered configuration rejected' );
$bad = $request;
$bad['an_mc_signature'] = array();
verify( false === AN_MC_Form::submit( $bad )['success'], 'Array signature rejected without warning' );
verify( false === AN_MC_Form::submit( array() )['success'], 'Unsigned submission rejected' );
verify( false === AN_MC_Form::submit( null )['success'], 'Non-array request rejected' );
$bad = $request;
$bad['form_email'] = array( 'test@example.com' );
verify( false === AN_MC_Form::submit( $bad )['success'], 'Array email rejected' );
$bad['form_email'] = 'invalid';
verify( false === AN_MC_Form::submit( $bad )['success'], 'Invalid email rejected' );
verify( $before === count( $calls ), 'Invalid requests make no API calls' );
$previous_blog = $GLOBALS['blog_id'];
$GLOBALS['blog_id'] = 999;
verify( false === AN_MC_Form::submit( $request )['success'], 'Configuration bound to the blog' );
$GLOBALS['blog_id'] = $previous_blog;
$required = signed_request( array( 'collect_first' => true, 'collect_last' => true ) );
verify( false === AN_MC_Form::submit( $required )['success'], 'Required names enforced server-side' );
$required['form_first_name'] = 'First';
$required['form_last_name'] = 'Last';
verify( true === AN_MC_Form::submit( $required )['success'], 'Required names accepted' );
$body = json_decode( end( $calls )[1]['body'], true );
verify( array( 'FNAME' => 'First', 'LNAME' => 'Last' ) === $body['merge_fields'], 'Names included in merge fields' );
reset_key( '' );
verify( false === AN_MC_Form::submit( $request )['success'], 'Missing API key fails gracefully' );
reset_key();
$api = new MailChimp_API( 'test-us1' );
$api->add_to_list( 'test@example.com', array( 'apikey' => 'test-us1', 'list_ids' => array() ) );
verify( true === $api->errors, 'Empty destinations fail without undefined offsets' );
$before = count( $calls );
$api->add_to_list( 'test@example.com', array( 'apikey' => 'test-us1.attacker.test', 'list_ids' => array( 'audience' => array() ) ) );
verify( true === $api->errors && $before === count( $calls ), 'Invalid API host rejected before HTTP' );
$multi = signed_request( array( 'current_mailing_list' => 'good,bad' ) );
foreach ( array( 'good,bad', 'bad,good' ) as $selection ) {
	$http = function ( $url ) { return false !== strpos( $url, '/lists/bad/' ) ? response( array( 'detail' => 'Failed' ), 400 ) : response( array( 'id' => 'member' ) ); };
	verify( false === AN_MC_Form::submit( signed_request( array( 'current_mailing_list' => $selection ) ) )['success'], 'Partial failure remains a failure in either order' );
}
$http = function () { return new WP_Error( 'timeout', 'Timed out' ); };
verify( false === AN_MC_Form::submit( $request )['success'], 'Transport failure reported' );
$http = function () { return response( 'invalid JSON' ); };
verify( false === AN_MC_Form::submit( $request )['success'], 'Malformed success body rejected' );
$http = function () { return response( array( 'id' => 'member' ), 201 ); };
verify( true === AN_MC_Form::submit( $request )['success'], 'Valid successful HTTP status accepted' );
$pending_filter = function () { return 'pending'; };
add_filter( 'an_mc_default_subscription_status', $pending_filter );
AN_MC_Form::submit( $request );
verify( 'pending' === json_decode( end( $calls )[1]['body'], true )['status'], 'Subscription status filter retained' );
remove_filter( 'an_mc_default_subscription_status', $pending_filter );

$api->set_apikey( 'test-us1' );

// Pagination is exercised for all three collection shapes, including a short page with total_items.
foreach ( array( 'lists' => 'name', 'categories' => 'title', 'interests' => 'name' ) as $key => $title_key ) {
	$http = function ( $url ) use ( $key, $title_key ) {
		parse_str( parse_url( $url, PHP_URL_QUERY ), $query );
		$offset = (int) $query['offset'];
		$items = array();
		for ( $i = $offset; $i < min( $offset + 100, 101 ); ++$i ) {
			$items[] = array( 'id' => 'id' . $i, $title_key => 'Title ' . $i );
		}
		return response( array( $key => $items, 'total_items' => 101 ) );
	};
	$items = 'lists' === $key ? $api->get_lists() : ( 'categories' === $key ? $api->get_interest_categories( 'audience' ) : $api->get_interests( 'audience', 'category' ) );
	verify( 101 === count( $items ), $key . ' paginates beyond 100' );
}
$http = function () { return response( array( 'lists' => array(), 'total_items' => 0 ) ); };
$api->set_apikey( 'test-us1' );
verify( array() === $api->get_account_subscribe_lists( true ), 'Empty account synchronization succeeds' );
$before = count( $calls );
verify( array() === $api->get_account_subscribe_lists(), 'Empty account cache retained' );
verify( $before === count( $calls ), 'Empty cache avoids repeated HTTP' );
$http = function ( $url ) {
	if ( false !== strpos( $url, '/interest-categories' ) ) { return new WP_Error( 'timeout', 'Nested failure' ); }
	return response( array( 'lists' => array( array( 'id' => 'audience', 'name' => 'Audience' ) ), 'total_items' => 1 ) );
};
$lists = $api->get_account_subscribe_lists( true );
verify( isset( $lists['error'] ) && 'Nested failure' === $lists['error'], 'Nested synchronization error propagated' );
verify( array() === get_option( 'ns_mc_account_subscribe_lists' ), 'Partial synchronization never replaces complete cache' );
$http = function () { return response( array( 'detail' => 'API error' ), 401 ); };
verify( array( 'error' => 'API error' ) === $api->get_account_subscribe_lists( true ), 'List error uses consistent array contract' );
$http = function () { return response( array( 'lists' => array(), 'total_items' => 0 ) ); };
$plugin = AN_MC_Plugin::get_instance();
$clean = $plugin->save_settings( array( 'api-key' => ' test-us2 ', 'mpam_sync' => 'Synchronize', 'unrecognized' => array() ) );
verify( array( 'api-key' => 'test-us2' ) === $clean, 'Settings sanitized, transient submit controls removed' );
update_option( 'ns_mc_options', $clean );
verify( 'test-us2' === $plugin->get_mcapi()->api_key, 'API client refreshed after credential change' );
verify( hash( 'sha256', 'test-us2' ) === get_option( 'ns_mc_account_subscribe_lists_key' ), 'New successful synchronization survives credential update' );
reset_key( 'test-us3' );
verify( false === get_option( 'ns_mc_account_subscribe_lists' ), 'External credential change invalidates cache' );
verify( array( 'api-key' => 'test-us3' ) === $plugin->save_settings( array( 'api-key' => array() ) ), 'Malformed settings safely normalized' );
verify( array( 'api-key' => 'test-us3' ) === $plugin->save_settings( array( 'api-key' => 'bad.invalid' ) ), 'Invalid key retains saved credentials' );

// Real shortcode parsing, WordPress escaping, widget rendering and template hooks.
reset_key();
$writes = 0;
$write_observer = function ( $option ) use ( &$writes ) { if ( 'sh_an_sh_mailchimp' === $option ) { ++$writes; } };
add_action( 'updated_option', $write_observer );
add_action( 'added_option', $write_observer );
$html = do_shortcode( '[mp-mc-form list="audience"]' );
verify( false === strpos( $html, '_first_name"' ) && false === strpos( $html, '_last_name"' ), 'Shortcode defaults do not collect names' );
verify( false !== strpos( $html, 'placeholder="Your E-mail"' ), 'Shortcode placeholder defaults true' );
$html .= do_shortcode( '[mp-mc-form list="audience" firstname="true" lastname="true" placeholder="false" button="Say &quot;Hi&quot; &#93;"]' );
$widget = new AN_Widget_MailChimp();
$widget->_set( 2 );
ob_start();
$widget->widget( array(), array( 'current_mailing_list' => 'audience', 'collect_first' => true ) );
$widget->widget( array(), array( 'current_mailing_list' => array( 'audience' ), 'collect_last' => true ) );
$html .= ob_get_clean();
verify( 0 === $writes, 'Rendering does not persist shortcode settings' );
verify( false !== strpos( $html, 'Say &quot;Hi&quot; ]' ), 'Entity-encoded popup attributes round-trip' );
$editor_html = do_shortcode( '[mp-mc-form list="audience" button="Say &amp;quot;Hi&amp;quot; &amp;#93;"]' );
verify( false !== strpos( $editor_html, 'Say &quot;Hi&quot; ]' ), 'HTML-serialized editor attributes round-trip' );
preg_match_all( '/\sid="([^"]+)"/', $html, $ids );
verify( count( $ids[1] ) === count( array_unique( $ids[1] ) ), 'Field and form IDs unique across repeated widgets/shortcodes' );
verify( 4 === substr_count( $html, 'name="an_mc_signature"' ), 'Every rendered form includes a signature' );
$http = function () { return response( array( 'detail' => 'Unavailable' ), 503 ); };
ob_start();
$widget->form( array() );
AN_MC_View::get_instance()->get_template( 'mailchimp-lists', array( 'lists' => array( 'error' => 'Unavailable' ), 'current_mailing_list' => array() ) );
$admin_html = ob_get_clean();
verify( false !== strpos( $admin_html, 'configure Mailchimp' ), 'Widget editor safely displays API errors' );
wp_set_current_user( 1 );
$http = function () { return response( array( 'lists' => array(), 'total_items' => 0 ) ); };
ob_start();
$plugin->admin_page();
$admin_html = ob_get_clean();
verify( false !== strpos( $admin_html, 'ns_mc_options[api-key]' ), 'Settings page renders through Settings API' );
$update = $widget->update( array( 'current_mailing_list' => array( 'audience/group' ), 'success_message' => '<strong>Saved</strong>', 'signup_text' => 'A & B' ), array() );
verify( false === $update['showplaceholder'] && '<strong>Saved</strong>' === $update['success_message'] && 'A & B' === $update['signup_text'], 'Widget updates preserve markup/text without storing output escapes' );
$template_hooks = 0;
$hook = function () use ( &$template_hooks ) { ++$template_hooks; };
add_action( 'an_mc_before_template_part', $hook );
ob_start();
AN_MC_View::get_instance()->render_template( 'notice/change-settings' );
$rendered = ob_get_clean();
verify( '' !== $rendered && 1 === $template_hooks, 'Legacy template renderer resolves existing files and preserves template hooks' );
verify( $rendered === AN_MC_View::get_instance()->render_html( 'notice/change-settings', null, false ), 'HTML renderer returns resolved template' );
remove_action( 'an_mc_before_template_part', $hook );
$plugin->register_settings();
update_option( 'ns_mc_options', array( 'api-key' => ' test-us4 ', 'mpam_sync' => 'Synchronize' ) );
verify( array( 'api-key' => 'test-us4' ) === get_option( 'ns_mc_options' ), 'Actual Settings API sanitization saves normalized credentials only' );
verify( hash( 'sha256', 'test-us4' ) === get_option( 'ns_mc_account_subscribe_lists_key' ), 'Actual settings update keeps complete synchronized cache' );
$previous_get = $_GET;
delete_user_meta( get_current_user_id(), 'ignore_error_notice' );
$_GET = array( 'dismiss_another-mailchimp' => 'yes' );
$plugin->dismiss_admin_notice();
verify( '' === get_user_meta( get_current_user_id(), 'ignore_error_notice', true ), 'Notice dismissal requires a nonce' );
$_GET['_wpnonce'] = wp_create_nonce( 'an_mc_dismiss_notice' );
$plugin->dismiss_admin_notice();
verify( 'yes' === get_user_meta( get_current_user_id(), 'ignore_error_notice', true ), 'Authorized notice dismissal accepts valid nonce' );
$_GET = $previous_get;
// Verify routing, method rejection, JSON termination and legacy-cache failures in fresh requests.
function routed_request( $method, $request ) {
	$process = proc_open( array( PHP_BINARY, __DIR__ . '/request.php', $method, base64_encode( wp_json_encode( $request ) ) ),
		array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes );
	fclose( $pipes[0] );
	$output = stream_get_contents( $pipes[1] );
	$errors = stream_get_contents( $pipes[2] );
	fclose( $pipes[1] );
	fclose( $pipes[2] );
	$status = proc_close( $process );
	verify( 0 === $status && '' === $errors, 'HTTP callback exits cleanly: ' . $errors );
	return 'NOT_HANDLED' === $output ? $output : json_decode( $output, true );
}
verify( true === routed_request( 'POST', signed_request() )['success'], 'Signed POST handled by real parse_request callback' );
verify( false === routed_request( 'GET', signed_request() )['success'], 'Signed GET cannot subscribe' );
verify( false === routed_request( 'POST', array( 'mp_am_type' => 'shortcode', 'widget_id' => 'form' ) )['success'], 'Legacy unsigned POST fails explicitly' );
verify( false === routed_request( 'GET', array( 'mp_am_type' => 'widget', 'widget_id' => 'form' ) )['success'], 'Legacy unsigned GET fails explicitly' );
verify( 'NOT_HANDLED' === routed_request( 'POST', array( 'widget_id' => 'unrelated' ) ), 'Unrelated requests remain untouched' );
echo "PASS: $count checks on PHP " . PHP_VERSION . ' / WordPress ' . $wp_version . "\n";
