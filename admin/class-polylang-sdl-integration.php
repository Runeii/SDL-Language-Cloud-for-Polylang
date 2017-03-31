<?php
class Polylang_SDL_Polylang_Integration {
	public function __construct(){
		add_action('post_updated', array($this, 'flag_existing_translations'), 10, 3);
		add_action('current_screen', array($this, 'post_screen_functions'));
	}
	public function flag_existing_translations($post_ID, $post_after, $post_before){
		$local = new Polylang_SDL_Local;
		$flag = get_post_meta($post_ID, 'sdl_flag_outofdate', true);
		if($flag == false ||  $flag == 0 || $flag == null || $flag == '') {
			foreach($local->get_post_translations($post_ID) as $trans_ID) {
				if($trans_ID !== $post_ID) {
					if($post_before->post_content != $post_after->post_content) {
						update_post_meta($trans_ID, 'sdl_flag_outofdate', true);
					}
				}
			}
		}
		update_post_meta($post_ID, 'sdl_flag_outofdate', false);
	}
	public function post_screen_functions(){
		global $my_admin_page;
		$screen = get_current_screen();
		if(isset($_POST['sdl_button_update_translation'])) {
			$id = $_POST['sdl_id'];
			$options = get_post_meta($id, 'sdl_source_projectoptions', true);
			$inprogress_details = get_option('sdl_translations_record_details');
			$args = array(
					'ProjectOptionsID' => $inprogress_details[$options]['project_options'],
					'SrcLang' => $inprogress_details[$options]['SrcLang'],
					'Targets' => array($_POST['target'])
				);
			$api = new Polylang_SDL_API;
			$src_id = pll_get_post($id, explode('-', $args['SrcLang'])[0]);
			$response = $api->translation_create(array($src_id), $args);
			if(is_array($response)) {
				/// TODO: Return success message
			} else {
				// TODO: Return error message
			}
		}
		if ( $screen->id == 'post' && pll_is_translated_post_type( $screen->post_type ) ){
			add_action( 'add_meta_boxes', array($this, 'update_existing_translations'), 10, 2 );
		}
	}
	public function update_existing_translations($post_type, $post ){
		if (get_post_meta($post->ID, 'sdl_flag_outofdate', true) === true || get_post_meta($post->ID, 'sdl_flag_outofdate', true) == 1 ) {
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

}
?>