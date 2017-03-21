<?php

/**
 * @wordpress-plugin
 * Plugin Name:       SDL for Polylang
 * Plugin URI:        http://languagecloud.sdl.com/
 * Description:       SDL Managed Translation integration for Poylang, for translating WordPress site content.
 * Version:           1.0.0
 * Author:            SDL
 * Text Domain:		  languagecloud
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


//By default, hourly is the smallest interval available to WP-CRON. We add a custom one here 
add_filter('cron_schedules', 'custom_scheduled_interval');
function custom_scheduled_interval($schedules) {
	$schedules['15minutes'] = array('interval'=>900, 'display'=>__('Once every 15 minutes'));
	return $schedules;
}
//The CRON taskf
if (! wp_next_scheduled ( 'poll_projects' )) {
	wp_schedule_event(time(), '15minutes', 'poll_projects');
}
add_action('poll_projects', 'sdl_poll_projects');
function sdl_poll_projects(){
	$inprogress = get_option('sdl_translations_inprogress');
	$api = new Polylang_SDL_API;
	if(is_array($inprogress)) {
		$api->verbose('Currently in progress: ', $inprogress);
		foreach($inprogress as $project) {
			$status = $api->project_getStatusCode($project);
			$api->verbose('Current status: ', $status);
			if($status == 3 || $status == 4 || $status == 5) {
				$file = $api->translation_download($project);
				$api->verbose('We just downloaded a new post translation');
				if($file) {
					$unpack = new Polylang_SDL_Unpack_XLIFF;
					$posts = $unpack->convert($project);
					if(is_array($posts)) {
						$convertor = new Polylang_SDL_Local;
						foreach($posts as $post) {
							$saved = $convertor->save_post_translation($post);
						}
						//An update could have happened while saving, so let's refresh the array
						$latest = get_option('sdl_translations_inprogress');
						unset($latest[$project]);
						$api->verbose('Remaining in progress: ', array_diff($latest, [$project]));
						update_option('sdl_translations_inprogress', array_diff($latest, [$project]));
						$api->project_updateStatus($project, 'complete');
					}
				}
			}
		}
	} else {	
		$api->verbose('None in progress');
	}
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

function get_formatted_locale($blog_id) {
	$network_lang = get_site_option('WPLANG');
	$site_lang = get_blog_option($blog_id, 'WPLANG', $network_lang);
	return str_replace('_', '-', $site_lang);
}