<?php
/**
 * WordPress-Plugin Adminimize OOP
 *
 * PHP version 5.2
 *
 * @category   PHP
 * @package    WordPress
 * @subpackage AdminimizeOOP
 * @author     Ralf Albert <me@neun12.de>
 * @license    GPLv3 http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    0.1
 * @link       http://wordpress.com
 */

/**
 * Plugin Name: AdminimizeOOP
 * Plugin URI:  http://bueltge.de/wordpress-admin-theme-adminimize/674/
 * Text Domain: adminimize
 * Domain Path: /languages
 * Description: Visually compresses the administratrive meta-boxes so that more admin page content can be initially seen. The plugin that lets you hide 'unnecessary' items from the WordPress administration menu, for alle roles of your install. You can also hide post meta controls on the edit-area to simplify the interface. It is possible to simplify the admin in different for all roles.
 * Author:      Frank Bültge, Ralf Albert
 * Author URI:  http://bueltge.de/
 * Version:     2.0
 * License:     GPLv3
 */

!( defined( 'ABSPATH' ) ) AND die( 'Standing On The Shoulders Of Giants' );

global $wp_version;

if ( version_compare( $wp_version, "2.5alpha", "<" ) ) {
	$exit_msg = 'The plugin <em><a href="http://bueltge.de/wordpress-admin-theme-adminimize/674/">Adminimize</a></em> requires WordPress 2.5 or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please update WordPress</a> or delete the plugin.';

	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit( $exit_msg );
}

add_action(
	'plugins_loaded',
	'adminimize_plugin_init',
	10,
	0
);

/**
 * Initialize the autoloader
 */
function adminimize_autoloader() {

	require_once 'classes/adminimize_autoload.php';

	// do not forget to include the widgets directory
	$dirs = array(
			dirname( __FILE__ ) . '/classes',
			dirname( __FILE__ ) . '/widgets',
	);

	new Adminimize_Autoload( $dirs );

}

/**
 * Initialize the plugin
 * - Init autoloader
 * - Add hooks&filters on plugins loaded
 *
 * @hook	plugins_loaded
 * @return	boolean		False on ajax, xmlrpc and iframe requests, else true
 */
function adminimize_plugin_init() {

	if ( ! is_admin() )
		return false;

	adminimize_autoloader();

	// adding the widget hooks early
	$registry = new Adminimize_Registry();
	$registry->add_widgets_hooks();

	// setup basedirs
	$storage = $registry->get_storage();
	$storage->set_basedirs( __FILE__ );

	// initialize the PluginHeaderReader through the registry
	$pluginheaders = $registry->get_pluginheaders( __FILE__ );

	if ( ! defined( 'ADMINIMIZE_TEXTDOMAIN' ) )
		define( 'ADMINIMIZE_TEXTDOMAIN', $pluginheaders->TextDomain );

	add_action(
		'admin_init',
		'adminimize_on_admin_init',
		10,
		0
	);

	// adds the options page
	add_action(
		'init',
		'adminimize_add_options_page',
		10,
		0
	);


/*
 * get the widgets with a remote request
 */
//FIXME: remove hardcoded option name
 $widgets = $storage->get_option( 'available_dashboard_widgets' );
// die(var_dump($widgets));
//  if ( empty( $widgets ) ) {

//  	$cookies = array();

// 	 	foreach ( $_COOKIE as $name => $value ) {
// 	 		if ( 'PHPSESSID' === $name )
// 	 			continue;
// 	 		$cookies[] = new WP_Http_Cookie( array( 'name' => $name, 'value' => $value ) );
// 	 	}

// 	 	wp_remote_get( admin_url( '/index.php' ), array( 'cookies' => $cookies ) );

//  }

}

function adminimize_on_admin_init() {

	$registry      = new Adminimize_Registry();
	$storage       = $registry->get_storage();
	$pluginheaders = $registry->get_pluginheaders();
	$pluginstarter = new Plugin_Starter();

	$pluginstarter->basename = $storage->basename;
	$pluginstarter->load_textdomain( $pluginheaders );
	$pluginstarter->load_styles( array( 'adminimize-style' => array( 'file' => '/css/style.css', 'enqueue' => false ) ) );

}

function adminimize_add_options_page() {

	if ( ! is_admin() )
		return false;

	$storage  = new Adminimize_Storage();
	$opt_page = new Adminimize_Options_Page();
	$storage->options_page_object = $opt_page;

}


/*
 * Just for developing !!!
 */
register_deactivation_hook( __FILE__, 'adminimize_deactivate' );

function adminimize_deactivate() {
	$opt_key = Adminimize_Storage::OPTION_KEY;
	delete_option( $opt_key );
}