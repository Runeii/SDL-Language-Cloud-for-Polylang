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
		//sdl_poll_projects();
		  global $post_type;
		  $language_set = get_site_option('sdl_settings_projectoptions_pairs')[$this->args['ProjectOptionsID']];
		  if(pll_is_translated_post_type($post_type)) {
		  	$string = '';
		  	$bulk_actions['sdl_translate_full'] = __('Create translation project', 'managedtranslation');
		  	foreach($language_set['Target'] as $language) {
		  		$bulk_actions['sdl_translate_' . $language] = __('Quick translate into ' . strtoupper($language), 'managedtranslation');
		  		$string .= $language . '_';
		  	}
		  }
		  return $bulk_actions;
	}

	public function handle_dropdowns( $redirect_to, $doaction, $post_ids ) {
		var_dump($post_ids);
		$string = strpos($doaction, 'sdl_translate_');
		if ( $string !== 0) {
			return $redirect_to;
		}
		$suffix = preg_replace('/^sdl_translate_/', '', $doaction);
		if($suffix === 'full') {

		} else {
			$this->args['Targets'] = $suffix;
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
	public function create_project_form($id) {
		if(sizeof($id) > 1) {
			$this->args['Name'] = 'Bulk translation – ' . date('H:i jS M');
		} else {
			$this->args['Name'] = get_the_title($id[0]);
		}
		$this->args['Files'] = $this->posts_to_file($id);
		var_dump(JSON_encode($args));
		/*$response = $api->project_create($args);
		if(is_array($response)) {
			$inprogress = get_option('sdl_translations_inprogress');
			if($inprogress == null) {
				$inprogress = array();
			}
			$inprogress[] = $response['ProjectId'];
			update_option('sdl_translations_inprogress', $inprogress);
		}
		return $response;*/
	}
	public function create_project($id) {
		if(sizeof($id) > 1) {
			$this->args['Name'] = 'Bulk translation – ' . date('H:i jS M');
		} else {
			$this->args['Name'] = get_the_title($id[0]);
		}
		$this->args['Files'] = $this->posts_to_file($id);
		unset($this->args['Targets']);
		var_dump($this->args);
		/*$response = $api->project_create($args);
		if(is_array($response)) {
			$inprogress = get_option('sdl_translations_inprogress');
			if($inprogress == null) {
				$inprogress = array();
			}
			$inprogress[] = $response['ProjectId'];
			update_option('sdl_translations_inprogress', $inprogress);
			return array('key' => 'translation_success', 'value' => count( $post_ids ));
		} else {
			return array('key' => 'translation_error', 'value' => 'API error ' . $response);
		}*/
	}
	public function posts_to_file($id){
		$convertor = new Polylang_SDL_Create_XLIFF;
		$api = new Polylang_SDL_API;
		if(sizeof($id) > 1) {
			$description = '';
			$file_upload = array();
			foreach($id as $post) {
				$description .= get_the_title($post) . ', ';
				$xliff = $convertor->output($post, $this->src_lang, $target);
				$file_upload[] = array(
									'fileId' => $api->file_upload($xliff, $this->args['ProjectOptionsID'])[0]['FileId'],
									'targets' => array($this->args['Targets'])
								);
			}
		} else {
			$xliff = $convertor->output($id[0], $this->src_lang, $this->args['Targets']);
			$file_upload = array(
				array(
				'fileId' => $api->file_upload($xliff, $this->args['ProjectOptionsID'])[0]['FileId'],
				'targets' => array($this->args['Targets'])
				)
			);
		}
		return $file_upload;
	}
}

?>
