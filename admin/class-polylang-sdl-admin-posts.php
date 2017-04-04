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
		$this->api = new Polylang_SDL_API(true);
		if(is_admin() && $this->api->test_loggedIn()) {
			add_filter( 'bulk_actions-edit-post', array($this, 'register_dropdowns') );
			add_filter( 'handle_bulk_actions-edit-post', array($this, 'handle_dropdowns'), 10, 3 );
			add_action( 'admin_notices', array($this, 'handle_dropdowns_notice') );
			add_filter( 'manage_posts_columns', array($this, 'sdl_posts_translation_column') );
			add_filter( 'manage_posts_custom_column', array($this, 'sdl_posts_translation_column_row'), 10, 2 );
		}
		$this->args = array(
			'ProjectOptionsID' => get_option('sdl_settings_projectoption'),
			'SrcLang' => strtolower(get_option('sdl_settings_projectoptions_sourcelang')),
		);
	}

	public function register_dropdowns($bulk_actions){
		  global $post_type;
		  $language_set = get_site_option('sdl_settings_projectoptions_pairs')[$this->args['ProjectOptionsID']];
		  if(pll_is_translated_post_type($post_type)) {
		  	$string = '';
		  	$bulk_actions['sdl_translate_full'] = __('Create translation project', 'managedtranslation');
		  	$polylang_languages = pll_languages_list();
		  	foreach($language_set['Target'] as $language) {
		  		$short_name = explode('-', $language)[0];
		  		if(in_array($short_name, $polylang_languages)) {
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
			$this->api->translation_create($post_ids, $this->args);
		}
	}

	public function handle_dropdowns_notice() {
	  if ( ! empty( $_REQUEST['translation_success'] ) ) {
	    $emailed_count = intval( $_REQUEST['translation_success'] );
	    printf( '<div id="message" class="updated fade">' .
	      _n( 'Successfully sent %s post to the Managed Translation service for translation.',
	        'Successfully sent %s posts to the Managed Translation service for translation.',
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
	public function sdl_posts_translation_column( $columns ) {
		$columns['sdl_translation'] = 'SDL Translation';
	    return $columns;
	}
	public function sdl_posts_translation_column_row($column, $post_id) {
		switch ( $column ) {
			case 'sdl_translation':
				$project = get_post_meta($post_id, '_sdl_source_projectoptions', true);
				$inprogress = get_option('sdl_translations_inprogress');
				if($inprogress != null && $inprogress != '' && $inprogress != false) {
					if(in_array($project, $inprogress)) {
							echo '<div class="button button-secondary">In progress</div>';			
					} elseif($project != false && $project != '' && $project != null) {
						$status = get_post_meta($post_id, '_sdl_flag_outofdate', true);
						$local = new Polylang_SDL_Local;
						$source_id = $local->get_parent_translation($post_id);
						if(($status == true || $status == 1)  && $post_id == $source_id){
							echo '<button class="button button-primary">Update all translations</button>';	
						} elseif($status == true || $status == 1) {		
							echo '<button class="button button-secondary">Update translation</button>';	
						} else {	
							echo '<button class="button delete" disabled>Up to date</button>';	
						}

					}
				}
			break;
		}
	}
}

?>
