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
		add_menu_page('SDL Managed Translation settings', __( 'SDL Managed Translation','managedtranslation' ), 'edit_pages', 'managedtranslation', array($this, 'sdl_create_page'), 'dashicons-cloud');
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
        }
    }
    $selector .= '</select>';
    if($count === 0) {
    	$selector .= '<p style="font-style:italic; margin-right:75px;">Error: no project option sets include current WordPress locale ('. $lang .') as a source language.</p>';
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
