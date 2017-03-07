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

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Polylang_SDL
 * @subpackage Polylang_SDL/admin
 * @author     SDL
 */
class Polylang_SDL_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $polylang_sdl    The ID of this plugin.
	 */
	private $polylang_sdl;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $polylang_sdl       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $polylang_sdl, $version ) {

		$this->polylang_sdl = $polylang_sdl;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Polylang_SDL_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Polylang_SDL_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->polylang_sdl, plugin_dir_url( __FILE__ ) . 'css/polylang-sdl-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Polylang_SDL_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Polylang_SDL_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->polylang_sdl, plugin_dir_url( __FILE__ ) . 'js/polylang-sdl-admin.js', array( 'jquery' ), $this->version, false );

	}

}
