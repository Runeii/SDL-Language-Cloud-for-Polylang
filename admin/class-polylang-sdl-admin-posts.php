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
class Polylang_SDL_Admin_Posts {

	private $polylang_sdl;
	private $version;
	private $option_name;
	private $args;
	private $api;

	public function __construct() {
		if(is_admin()) {
			add_filter( 'bulk_actions-edit-post', array($this, 'register_dropdowns') );
			add_filter( 'handle_bulk_actions-edit-post', array($this, 'handle_dropdowns'), 10, 3 );
			add_action( 'admin_notices', array($this, 'handle_dropdowns_notice') );
		}
		$this->args = array(
			'ProjectOptionsID' => get_option('sdl_projectoptions'),
			'SrcLang' => strtolower(get_option('sdl_projectoptions_sourcelang')),
		);
	}

	public function register_dropdowns($bulk_actions){
		  global $post_type;
		  $language_set = get_site_option('sdl_settings_projectoptions_pairs')[$this->args['ProjectOptionsID']];
		  if(pll_is_translated_post_type($post_type)) {
		  	$string = '';
		  	$bulk_actions['sdl_translate_full'] = __('Create translation project', 'managedtranslation');
		  	$Polylang_languages = pll_languages_list();
		  	foreach($language_set['Target'] as $language) {
		  		$short_name = explode('-', $language)[0];
		  		if(in_array($short_name, $Polylang_languages)) {
			  		$bulk_actions['sdl_translate_' . $language] = __('Quick translate into ' . strtoupper($short_name), 'managedtranslation');
			  		$string .= $language . '_';	
		  		}
		  	}
		  }
		  return $bulk_actions;
	}

	public function handle_dropdowns( $redirect_to, $doaction, $post_ids ) {
		$string = strpos($doaction, 'sdl_translate_');
		if ( $string !== 0) {
			return $redirect_to;
		}
		$suffix = preg_replace('/^sdl_translate_/', '', $doaction);
		if($suffix === 'full') {
			$response = $this->create_project_form($post_ids);
		} else {
			$this->args['Targets'] = array($suffix);
			$response = $this->create_project($post_ids);
		}
		$redirect_to = add_query_arg( $reponse['key'], $response['value'], $redirect_to );
		return $redirect_to;
	}

	public function handle_dropdowns_notice() {
	  if ( ! empty( $_REQUEST['translation_success'] ) ) {
	    $emailed_count = intval( $_REQUEST['translation_success'] );
	    printf( '<div id="message" class="updated fade">' .
	      _n( 'Successfully sent %s post to the Managed Translation for translation.',
	        'Successfully sent %s posts to the Managed Translation for translation.',
	        $emailed_count,
	        'managedtranslation'
	      ) . '</div>', $emailed_count );
	  } else if ( ! empty( $_REQUEST['translation_error'] ) ) {
	    print( '<div id="message" class="updated fade">' . 
	    	__( 'Translation failed: ' . $_REQUEST['translation_error'], 'managedtranslation') . 
	    	'</div>' );
	  }
	}
	public function create_project_form($post_ids) {
		$sanitised_ids = implode(',', $post_ids);
		wp_redirect(
			add_query_arg(
				array(
					'page' => 'managedtranslation&tab=create_project',
					'override' => '1',
					'posts' => $sanitised_ids), 
				admin_url('admin.php')
			)
		);
		exit;
	}
	public function create_project($id) {
		$this->api = new Polylang_SDL_API;

		if(sizeof($id) > 1) {
			$this->args['Name'] = 'Bulk translation – ' . date('H:i jS M');
		} else {
			$this->args['Name'] = get_the_title($id[0]);
		}
		$convertor = new Polylang_SDL_Create_XLIFF;
		$this->args['Files'] = $convertor->package_post($id, $this->args);
		unset($this->args['Targets']);

		$response = $this->api->project_create($this->args);
		if(is_array($response)) {
			$inprogress = get_option('sdl_translations_inprogress');
			if($inprogress == null) {
				$inprogress = array();
			}
			$inprogress[] = $response['ProjectId'];
			update_option('sdl_translations_inprogress', $inprogress);
			return array('key' => 'translation_success', 'value' => count( $id ));
		} else {
			return array('key' => 'translation_error', 'value' => 'API error ' . $response);
		}
	}
}

?>
