<?php
class Polylang_SDL_Polylang_Integration {
	private $post_model;

	public function __construct(){
		add_action('post_updated', array($this, 'flag_existing_translations'), 10, 3);
		add_action('current_screen', array($this, 'post_screen_functions'));
		$this->post_model = new Polylang_SDL_Model;
	}
	public function flag_existing_translations($post_ID, $post_after, $post_before){
		$source_id = $this->post_model->get_source_id($post_ID);
		if($source_id == $post_ID) {
			$this->post_model->update_details($post_ID, 'updated', get_the_modified_date('U', $post));
		}
		return true;
	}
	public function post_screen_functions(){
		global $my_admin_page;
		$screen = get_current_screen();
		if ( $screen->id == 'post' && pll_is_translated_post_type( $screen->post_type ) ){
			add_action( 'add_meta_boxes', array($this, 'update_existing_translations'), 10, 2 );
		}
	}
	public function update_existing_translations($post_type, $post ){
		$source_id = $this->post_model->get_source_id($post->ID);
		$out_of_date = $this->post_model->get_old($post->ID);
		$lang = sdl_get_post_language($post->ID);
		if($source_id != $post->ID && is_array($out_of_date) && array_key_exists($lang, $out_of_date) && $out_of_date[$lang]['id'] == $post->ID) {
			add_meta_box('sdl_update_post', 'Update translation', array($this, 'update_existing_translations_box'), $post_type, 'side', 'high');
		}
	}
	public function update_existing_translations_box(){
		$post_id = get_the_ID();
		$map = $this->post_model->get_source_map($post_id);
		$details = $this->post_model->get_details($post_id, $map);
		print __('This translation is now out of date.', 'managedtranslation');
		echo '<br /><br />';
		$args = array(
			'action' => 'sdl_update_single',
			'src_id' => $map['parent']['id'],
			'src_lang' => $map['parent']['locale'],
			'target_lang' => $details['locale'],
			'project_options' => $details['produced_by'],
			'redirect_to' => admin_url('edit.php')
			);
		echo '<a class="button button-primary" href="admin.php?page=managedtranslation&override=1&'. http_build_query($args) .'">Update translation</a>';
	}
	public function sdl_manage_languages($lang, $mode = 'add'){
		//Polylang doesn't offer a hook or function for directly interacting with languages, so this is a bit of a hack job
		include( PLL_SETTINGS_INC.'/languages.php' );
		if($mode == 'add') {
			$polylang_set = $languages[$lang];
			$args = array(
				'name' => $polylang_set[2],
				'locale' => $polylang_set[1],
				'slug' => $polylang_set[0],
				'flag' => $polylang_set[4],
				'ltr' => $polylang_set[3]
				);
			$polylang_options = get_site_option('polylang', true);
			$polylang_settings = new PLL_Admin_Model($polylang_options);
			$polylang_settings->add_language( $args );

			if ( ! isset( $polylang_options['default_lang'] ) ) {
				// If this is the first language created, set it as default language
				$polylang_options['default_lang'] = $args['slug'];
				update_site_option( 'polylang', $polylang_options );

				// And assign default language to default category
				$polylang_settings->term->set_language( (int) get_option( 'default_category' ), (int) $r['term_id'] );
			} elseif ( empty( $args['no_default_cat'] ) ) {
				$polylang_settings->create_default_category( $args['slug'] );
			}
		}
	}
}
?>