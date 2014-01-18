<?php
/**
 * @package   Events_Toolkit
 * @author    Barry Ceelen <b@rryceelen.com>
 * @license   GPL-2.0+
 * @link      http://github.com/barryceelen/wp-events-toolkit
 * @copyright 2013 Barry Ceelen
 *
 * @wordpress-plugin
 * Plugin Name: Events Toolkit
 * Plugin URI:  http://github.com/barryceelen/wp-events-toolkit
 * Description: Registers an event post type and related functionality.
 * Version:     0.0.2
 * Author:      Barry Ceelen
 * Author URI:  http://github.com/barryceelen
 * Text Domain: events-toolkit
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'class-events-toolkit-custom-post-type-admin.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'class-events-toolkit-meta-box-date.php' );
}

require_once( plugin_dir_path( __FILE__ ) . 'class-events-toolkit-custom-post-type.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-events-toolkit.php' );

// Register hooks that are fired when the plugin is activated and deactivated.
register_activation_hook( __FILE__, array( 'Events_Toolkit', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Events_Toolkit', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Events_Toolkit', 'get_instance' ) );
