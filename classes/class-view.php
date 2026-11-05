<?php

/**
 * AN_MC_View class
 */
class AN_MC_View {
	
	protected static $instance;
	public $template;
	protected $template_path;
	protected $templates_path;
	protected $prefix = 'an_mc';
	private $data;
	
	/**
	 * AN_MC_View constructor.
	 */
	public function __construct() {
		$this->template_path  = AN_MC_TEMPLATE_PATH;
		$this->templates_path = AN_MC_TEMPLATES_PATH;
	}
	
	/**
	 * @return AN_MC_View
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Render template
	 *
	 * @param string $template
	 * @param array $data
	 */
	function render_template( $template = null, $data = null ) {
		if ( ! is_string( $template ) || '' === $template ) {
			return;
		}
		$this->template = $template;
		$this->data = $data;
		$this->get_template( $template, is_array( $data ) ? $data : array() );
	}

	/**
	 * Render html
	 *
	 * @param string $template
	 * @param array $data
	 * @param bool $output : true - echo , false - return
	 *
	 * @return string
	 */
	
	public function render_html( $template, $data = null, $output = true ) {
		$this->data = $data;
		$out = $this->get_template_html( $template, is_array( $data ) ? $data : array() );
		if ( $output ) {
			echo $out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			return $out;
		}
	}

	/**
	 * Get template part theme/plugin
	 *
	 * @param string $name
	 * @param string $slug
	 *
	 * @return void
	 */
	public function get_template_part( $slug, $name = '' ) {
		$template = '';
		
		// Look in your-theme/slug-name.php and your-theme/mp-restaurant-menu/slug-name.php
		if ( $name ) {
			$template = locate_template( array( "{$slug}-{$name}.php", $this->template_path . "{$slug}-{$name}.php" ) );
		}
		
		// Get default slug-name.php
		if ( ! $template && $name && file_exists( $this->templates_path . "{$slug}-{$name}.php" ) ) {
			$template = $this->templates_path . "{$slug}-{$name}.php";
		}
		
		// If template file doesn't exist, look in your-theme/slug.php and your-theme/mp-restaurant-menu/slug.php
		if ( ! $template ) {
			$template = locate_template( array( "{$slug}.php", $this->template_path . "{$slug}.php" ) );
		}
		
		if ( ! $template && file_exists( $this->templates_path . $slug . '.php' ) ) {
			$template = $this->templates_path . $slug . '.php';
		}

		// Allow 3rd party plugins to filter template file from their plugin.
		$template = apply_filters( $this->prefix . '_get_template_part', $template, $slug, $name );
		
		if ( $template ) {
			load_template( $template, false );
		}
	}
	
	/**
	 * Get template html
	 *
	 * @param $template_name
	 * @param array $args
	 * @param string $template_path
	 * @param string $default_path
	 *
	 * @return mixed/void
	 */
	public function get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
		ob_start();
		
		$this->get_template( $template_name, $args, $template_path, $default_path );
		
		return ob_get_clean();
	}
	
	/**
	 * Get template
	 *
	 * @param $template_name
	 * @param array $args
	 * @param string $template_path
	 * @param string $default_path
	 */
	public function get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
		$template_name = $template_name . '.php';
		
		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args, EXTR_SKIP );
		}
		
		$located = $this->locate_template( $template_name, $template_path, $default_path );
		
		if ( ! file_exists( $located ) ) {
			_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $located ), '2.1' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			
			return;
		}
		
		// Allow 3rd party plugin filter template file from their plugin.
		$located = apply_filters( $this->prefix . '_get_template', $located, $template_name, $args, $template_path, $default_path );
		
		do_action( $this->prefix . '_before_template_part', $template_name, $template_path, $located, $args );
		
		include( $located );
		
		do_action( $this->prefix . '_after_template_part', $template_name, $template_path, $located, $args );
	}
	
	/**
	 * Locate template
	 *
	 * @param $template_name
	 * @param string $template_path
	 * @param string $default_path
	 *
	 * @return mixed
	 */
	function locate_template( $template_name, $template_path = '', $default_path = '' ) {
		if ( ! $template_path ) {
			$template_path = $this->template_path;
		}
		
		if ( ! $default_path ) {
			$default_path = $this->templates_path;
		}
		
		// Look within passed path within the theme - this is priority.
		$template_args = array( trailingslashit( $template_path ) . $template_name, $template_name );
		
		$template = locate_template( $template_args );
		
		// Get default template/
		if ( ! $template ) {
			$template = $default_path . $template_name;
		}
		
		// Return what we found.
		return apply_filters( $this->prefix . '_locate_template', $template, $template_name, $template_path );
	}
}