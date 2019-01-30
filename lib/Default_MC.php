<?php

/**
 * Default widget class
 *
 * @abstract
 */
abstract class Widget_Default_AN_MC extends WP_Widget {
	
	/**
	 * Widget Prefix
	 * @var string
	 */
	protected $prefix;
	
	/**
	 * Textdomain for translation
	 * @var string
	 */
	protected $textdomain;
	
	/**
	 *
	 * @var string
	 */
	protected $classname = '';
	
	/**
	 * required if more than 250px
	 * @var int
	 */
	protected $width = 200;
	
	/**
	 * currently not used but may be needed in the future
	 * @var int
	 */
	protected $height = 350;
	
	/**
	 * shown on the configuration page.
	 * @var string
	 */
	protected $description = '';
	
	/**
	 * Name
	 * @var string
	 */
	protected $__name = '';
	
	/**
	 * Part of base_id. THEMENAME-{__id}
	 * @var $__id
	 */
	protected $__id = '';
	
	/**
	 * Delimiter between the name of the THEME and the name of the widget <br/>
	 * displayed on the configuration page
	 * @var string
	 */
	protected $name_delimiter = '';
	
	/**
	 * Wiget constructor
	 */
	function __construct() {
		parent::__construct( $this->getBaseId(), $this->getTranslatedName(), $this->getWidgetOption(), $this->getWidgetControlOption() );
	}
	
	/**
	 * Get  Base ID for the widget, lower case,
	 * if left empty a portion of the widget's class name will be used. Has to be unique.
	 * @return string
	 */
	protected function getBaseId() {
		$base_id = "{$this->getPrefix()}-{$this->getIdSuffix()}";
		
		return strtolower( $base_id );
	}
	
	/**
	 * Get wigget prefix( lowercase THEMNAME)
	 * @return string
	 */
	public function getPrefix() {
		return $this->prefix;
	}
	
	/**
	 * Set prefix
	 *
	 * @param $prefix
	 */
	protected function setPrefix( $prefix ) {
		$this->prefix = $prefix;
	}
	
	/**
	 * Get suffix part of id_base (THEMNAME-{suffix})
	 * @return string
	 */
	protected function getIdSuffix() {
		return $this->__id;
	}
	
	/**
	 * Translated name for the widget displayed on the configuration page/
	 * @return string
	 */
	protected function getTranslatedName() {
		return $this->getNameDelimiter() . $this->getName();
	}
	
	/**
	 * Get Delimetr for the THEMANAME and widget name displayed on the configuration page
	 * @return string
	 */
	protected function getNameDelimiter() {
		return $this->name_delimiter;
	}
	
	/**
	 * Set Delimetr for the THEMANAME and widget name displayed on the configuration page
	 *
	 * @param string $name_delimiter
	 */
	protected function setNameDelimiter( $name_delimiter ) {
		$this->name_delimiter = $name_delimiter;
	}
	
	/**
	 * Get widget name<br/>
	 * for the widget displayed on the configuration page
	 * @return string
	 */
	public function getName() {
		return $this->__name;
	}
	
	/**
	 * Set Widget name<br/>
	 * for the widget displayed on the configuration page
	 *
	 * @param string $name
	 */
	protected function setName( $name ) {
		$this->__name = $name;
	}
	
	/**
	 * Get classname and translated description
	 * @return array Optional Passed to wp_register_sidebar_widget()
	 *  - classname:
	 *  - description: shown on the configuration page
	 */
	protected function getWidgetOption() {
		$widget_ops = array(
			'classname'   => $this->getClassName(),
			'description' => $this->getDescription()
		);
		
		return $widget_ops;
	}
	
	/**
	 * Get widget classname
	 * @return string
	 */
	public function getClassName() {
		return $this->classname;
	}
	
	/**
	 * Set widget classname
	 *
	 * @param string $classname
	 */
	public function setClassName( $classname ) {
		$this->classname = $classname;
	}
	
	/**
	 * Get widget description for shown on the configuration page
	 * @return string
	 */
	public function getDescription() {
		
		return $this->description;
	}
	
	/**
	 * Set widget description for shown on the configuration page
	 *
	 * @param string $description shown on the configuration page
	 */
	public function setDescription( $description ) {
		$this->description = $description;
	}
	
	/**
	 * Get wodget control data
	 * @return array Passed to wp_register_widget_control()
	 *     - width: required if more than 250px
	 *     - height: currently not used but may be needed in the future
	 *     - id_base:
	 */
	protected function getWidgetControlOption() {
		$control_ops = array(
			'width'   => $this->getWidth(),
			'height'  => $this->getHeight(),
			'id_base' => $this->getBaseId()
		);
		
		return $control_ops;
	}
	
	/**
	 * Get Width
	 *
	 * @return int
	 */
	public function getWidth() {
		return $this->width;
	}
	
	/**
	 * Set Width
	 *
	 * @param $width
	 */
	public function setWidth( $width ) {
		$this->width = $width;
	}
	
	/**
	 * Get Height
	 *
	 * @return int
	 */
	public function getHeight() {
		
		return $this->height;
	}
	
	/**
	 * Set Height
	 *
	 * @param $height
	 */
	public function setHeight( $height ) {
		$this->height = $height;
	}
	
	/**
	 * Get textdomain for translation
	 * @return string
	 */
	public function getTextdomain() {
		return $this->textdomain;
	}
	
	/**
	 * Set text domain
	 *
	 * @param $textdomain
	 */
	protected function setTextdomain( $textdomain ) {
		$this->textdomain = $textdomain;
	}
	
	/**
	 * Set suffix part of id_base (THEMENAME-{suffix})
	 *
	 * @param string $suffix
	 */
	protected function setIdSuffix( $suffix ) {
		$this->__id = $suffix;
	}
	
	/**
	 * Check is plugin wpml is active
	 * @return boolean
	 */
	protected function isWPML_PluginActive() {
		return defined( 'ICL_LANGUAGE_CODE' );
	}
	
}
