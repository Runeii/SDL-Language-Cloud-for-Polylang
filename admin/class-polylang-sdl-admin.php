<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://languagecloud.sdl.com
 * @since      1.0.0
 *
 * @package    Polylang_SDL
 * @subpackage Polylang_SDL/admin
 */
class Polylang_SDL_Admin {

	private $polylang_sdl;
	private $version;
	private $option_name;
	public	$admin_actions;

	public function __construct($polylang_sdl, $version) {
		if(is_admin()) {
			$this->polylang_sdl = $polylang_sdl;
			$this->version = $version;
			$this->register_interface();
			$polylang = new Polylang;
			new Polylang_SDL_Polylang_Integration;

			add_action( 'wp_loaded', array($this, 'process_actions') );
			add_action( 'current_screen', array($this, 'check_translated_post_screen') );
		}
	}
	public function process_actions(){
		$this->admin_actions = new Polylang_SDL_Admin_Actions();
	}
	public function check_translated_post_screen(){
		if ( is_admin() ) {
			$screen = get_current_screen();
			if($screen->base == 'edit' && pll_is_translated_post_type($screen->post_type)) {
				new Polylang_SDL_Admin_Posts;
			}
		}
	}
	public function enqueue_styles() {

		wp_enqueue_style( $this->polylang_sdl, plugin_dir_url( __FILE__ ) . 'css/polylang-sdl-admin.css', array(), $this->version, 'all' );

	}

	public function enqueue_scripts() {

		wp_enqueue_script( $this->polylang_sdl, plugin_dir_url( __FILE__ ) . 'js/polylang-sdl-admin.js', array( 'jquery' ), $this->version, false );

	}

	public function register_interface(){
		add_action('admin_menu', array($this, 'sdl_register_menu'));
		add_action('network_admin_menu', array($this, 'sdl_register_menu'));
	}

	public function sdl_register_menu(){
		// Temporarily lower permissions to Editor for debug
		add_menu_page('SDL Managed Translation settings', __( 'SDL Managed Translation','managedtranslation' ), 'read', 'managedtranslation', array($this, 'sdl_create_page'), 'dashicons-cloud');
	}

	public function sdl_create_page(){
   		new Polylang_SDL_Admin_Panel($this);
	}
	public function filter_project_options($blog = null, $debug = false){
		if($blog === null) {
			$existing_id = get_option('sdl_settings_projectoption');
		} else {
			$existing_id = get_blog_option($blog, 'sdl_settings_projectoption', '0');
		}
    $lang = get_formatted_locale($blog);
    $options = get_site_option('sdl_settings_projectoptions_all');
    if($options === null || $options === false) {
        $API = new Polylang_SDL_API();
        $options = $API->user_options();
    }
    $available_pairs = get_site_option('sdl_settings_projectoptions_pairs');
		$selector = '';
    $selector .= '<select name="sdl_settings_projectoption">';
    $selector .= '<option value="blank">– Select project options set –</option>';
    $count = 0;
    foreach($options as $option){
        if(in_array($lang, $available_pairs[$option['Id']]['Source'])) {
            if($option['Id'] === $existing_id) {
                $selector .= '<option value="'. $option['Id'] . '" selected="selected">' . $option['Name'] . '</option>';
            } else {
                $selector .= '<option value="'. $option['Id'] . '">' . $option['Name'] . '</option>';
            }
            $count++;
        } else if($debug === true) {
					echo $lang . ' not in ' . $option['Id'] . ' pairs <br />';
					echo 'Were looking for: ' . implode(', ', $available_pairs[$option['Id']]['Source']) . '<br /><br />';
				}
    }
    $selector .= '</select>';
		if($debug === true) {
			echo 'We were using formatted blog locale: ' . $lang;
		}
    if($count === 0) {
			$errorlog = array(
				'available_pairs' => get_site_option('sdl_settings_projectoptions_pairs'),
				'formatted_locale' => get_formatted_locale($blog),
				'projectoptions' => get_site_option('sdl_settings_projectoptions_all'),
				'site_lang' => get_locale(),
				'network_lang' => get_site_option('WPLANG'),
				'polylang_langs' => pll_languages_list(),
				'polylang_default_slug' => pll_default_language('slug'),
				'poylang_default_locale' => pll_default_language('locale')
			);
			if(function_exists('get_blog_option')) {
				$errorlog['site_lang_WPLANG'] = get_blog_option($blog, 'WPLANG');
			}
    	$selector .= '<p style="font-style:italic; margin-right:75px;">Error: no project option sets include current WordPress locale ('. $lang .') as a source language. <a href="mailto:contact@andrewthomashill.co.uk?subject=SDL%20LOG%20SiteLangagueIssue&body='. urlencode(json_encode($errorlog)) .'">Send error log via email</a></p>';
    }
    return $selector;
	}
}

add_action( 'wp_ajax_sdl_get_options', 'sdl_get_options' );
function sdl_get_options() {
	global $wpdb;
	echo JSON_encode( get_site_option('sdl_settings_projectoptions_pairs') );
	wp_die();
}

?>
