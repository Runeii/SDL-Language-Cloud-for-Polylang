<?php
class Polylang_SDL_Polylang_Integration {
	public function __construct(){
		add_action('post_updated', array($this, 'flag_existing_translations'), 10, 3);
		add_action('current_screen', array($this, 'post_screen_functions'));
	}
	public function flag_existing_translations($post_ID, $post_after, $post_before){
		$local = new Polylang_SDL_Local;
		$flag = get_post_meta($post_ID, '_sdl_flag_outofdate', true);
		if($flag == false ||  $flag == 0 || $flag == null || $flag == '') {
			foreach($local->get_post_translations($post_ID) as $trans_ID) {
				if($post_before->post_content != $post_after->post_content) {
					update_post_meta($trans_ID, '_sdl_flag_outofdate', true);
				}
			}
		}
	}
	public function post_screen_functions(){
		global $my_admin_page;
		$screen = get_current_screen();
		if(isset($_POST['sdl_button_update_translation'])) {
			$id = $_POST['sdl_id'];
			$src_id = get_post_meta($id, '_sdl_source_id', true);
			$options = get_post_meta($src_id, '_sdl_source_projectoptions', true);
			$inprogress_details = get_option('sdl_translations_record_details');
			var_dump($options);
			var_dump($inprogress_details);
			$args = array(
					'ProjectOptionsID' => $inprogress_details[$options]['project_options'],
					'SrcLang' => $inprogress_details[$options]['SrcLang'],
					'Targets' => array($_POST['target'])
				);
			$api = new Polylang_SDL_API;
			$response = $api->translation_create(array($src_id), $args);
			var_dump($args);
			var_dump($response);
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
		$local = new Polylang_SDL_Local;
		if (get_post_meta($post->ID, '_sdl_flag_outofdate', true) == 1 && $local->get_parent_translation($post->ID) != $post->ID) {
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