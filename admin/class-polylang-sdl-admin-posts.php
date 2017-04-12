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
		$this->post_model = new Polylang_SDL_Model;
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
			$response = $this->api->translation_create($post_ids, $this->args);
			wp_redirect( add_query_arg($response, admin_url('edit.php')) );
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
	  	} else if ( ! empty( $_REQUEST['update_success'] ) ) {
		    $updated_count = intval( $_REQUEST['update_success'] );
		    printf( '<div id="message" class="updated fade">' .
		      _n( 'Successfully sent %s translations to the Managed Translation service for update.',
		        'Successfully sent %s translations to the Managed Translation service for update.',
		        $updated_count,
		        'managedtranslation'
		      ) . '</div>', $updated_count );
	  	} else if ( ! empty( $_REQUEST['update_error'] ) ) {
		    print( '<div id="message" class="updated fade">' . 
		    	__( 'Translation update failed: ' . $_REQUEST['update_error'], 'managedtranslation') . 
		    	'</div>' );
	  	} else if ( ! empty( $_REQUEST['update_success_total'] ) ) {
		    $updated_count = intval( $_REQUEST['update_success'] );
		    printf( '<div id="message" class="updated fade">' .
		      _n( 'Successfully sent %s translations to the Managed Translation service for update.',
		        'Successfully sent %s translations to the Managed Translation service for update.',
		        $updated_count,
		        'managedtranslation'
		      ) . '</div>', $updated_count );
		} else if ( ! empty( $_REQUEST['update_error_total'] ) ) {
		    print( '<div id="message" class="updated fade">' . 
		    	__( 'Failed to send translations for update: ' . $_REQUEST['update_error_total'], 'managedtranslation') . 
		    	'</div>' );
		} else if ( ! empty( $_REQUEST['update_success_partial'] ) ) {
		    $updated_count = intval($_REQUEST['update_success_partial'][0]);
		    $error_count = intval($_REQUEST['update_success_partial'][1]);
		    printf( '<div id="message" class="updated fade">' .
		      	_n( 'Successfully sent %s translations to the Managed Translation service for update.',
			        'Successfully sent %s translations to the Managed Translation service for update.',
			        $updated_count,
			        'managedtranslation'
		      	) .
				_n( '%s translations failed with errors.',
			        '%s translations failed with errors.',
			        $error_count,
			        'managedtranslation'
		      	)
		      . '</div>');
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
		$columns['sdl_translation'] = 'Managed Translation';
	    return $columns;
	}
	public function sdl_posts_translation_column_row($column, $post_id) {
		switch ( $column ) {
			case 'sdl_translation':
				$map = $this->post_model->get_source_map($post_id);
				$details = $this->post_model->get_details($post_id, $map);
				$out_of_date = $this->post_model->get_old($post_id);
				if(count($map['children']) > 0 && array_filter($map['children'])) {
					if(	is_array($map['in_progress']) && count($map['in_progress']) > 0 &&
							(array_key_exists($details['lang'], $map['in_progress']) || $post_id == $map['parent']['id'])
						) {
						echo '<button class="button button-secondary" disabled >Translation in progress</button>';	
					} elseif(is_array($out_of_date) && array_key_exists($details['lang'], $out_of_date)) {
						$args = array(
							'action' => 'sdl_update_single',
							'src_id' => $map['parent']['id'],
							'src_lang' => $map['parent']['locale'],
							'target_lang' => $details['locale'],
							'project_options' => $details['produced_by'],
							'redirect_to' => admin_url('edit.php')
							);
						echo '<a class="button button-secondary" href="admin.php?page=managedtranslation&override=1&'. http_build_query($args) .'">Update translation</a>';
					} elseif(is_array($out_of_date) && $post_id == $map['parent']['id'] && count($out_of_date) > 0) {
						$args = array(
							'action' => 'sdl_update_all',
							'src_id' => $map['parent']['id'],
							'src_lang' => $map['parent']['locale'],
							'redirect_to' => admin_url('edit.php')
							);
						echo '<a class="button button-primary" href="admin.php?page=managedtranslation&override=1&'. http_build_query($args) .'">Update all Translations</a>';
					}  elseif($post_id != $map['parent']['id']) {
						echo '<button class="button delete" disabled>Up to date</button>';		
					} elseif($post_id == $map['parent']['id']) {
						echo '<button class="button delete" disabled>Translations up to date</button>';		
					}	
				}
			break;
		}
	}
}

?>
