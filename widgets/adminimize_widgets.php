<?php
/**
 * Class providing the widgets displayed on the Adminimize options page
 *
 * PHP version 5.2
 *
 * @category   PHP
 * @package    WordPress
 * @subpackage Inpsyde\Adminimize
 * @author     Ralf Albert <me@neun12.de>
 * @license    GPLv3 http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    1.0
 * @link       http://wordpress.com
 */

if ( ! class_exists( 'Adminimize_Widgets' ) ) {

class Adminimize_Widgets implements I_Adminimize_Widgets_Provider
{

	/**
	 * Screen object
	 * @var object
	 */
	public $screen = null;

	/**
	 * Availbale columns
	 * @var array
	 */
	public $columns = array();

	/**
	 * Array for default widget attributes
	 * @var array
	 */
	public $default_widget_attr = array();

	/**
	 * Array with attributes of each available widget
	 * @var array
	*/
	public $widgets_attributes = array();

	/**
	 * Array with classname => widget objects
	 * @var array
	 */
	protected $widgets_objects = array();

	/**
	 * Array with option names used by the widgets
	 * @var array
	*/
	public $used_options = array();

	/**
	 * Initialize the common function class and the templater class
	 * @param boolean $recursive Set to true when call from inside the class ( new self( true ); ) to prevent recursion
	*/
	public function __construct() {}

	/**
	 * Returns an array with the used option names
	 * @return array
	 */
	public function get_used_options() {

		if ( ! empty( $this->used_options ) )
			return $this->used_options;

		$this->used_options = array();

		// get widgets if not already done
		if ( empty( $this->widgets_attributes ) )
			$this->get_widgets_attributes();

		foreach ( $this->widgets_attributes as $attr ) {
			if ( isset( $attr['option_name'] ) )
				array_push( $this->used_options, $attr['option_name'] );
		}

		$this->used_options = array_unique( $this->used_options );

		return $this->used_options;

	}

	/**
	 * Returns a list with widget instances
	 * @return array $widget_objects List with widget instances
	 */
	public function get_widgets() {

		if ( empty( $this->widgets_objects ) )
			$this->read_widget_dir();

		return $this->widgets_objects;

	}

	/**
	 * (non-PHPdoc)
	 * @see I_Adminimize_Widgets_Provider::get_widgets_attributes()
	 */
	public function get_widgets_attributes() {

		if ( empty( $this->columns ) || empty( $this->default_widget_attr ) )
			return array();

		if ( empty( $this->widgets_objects ) )
			$this->read_widget_dir();

		foreach ( $this->widgets_objects as $widget ) {

			$attr = $this->sanitize_attrs( $widget->get_attributes(), $widget );

			$this->widgets_attributes[] = array_merge( $this->default_widget_attr, $attr );

		}

		return $this->widgets_attributes;

	}

	/**
	 * (non-PHPdoc)
	 * @see I_Adminimize_Widgets_Provider::get_widgets_actions()
	 */
	public function get_widgets_actions() {

		$hooks = array();

		if ( empty( $this->widgets_objects ) )
			$this->read_widget_dir();

		foreach ( $this->widgets_objects as $widget ) {

			$actions_and_filters = $widget->get_hooks();

			if ( ! empty( $actions_and_filters ) )
				$hooks[] = $actions_and_filters;

		}

		return $hooks;

	}

	/**
	 * (non-PHPdoc)
	 * @see I_Adminimize_Widgets_Provider::get_widgets_validation_callbacks()
	 */
	public function get_widgets_validation_callbacks() {

		$callbacks = array();

		if ( empty( $this->widgets_objects ) )
			$this->read_widget_dir();

		foreach ( $this->widgets_objects as $widget ) {

			$validation_callback = $widget->get_validation_callback();

			if ( ! empty( $validation_callback ) )
				$callbacks[ get_class( $widget ) ] = $validation_callback;

		}

		return $callbacks;

	}

	/**
	 * Get all widgets from widget directory and create an instance of each
	 * @return arary	Array with classname => widget object
	 */
	public function read_widget_dir() {

		$dir_pattern = sprintf(
				'%s/%s/*_widget.php',
				dirname( __FILE__ ),
				str_replace( '/', '', Adminimize_Registry::WIDGET_DIR )
		);

		// get the widgets
		$widgets = glob( $dir_pattern );

		foreach ( $widgets as $widget ) {

			require_once $widget;

			$class = str_replace( '.php', '', basename( $widget ) );
			$obj = new $class();

			// skip widgets which are not an instance of the widget base class
			if ( ! $obj instanceof Adminimize_Base_Widget ) {
				unset( $obj );
				continue;
			}

			$this->widgets_objects[ $class ] = $obj;

		}

		return $this->widgets_objects;

	}

	/**
	 * Sanitizing the attributes for a widget
	 * @param		array		$attr	Array with attributes
	 * @param		object	$obj	Widget class for building the callback
	 * @return	array		$attr	Array with sinitized values
	 */
	public function sanitize_attrs( $attr, $obj ) {

		$col = (int) $attr['context'];

		$attr['context'] = ( isset( $this->columns[ $col ] ) ) ?
			$this->columns[ $col ] : $this->columns[0];

		$attr['callback'] = isset( $attr['callback'] ) ? $attr['callback'] : array( $obj, 'content' );

		$attr['post_type'] = ( isset( $attr['post_type'] ) && ! empty( $attr['post_type'] ) ) ?
			$attr['post_type'] : $this->screen;

		return $attr;

	}

}

}