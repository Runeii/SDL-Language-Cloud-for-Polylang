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

	public function __construct( $polylang_sdl, $version ) {

		$this->polylang_sdl = $polylang_sdl;
		$this->version = $version;
		$this->register_interface();
	}

	public function enqueue_styles() {

		wp_enqueue_style( $this->polylang_sdl, plugin_dir_url( __FILE__ ) . 'css/polylang-sdl-admin.css', array(), $this->version, 'all' );

	}

	public function enqueue_scripts() {

		wp_enqueue_script( $this->polylang_sdl, plugin_dir_url( __FILE__ ) . 'js/polylang-sdl-admin.js', array( 'jquery' ), $this->version, false );

	}

	public function register_interface(){
		add_action('admin_menu', 'sdl_register_menu');
		add_action('network_admin_menu', 'sdl_register_menu');
		function sdl_register_menu(){
			add_menu_page('SDL Language Cloud settings', __( 'Language Cloud','languagecloud' ), 'edit_others_posts', 'languagecloud', 'sdl_create_page', 'dashicons-cloud');
		}

		function sdl_create_page(){
    		include('class-polylang-sdl-panel.php');
		}

		add_action( 'admin_init', 'sdl_settings_init' );
		function sdl_settings_init() {
			register_setting( 'sdl_settings_account_page', 'sdl_settings' );
			add_settings_section(
				'sdl_settings_account_section', 
				__( 'Language Cloud account details', 'languagecloud' ), 
				'sdl_settings_account_section_callback', 
				'sdl_settings_account_page'
			);
			add_settings_field( 
				'sdl_settings_account_username', 
				__( 'Username', 'languagecloud' ), 
				'sdl_settings_account_username_render', 
				'sdl_settings_account_page', 
				'sdl_settings_account_section' 
			);
			add_settings_field( 
				'sdl_settings_account_password', 
				__( 'Password', 'languagecloud' ), 
				'sdl_settings_account_password_render', 
				'sdl_settings_account_page', 
				'sdl_settings_account_section'
			);

			register_setting( 'sdl_settings_overview_page', 'sdl_settings' );
			add_settings_section(
				'sdl_settings_overview_section', 
				__( 'Your section description', 'languagecloud' ), 
				'sdl_settings_overview_section_callback', 
				'sdl_settings_overview_page'
			);
			add_settings_field( 
				'sdl_settings_overview_', 
				__( 'Settings field description', 'languagecloud' ), 
				'sdl_settings_overview__render', 
				'sdl_settings_overview_page', 
				'sdl_settings_overview_section' 
			);

		}
		function sdl_settings_account_username_render(  ) { 
			$options = get_option( 'sdl_settings' );
			?>
			<input type='text' name='sdl_settings[sdl_settings_account_username]' value='<?php echo $options['sdl_settings_account_username']; ?>'>
			<?php
		}
		function sdl_settings_account_password_render(  ) { 
			$options = get_option( 'sdl_settings' );
			?>
			<input type='password' name='sdl_settings[sdl_settings_account_password]' value='<?php echo $options['sdl_settings_account_password']; ?>'>
			<?php
		}
		function sdl_settings_account_section_callback(  ) { 
			echo __( 'User account login details for the SDL Language Cloud', 'languagecloud' );
		}

		function sdl_settings_text_overview__render(  ) { 

		}
		function sdl_settings_overview_section_callback(  ) { 
			echo __( 'This section description', 'languagecloud' );
		}
	}
}

?>
