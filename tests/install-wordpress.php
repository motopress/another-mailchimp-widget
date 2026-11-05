<?php
/** Install an isolated, disposable WordPress site for local regression checks. */
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
$root = getenv( 'AN_MC_WP_ROOT' );
if ( ! $root || ! is_file( $root . '/wp-load.php' ) || is_file( $root . '/wp-config.php' ) ) {
	throw new RuntimeException( 'AN_MC_WP_ROOT must point to a fresh disposable WordPress download.' );
}
$config = <<<'CONFIG'
<?php
define( 'DB_NAME', 'an_mc_test' );
define( 'DB_USER', 'unused' );
define( 'DB_PASSWORD', 'unused' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );
define( 'AUTH_KEY', 'test-auth-key' );
define( 'SECURE_AUTH_KEY', 'test-secure-key' );
define( 'LOGGED_IN_KEY', 'test-logged-key' );
define( 'NONCE_KEY', 'test-nonce-key' );
define( 'AUTH_SALT', 'test-auth-salt' );
define( 'SECURE_AUTH_SALT', 'test-secure-salt' );
define( 'LOGGED_IN_SALT', 'test-logged-salt' );
define( 'NONCE_SALT', 'test-nonce-salt' );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'DISABLE_WP_CRON', true );
$table_prefix = 'wp_';
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ . '/' ); }
require_once ABSPATH . 'wp-settings.php';
CONFIG;
file_put_contents( $root . '/wp-config.php', $config );
if ( ! is_dir( $root . '/wp-content/themes' ) ) {
	mkdir( $root . '/wp-content/themes', 0777, true );
}
copy( $root . '/wp-content/plugins/sqlite-database-integration/db.copy', $root . '/wp-content/db.php' );
symlink( dirname( __DIR__ ), $root . '/wp-content/plugins/another-mailchimp-widget' );
define( 'WP_INSTALLING', true );
require $root . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
wp_install( 'Compatibility test', 'compat_admin', 'compat@example.test', false, '', 'local-test-password' );
$result = activate_plugin( 'another-mailchimp-widget/another-mailchimp-widget.php' );
if ( is_wp_error( $result ) ) {
	throw new RuntimeException( $result->get_error_message() );
}
echo 'Installed WordPress ' . $wp_version . " and activated plugin.\n";
