<?php

/**
 * @wordpress-plugin
 * Plugin Name:       SDL for Polylang
 * Plugin URI:        http://languagecloud.sdl.com/
 * Description:       SDL Language Cloud integration for Poylang, for translating WordPress site content.
 * Version:           1.0.0
 * Author:            SDL
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-polylang-sdl-activator.php
 */
function activate_polylang_sdl() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-polylang-sdl-activator.php';
	Polylang_SDL_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-polylang-sdl-deactivator.php
 */
function deactivate_polylang_sdl() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-polylang-sdl-deactivator.php';
	Polylang_SDL_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_polylang_sdl' );
register_deactivation_hook( __FILE__, 'deactivate_polylang_sdl' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-polylang-sdl.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_polylang_sdl() {

	$plugin = new Polylang_SDL();
	$plugin->run();

}
run_polylang_sdl();
