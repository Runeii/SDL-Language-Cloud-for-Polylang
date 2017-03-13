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
		  if(pll_is_translated_post_type($post_type)) {
		  	$bulk_actions['sdl_translate'] = __('Translate ' . $post_type, 'languagecloud');
		  }
		  return $bulk_actions;
		}

		add_filter( 'handle_bulk_actions-edit-post', 'create_translation_bulk_handler', 10, 3 );
		function create_translation_bulk_handler( $redirect_to, $doaction, $post_ids ) {
		  if ( $doaction !== 'sdl_translate' ) {
		    return $redirect_to;
		  }
		  create_project($post_ids);
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

function create_project($id) {
	if(is_array($id)) {
		$name = 'Bulk translation – ' . date('H:i jS M');
		$description = '';
		foreach($id as $post) {
			$description .= get_the_title($id) . ', ';
		}
	} else {
		$name = get_the_title($id);
	}
	$args = array(
		'Name' => $name,
		'Description' => $description,
		'ProjectOptionsID' => get_option('sdl_projectoptions'),
		'SrcLang' => get_option('sdl_projectoptions_sourcelang'),
		'Files' => 'blank'
	);

}

?>
