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

	public function __construct() {
		if(!is_admin()) {
			die();
		}
		$this->register_dropdowns();
	}

	public function register_dropdowns(){

		add_filter( 'bulk_actions-edit-post', 'create_translation_bulk_action' );
		function create_translation_bulk_action($bulk_actions) {
		  global $post_type;
		  $ProjectOptionsID = get_option('sdl_projectoptions');
		  $language_set = get_site_option('sdl_settings_projectoptions_pairs')[$ProjectOptionsID];
		  sdl_poll_projects();
		  if(pll_is_translated_post_type($post_type)) {
		  	$string = '';
		  	foreach($language_set['Target'] as $language) {
		  		$bulk_actions['sdl_translate_' . $language] = __('Translate into ' . $language, 'languagecloud');
		  		$string .= $language . '_';
		  	}
		  }
		  return $bulk_actions;
		}

		add_filter( 'handle_bulk_actions-edit-post', 'create_translation_bulk_handler', 10, 3 );
		function create_translation_bulk_handler( $redirect_to, $doaction, $post_ids ) {
			$string = strpos($doaction, 'sdl_translate_');
			if ( $string !== 0) {
				return $redirect_to;
			}
			$suffix = preg_replace('/^sdl_translate_/', '', $doaction);
			$output = create_project($post_ids, $suffix);
			$redirect_to = add_query_arg( 'translated', count( $post_ids ), $redirect_to );
			return $redirect_to;
		}

		add_action( 'admin_notices', 'create_translation_bulk_notice' );
		function create_translation_bulk_notice() {
		  if ( ! empty( $_REQUEST['translated'] ) ) {
		    $emailed_count = intval( $_REQUEST['translated'] );
		    printf( '<div id="message" class="updated fade">' .
		      _n( 'Successfully sent %s post to the Language Cloud for translation.',
		        'Successfully sent %s posts to the Language Cloud for translation.',
		        $emailed_count,
		        'languagecloud'
		      ) . '</div>', $emailed_count );
		  }
		}
	}
}

function create_project($id, $target) {
	$src_lang = get_option('sdl_projectoptions_sourcelang');
	$ProjectOptionsID = get_option('sdl_projectoptions');
	$convertor = new Polylang_SDL_Create_XLIFF;
	$api = new Polylang_SDL_API;
	if(sizeof($id) > 1) {
		$name = 'Bulk translation – ' . date('H:i jS M');
		$description = '';
		$file_upload = array();
		foreach($id as $post) {
			$description .= get_the_title($post) . ', ';
			$xliff = $convertor->output($post, $src_lang, $target);
			$file_upload[] = array(
								'fileId' => $api->file_upload($xliff, $ProjectOptionsID)[0]['FileId'],
								'targets' => array($target)
							);
		}
	} else {
		$name = get_the_title($id[0]);
		$xliff = $convertor->output($id[0], $src_lang, $target);
		$file_upload = array(
			array(
			'fileId' => $api->file_upload($xliff, $ProjectOptionsID)[0]['FileId'],
			'targets' => array($target)
			)
		);
	}
	$args = array(
		'Name' => $name,
//		'Description' => $description,
		'ProjectOptionsID' => $ProjectOptionsID,
		'SrcLang' => $src_lang,
		'Files' => $file_upload
	);
	$response = $api->project_create($args);
	if(is_array($response)) {
		$inprogress = get_option('sdl_translations_inprogress');
		$inprogress = array();
		if($inprogress == null) {
			$inprogress = array();
		}
		$inprogress[] = $response['ProjectId'];
		update_option('sdl_translations_inprogress', $inprogress);
	}
	return $response;
}
function sdl_poll_projects(){
	$inprogress = get_option('sdl_translations_inprogress');
	$api = new Polylang_SDL_API;
	foreach($inprogress as $project) {
		$status = $api->project_getStatusCode($project);
		if($status == 3 || $status == 4) {
			$file = $api->translation_download($project);
			if($file) {
				$unpack = new Polylang_SDL_Unpack_XLIFF;
				$posts = $unpack->convert($project);
				if(is_array($posts)) {
					$convertor = new Polylang_SDL_Local;
					foreach($posts as $post) {
						//An update could have happened while testing, so let's refresh the array
						$saved = $convertor->save_post_translation($post);	
						$latest = get_option('sdl_translations_inprogress');
						unset($latest[$project]);
						update_option('sdl_translations_inprogress', $latest);	
					}
				}
			}
		}
	}
}
?>
