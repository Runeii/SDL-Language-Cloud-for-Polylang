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

	public function __construct( $polylang_sdl, $version ) {
		if(is_admin()) {
			$this->polylang_sdl = $polylang_sdl;
			$this->version = $version;
			$this->register_interface();

			add_action( 'current_screen', 'check_current_screen' ); 
			function check_current_screen(){
				if ( is_admin() ) {
					$screen = get_current_screen();
					if($screen->base == 'edit' && pll_is_translated_post_type($screen->post_type)) {
						new Polylang_SDL_Admin_Posts;
					}
				}
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
		add_action( 'admin_init', array($this, 'sdl_settings_init') );
	}
	
	public function sdl_register_menu(){
		add_menu_page('SDL Managed Translation settings', __( 'Managed Translation','managedtranslation' ), 'manage_options', 'managedtranslation', array($this, 'sdl_create_page'), 'dashicons-cloud');
	}

	public function sdl_create_page(){
    	include('class-polylang-sdl-panel.php');
   		new Polylang_SDL_Admin_Panel;
	}

	public function sdl_settings_init() {

		add_filter('network_admin_menu', 'sdl_settings_network_admin_menu');
		add_action('network_admin_edit_sdl_settings_update_network_options',  'sdl_settings_update_network_options');
		function sdl_settings_network_admin_menu() {
			register_setting( 'sdl_settings_account_page', 'sdl_settings_account_username' );
			register_setting( 'sdl_settings_account_page', 'sdl_settings_account_password' );
			add_settings_section(
				'sdl_settings_account_section', 
				__( 'Managed Translation account details', 'managedtranslation' ), 
				false, 
				'sdl_settings_account_page'
			);
			add_settings_field( 
				'sdl_settings_account_username', 
				__( 'Username', 'managedtranslation' ), 
				'sdl_settings_account_username_render', 
				'sdl_settings_account_page', 
				'sdl_settings_account_section' 
			);
			add_settings_field( 
				'sdl_settings_account_password', 
				__( 'Password', 'managedtranslation' ), 
				'sdl_settings_account_password_render', 
				'sdl_settings_account_page', 
				'sdl_settings_account_section'
			);
		}
		function sdl_settings_account_username_render(  ) {
			?>
			<input type='text' name='sdl_settings_account_username' value='<?php echo get_site_option('sdl_settings_account_username'); ?>'>
			<?php
		}
		function sdl_settings_account_password_render(  ) {
			?>
			<input type='password' name='sdl_settings_account_password' />
			<?php
		}
		function sdl_settings_account_section_callback(  ) { 
			echo __( 'User account login details for the SDL Managed Translation', 'managedtranslation' );
		} 
		function sdl_settings_update_network_options() {
			check_admin_referer('sdl_settings_account_page-options');
			global $new_whitelist_options;
			$options = $new_whitelist_options['sdl_settings_account_page'];

			foreach ($options as $option) {
				if (isset($_POST[$option]) && $option === 'sdl_settings_account_password') {
				    $output = openssl_encrypt($_POST[$option], 'AES-256-CBC', hash('sha256', wp_salt()), 0, substr(hash('sha256', 'managedtranslation'), 0, 16));
				    $output = base64_encode($output);
					update_site_option($option, $output);
				} else if (isset($_POST[$option])) { 
					update_site_option($option, $_POST[$option]);
				} else {
					delete_site_option($option);
				}
			}

			wp_redirect(add_query_arg(array('page' => 'managedtranslation&tab=account',
			'updated' => 'true'), network_admin_url('admin.php')));
			exit;
		}
		function sdl_settings_text_sites__render(  ) { 

		}
		function sdl_settings_sites_section_callback(  ) { 
			echo __( 'This section description', 'managedtranslation' );
		}
	}
}

?>
