<?php

/*
 * @wordpress-plugin
 * Plugin Name:       SDL Managed Translation for Polylang
 * Plugin URI:        http://languagecloud.sdl.com/
 * Description:       SDL Managed Translation integration for Poylang, for translating WordPress site content.
 * Version:           1.0.0
 * Author:            SDL
 * Text Domain:		  managedtranslation
 * License:     GPL3

 Copyright (C) 2017  SDL

 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.
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

function activate_polylang_sdl() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-polylang-sdl-activator.php';
	Polylang_SDL_Activator::activate();
}

function deactivate_polylang_sdl() {
	error_log('SDL: Removing CRON event', 0);
	wp_clear_scheduled_hook('poll_projects');
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
	if(class_exists('Polylang')) {
		$plugin = new Polylang_SDL();
		$plugin->run();

		if (! wp_next_scheduled ( 'poll_projects' )) {
			error_log('SDL: Adding CRON event', 0);
			wp_schedule_event(time(), '15minutes', 'poll_projects');
		}
	}
}
add_action('plugins_loaded', 'run_polylang_sdl');

function test_dependencies()
{
	if ( ! function_exists( 'deactivate_plugins' ) ) {
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	}
	if(!class_exists('Polylang')) {
		deactivate_plugins( plugin_basename( __FILE__) );
		wp_die( __( 'Please install and activate Polylang before enabling SDL Managed Translation plugin.', 'managedtranslation' ), 'Plugin dependency check', array( 'back_link' => true ) );
	}
	if(!function_exists('pll_languages_list')) {
		deactivate_plugins( plugin_basename( __FILE__) );
		wp_die( __( 'Polylang installation is corrupted, or an incompatible version.', 'managedtranslation' ), 'Plugin dependency check', array( 'back_link' => true ) );
	}
	if (!is_plugin_active_for_network( plugin_basename(__FILE__)) && is_multisite() ) {
		deactivate_plugins( plugin_basename( __FILE__) );
		wp_die( __( 'Multisite setup detected. Please activate plugin via Network administration screen.', 'managedtranslation' ), 'Plugin scope check', array( 'back_link' => true ) );
	}
}
add_action('admin_init', 'test_dependencies');

function sdl_poll_projects(){
	$inprogress = get_option('sdl_translations_inprogress');
	$api = new Polylang_SDL_API;
	//Test if anything is in progress
	error_log('SDL: Polling for project updates', 0);
	error_log('SDL: Currently ' . sizeof($inprogress) . ' in progress', 0);
	if(is_array($inprogress) && sizeof($inprogress) > 0) {
		foreach($inprogress as $project) {
			error_log('SDL: Looking up '. $project, 0);
			$status = $api->project_getStatusCode($project);
			//Test if any are now listed as complete
			error_log('SDL: Current status code ' . $status, 0);
			if($status == 3 || $status == 4 || $status == 5) {
				$file = $api->translation_download($project);
				//Test that the download was successful
				if($file) {
					error_log('SDL: Download successful', 0);
					$unpack = new Polylang_SDL_Unpack_XLIFF;
					$posts = $unpack->convert($project);
					//Test that we successfull converted the XLIFF
					if(is_array($posts)) {
						error_log('SDL: Converted successfully', 0);
						$convertor = new Polylang_SDL_Local;
						foreach($posts as $post) {
							$saved_id = $convertor->save_post_translation($post);
							$post_model = new Polylang_SDL_Model;
							$post_model->process_in_progress($saved_id);
							error_log('SDL: Processed' . $saved_id, 0);
						}
						error_log('SDL: Posts saved', 0);

						//An update could have happened while saving, so let's refresh the array
						$latest = get_option('sdl_translations_inprogress');
						unset($latest[$project]);
						$api->verbose('Remaining in progress: ', array_diff($latest, [$project]));
						update_option('sdl_translations_inprogress', array_diff($latest, [$project]));
						$response = $api->project_updateStatus($project, 'complete');
					}
				}
			}
		}
	} else {
		$api->verbose('None in progress');
	}
}
add_action('poll_projects', 'sdl_poll_projects');

function sdl_get_post_language($id = null, $option = 'slug'){
	$lang = pll_get_post_language($id, $option);
	if($lang == '' || $lang == false || $lang == null) {
		$lang = pll_current_language();
		pll_set_post_language($id, $lang);
		$lang = pll_get_post_language($id, $option);
	}
	return $lang;
}

function get_formatted_locale($blog_id = null) {
	if($blog_id === null) {
		$site_lang = get_locale();
	} else {
		$network_lang = get_site_option('WPLANG');
		$site_lang = get_blog_option($blog_id, 'WPLANG');
		if($site_lang == '' || $site_lang == null) {
			$site_lang = $network_lang;
		}
	}
	return format_locale($site_lang);
}
function format_locale($locale){
	return str_replace('_', '-', $locale);
}
