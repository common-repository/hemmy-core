<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @wordpress-plugin
 * Plugin Name:       Hemmy Core
 * Plugin URI:        http://abileweb.com/hemmy-core/
 * Description:       This is core plugin of hemmy theme
 * Version:           1.0.0
 * Author:            Abileweb
 * Author URI:        http://abileweb.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       hemmy-core
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$cur_theme = wp_get_theme();	
if ( $cur_theme->get( 'Name' ) != 'Hemmy' && $cur_theme->get( 'Name' ) != 'Hemmy Child' ){
	return;
}

define( 'HEMMY_CORE_DIR', plugin_dir_path( __FILE__ ) );
define(	'HEMMY_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-hemmy-core-activator.php
 */
function activate_hemmy_core() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-hemmy-core-activator.php';
	Hemmy_Core_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-hemmy-core-deactivator.php
 */
function deactivate_hemmy_core() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-hemmy-core-deactivator.php';
	Hemmy_Core_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_hemmy_core' );
register_deactivation_hook( __FILE__, 'deactivate_hemmy_core' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-hemmy-core.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_hemmy_core() {

	$plugin = new Hemmy_Core();
	$plugin->run();

}
run_hemmy_core();
