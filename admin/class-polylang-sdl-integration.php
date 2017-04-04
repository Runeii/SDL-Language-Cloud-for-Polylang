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
		if(isset($_POST['sdl_button_update_translation'])) {
			$id = $_POST['sdl_id'];
			$source_map = $this->post_model->get_source_map($id);
			$self_map = $this->post_model->get_details($id);
			$args = array(
					'ProjectOptionsID' => $self_map['produced_by'],
					'SrcLang' => $source_map['parent']['locale'],
					'Targets' => array($_POST['target'])
				);

			$api = new Polylang_SDL_API;
			$response = $api->translation_create(array($source_map['parent']['id']), $args);
			if(is_array($response)) {
				add_action( 'admin_notices', array($this, 'sdl_notice_update_success'), 10, 2 );
				add_settings_error('managedtranslation', 'update', 'Successfully requested translations update via SDL Managed Translation', 'updated');	
			} else {
				// TODO: Return error message
				add_action( 'admin_notices', array($this, 'sdl_notice_update_failed'), 10, 2 );
				add_settings_error('managedtranslation', 'update', 'Failed to send posts for update via SDL Managed Translation', 'error');
			}
		}
		if ( $screen->id == 'post' && pll_is_translated_post_type( $screen->post_type ) ){
			add_action( 'add_meta_boxes', array($this, 'update_existing_translations'), 10, 2 );
		}
	}
	public function update_existing_translations($post_type, $post ){
		$source_id = $this->post_model->get_source_id($post->ID);
		$out_of_date = $this->post_model->get_old($post->ID);
		$lang = pll_get_post_language($post->ID);
		if($source_id != $post->ID && is_array($out_of_date) && in_array($lang, $out_of_date)) {
			add_meta_box('sdl_update_post', 'Update translation', array($this, 'update_existing_translations_box'), $post_type, 'side', 'high');
		}
	}
	public function update_existing_translations_box(){
		$post_id = get_the_ID();
		print __('This translation is now out of date.', 'managedtranslation');
		echo '<br /><br />';
		echo '<input type="hidden" name="sdl_id" value="'. $post_id .'" />
				<input type="hidden" name="target" value="'. pll_get_post_language($post_id) .'" />
				<button class="button button-primary" name="sdl_button_update_translation">Update translation now</button>';
	}
	public function supported_project_options(){
		
	}
	public function sdl_notice_update_success(){
		echo '<div class="notice notice-success is-dismissible">
			        <p>'. __( 'Successfully requested translation update via SDL Managed Translation', 'managedtranslation' ) .'</p>
			    </div>';
	}
	public function sdl_notice_update_failed(){
		echo '<div class="notice notice-error is-dismissible">
	        <p>'. __( 'Failed to send translations for update via SDL Managed Translation', 'managedtranslation' ) .'</p>
	    </div>';
	}

}
?>