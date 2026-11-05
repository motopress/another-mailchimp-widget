<?php
/**
 * Plugin Name: Another Mailchimp Widget
 * Plugin URI: https://motopress.com/products/another-mailchimp-widget/
 * Description: Simple Mailchimp subscription form to your lists and groups.
 * Author: MotoPress
 * Requires at least: 6.9
 * Requires PHP: 7.4
 * Version: 2.2.0
 * Author URI: https://motopress.com/
 * License: GPL2
 * Text Domain: another-mailchimp-widget
 * Domain Path: /languages
 */

if ( ! defined( 'AN_MC_PLUGIN_VERSION' ) ) {
	define( 'AN_MC_PLUGIN_VERSION', '2.2.0' );
}

if ( ! defined( 'AN_MC_PLUGIN_FILE' ) ) {
	define( 'AN_MC_PLUGIN_FILE', __FILE__ );
}

register_activation_hook( __FILE__, function () {
	Another_mailChimp_widget::get_instance()->on_activation();
} );
add_action( 'plugins_loaded', array( 'Another_mailChimp_widget', 'get_instance' ) );

/**
 * Class Another_mailChimp_widget
 */
class Another_mailChimp_widget {

	protected static $instance;

	/**
	 * Another_mailChimp_widget constructor.
	 */
	public function __construct() {
		
		spl_autoload_register( array( $this, 'buffered_autoloader' ) );
		
		$this->include_all();

		if ( ! defined( 'AN_MC_TEMPLATE_PATH' ) ) {
			define( 'AN_MC_TEMPLATE_PATH', $this->template_path() );
		}
		if ( ! defined( 'AN_MC_PLUGIN_URL' ) ) {
			define( 'AN_MC_PLUGIN_URL', $this->get_plugin_url() );
		}
		if ( ! defined( 'AN_MC_TEMPLATES_PATH' ) ) {
			define( 'AN_MC_TEMPLATES_PATH', $this::get_plugin_dir() . 'templates/' );
		}
		$this->init();
		
	}
	
	/**
	 * Include classes into plugin
	 */
	public function include_all() {
		
		$plugin_dir = $this::get_plugin_dir();
		
		include $plugin_dir . 'classes/class-view.php';

		include $plugin_dir . 'functions/functions.php';
		
		require_once $plugin_dir . 'lib/an_mc_form.class.php';
		require_once $plugin_dir . 'lib/an_mc_plugin.class.php';
		
		include_once $plugin_dir . 'shortcodes/registrator.php';
		
	}
	
	/**
	 *  Get plugin dir
	 * @return string
	 */
	public static function get_plugin_dir() {
		if ( ! defined( 'AN_MC_PLUGIN_PATH' ) ) {
			
			$file = Another_mailChimp_widget::get_plugin_file();
			$path = trailingslashit( plugin_dir_path( $file ) );
			
			define( 'AN_MC_PLUGIN_PATH', $path );
		} else {
			$path = AN_MC_PLUGIN_PATH;
		}
		
		return $path;
	}
	
	/**
	 * Get plugin File
	 *
	 * @return string
	 */
	public static function get_plugin_file() {
		global $wp_version, $network_plugin;
		
		if ( version_compare( $wp_version, '3.9', '<' ) && isset( $network_plugin ) ) {
			$pluginFile = $network_plugin;
		} else {
			$pluginFile = __FILE__;
		}
		
		return $pluginFile;
	}
	
	/**
	 * init plugin data
	 */
	public function init() {
		AN_MC_Plugin::get_instance()->init_action();
	}
	
	/**
	 * Get the template path.
	 *
	 * @return string
	 */
	public function template_path() {
		return apply_filters( 'an_mc_template_path', 'another-mailchimp-widget/' );
	}
	
	/**
	 * Get plugin URL
	 */
	public function get_plugin_url() {
		$path = plugins_url( plugin_basename( __DIR__ ) );
		
		return trailingslashit( $path );
	}
	
	/**
	 * @return Another_mailChimp_widget
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * On activation
	 */
	public function on_activation() {
		AN_MC_Plugin::get_instance()->set_up_options();
	}
	
	/**
	 * On deactivation
	 */
	public function on_deactivation() {
	}

	/**
	 * on uninstall
	 */
	public function on_uninstall() {
	}
	
	/**
	 *
	 * Autoloader
	 *
	 * @param $class
	 *
	 * @return string
	 */
	public function buffered_autoloader( $class ) {
		$classes = array(
			'an_mc_plugin' => 'an_mc_plugin.class.php',
			'an_widget_mailchimp' => 'an_widget_mailchimp.class.php',
			'mailchimp_api' => 'mailchimp_api.class.php',
			'widget_default_an_mc' => 'Default_MC.php',
			'an_mc_form' => 'an_mc_form.class.php',
		);
		$key = strtolower( $class );
		if ( isset( $classes[ $key ] ) ) {
			require_once self::get_plugin_dir() . 'lib/' . $classes[ $key ];
		}
	}
}
