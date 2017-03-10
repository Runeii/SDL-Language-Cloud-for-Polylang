<?php

/**
 * The admin post-page-specific functionality of the plugin.
 *
 * @link       http://languagecloud.sdl.com
 * @since      1.0.0
 *
 * @package    Polylang_SDL
 * @subpackage Polylang_SDL/admin
 */
class Polylang_SDL_Admin_Posts extends Polylang_SDL_Admin {

	private $polylang_sdl;
	private $version;
	private $option_name;

	public function __construct( $polylang_sdl, $version ) {
		if(!is_admin()) {
			die();
		}
		$this->polylang_sdl = $polylang_sdl;
		$this->version = $version;
		$this->register_interface();
	}

	public function register_interface(){
		
	}
}

?>
