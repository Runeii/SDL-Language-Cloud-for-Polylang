<?php
class Polylang_SDL_Model {

	private $verbose = false;
	private $map;
	private $parent_id;
	private $syslangs;

	public function __construct($id = null){
		if(function_exists('pll_languages_list')) {
    	$this->syslangs = pll_languages_list();
		} else {
			$this->syslangs = null;
		}
    if($id != null) {
			$this->get_source_id($id);
			$this->get_map($this->parent_id);
    }
	}

	public function verbose($msg, $array = null) {
		if($this->verbose === true) {
			var_dump('Error: ' . $msg, $array);
		}
	}
	
	public function get_source_id($id){
		$source = get_post_meta($id, '_sdl_source_id', true);
		if($source == $id || ($source == '' || $source == null || $source == false)) {
			//We're looking at the parent post
			$this->parent_id = $id;
			$this->verbose('This is the parent ' . $this->parent_id);
		} else {
			//We're looking at a child post
			$this->parent_id = $source;
			$this->verbose('Retrieved parent of child ' . $this->parent_id);
		}
		return $this->parent_id;
	}
	public function get_source_map($id){
		$this->parent_id = $this->get_source_id($id);
		$map = $this->get_map($this->parent_id);
		return $map;
	}
	public function get_details($id, $map = null){
		$this->parent_id = $this->get_source_id($id);
		if($map == null) {
			$map = $this->get_map($this->parent_id);
		}
		if($this->parent_id == $id) {
			return $map['parent'];
		} else {
			foreach($map['children'] as $lang => $meta) {
				if($meta['id'] == $id) {
					return $meta;
				}
			}
			return false;
		}
	}
	public function update_details($id, $detail, $value) {
		$details = $this->get_details($id);
		$map = $this->get_map($this->parent_id);

		if($this->parent_id == $id) {
			$map['parent'][$detail] = $value;
		} else {
			$lang = polylang_sdl_get_post_language($id);
			$map['children'][$lang][$detail] = $value;
		}
		$this->sync_all($id, $map);
		return true;
	}
	public function get_old($id = null, $clean = false){
		if($id === null) {
			$id = $this->parent_id;
		}
		if($this->map == null) {
			$this->map = $this->get_map($id);
		}

		$old = array();
		foreach($this->map['children'] as $lang => $meta) {
			if($meta['updated'] < $this->map['parent']['updated'] && $meta != null) {
				$old[$lang] = $meta;
			}
		}
		$this->verbose('Listing out of date translations: ', $old);
		//Sometimes we need to destroy the existing settings to prevent issues/needing to constantly reinitialise post model
		if($clean === true) {
			$this->map = null;
		}
		return $old;
	}
	public function add_to_map($id, $options_set = null) {
		if($this->map === null) {
			$parent_id = $this->get_source_id($id);
			$this->get_map($this->parent_id);
		}
		$target = null;
		if($this->map['parent']['id'] == $id) {
			//We're updating the original post with a translation. Unusual, but could happen in certain cases.
			$this->map = $this->add_parent_map($this->map, $id, $this->map['parent']['lang'], $options_set);
			$this->verbose('Replaced parent in post map', $this->map);
		} else {
			foreach($this->map['children'] as $lang => $meta) {
				if($meta['id'] == $id) {
					$target = $meta['lang'];
				}
			}
			if($target == null){
				$target = polylang_sdl_get_post_language($id);
			}
			$this->map = $this->add_child_map($id, $target, $options_set);
			$this->verbose('Added to post map', $this->map);
		}
		$this->sync_all($id, $this->map);
		return $this->map;
	}
	public function add_in_progress($id, $target, $options_set){
		$this->map = $this->get_source_map($id);
		$target = explode('-', $target)[0];
		$this->map['in_progress'][$target] = $options_set;
		$this->sync_all($id, $this->map);
		return $this->map;
	}
	public function process_in_progress($id){
		$this->map = $this->get_source_map($id);
		$lang = polylang_sdl_get_post_language($id);

		$options_set = $this->map['in_progress'][$lang];

		$this->map = $this->add_to_map($id, $options_set);
		unset($this->map['in_progress'][$lang]);

		$this->sync_all($id, $this->map);
		return $this->map;
	}

	private function get_map($id) {
		$this->map = get_post_meta($id, '_sdl_translation_map', true);
		if($this->map == '' || $this->map == null || $this->map == false) {
			//Map doesn't currently exist. Create a new one
			$this->map = $this->create_map();
		}
		$this->verbose('Retrieved post map for ' . $id, $this->map);
		return $this->map;
	}
	public function create_map($id = null){
		if($id === null) {
			$id = $this->parent_id;
		}
		$this->map = array(
			'parent' => array(
				'id' => $id,
				'lang' => polylang_sdl_get_post_language($id),
				'locale' => polylang_sdl_format_locale(polylang_sdl_get_post_language($id, 'locale')),
				'updated' => get_the_modified_date('U', $id)
			),
			'children' => array(),
			'in_progress' => array()
		);
		$languages = $this->get_post_translations($id);
		foreach($languages as $lang => $post) {
			if($post != $id) {
				if(is_int($post)) {
					$this->add_child_map($post, $lang);
				} else {
					$this->map['children'][$lang] = null;
				}
			}
		}
		update_post_meta($id, '_sdl_translation_map', $this->map);
		$this->verbose('Created new post map', $this->map);
		return $this->map;
	}
	private function add_parent_map($parent_id, $parent_lang, $options_set = null){
		$this->map['parent'] = array(
			'id' => $post,
			'lang' => polylang_sdl_get_post_language($post),
			'locale' => polylang_sdl_format_locale(polylang_sdl_get_post_language($post, 'locale')),
			'updated' => get_the_modified_date('U', $post)
		);
		if($options_set != null) {
			$this->map['parent']['produced_by'] = $options_set;
		}
		$this->verbose('Updated parent details in post map');
		return $map;
	}
	private function add_child_map($child_id, $child_lang, $options_set = null){
		if(!is_array($this->map['children'][$child_lang])) {
			$this->map['children'][$child_lang] = array();
		}
		$this->map['children'][$child_lang]['id'] = $child_id;
		$this->map['children'][$child_lang]['lang'] = $child_lang;
		$this->map['children'][$child_lang]['locale'] = polylang_sdl_format_locale(polylang_sdl_get_post_language($child_id, 'locale'));
		$this->map['children'][$child_lang]['updated'] = get_the_modified_date('U', $child_id);

		if($options_set != null) {
			$this->map['children'][$child_lang]['produced_by'] = $options_set;
		}
		$this->verbose('Added new child translation to post map');
		return $this->map;
	}

    public function get_post_translations($post_id){
        $results = array();
        foreach($this->syslangs as $lang) {
            $results[$lang] = pll_get_post($post_id, $lang);
        }
        return $results;
    }

    private function sync_all($post_id, $map) {
		$this->parent_id = $this->get_source_id($post_id);
		$this->verbose('About to start syncing this map: ', $map);
		update_post_meta($this->parent_id, '_sdl_translation_map', $map);
		if(is_array($map['children'])) {
			foreach($map['children'] as $lang) {
				if($lang != null && is_array($lang)) {
					update_post_meta($lang['id'], '_sdl_translation_map', $map);
				}
			}
		}
		return true;
    }
}

?>
