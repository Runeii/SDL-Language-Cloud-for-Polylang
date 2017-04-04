<?php
class Polylang_SDL_Model {

	private $verbose;
	private $map;
	private $parent_id;
	private $syslangs;

	public function __construct($id = null, $verbose = false){
		$this->verbose = $verbose; 
        $this->syslangs = pll_languages_list();
        if($id != null) {
			$this->get_source_id($id);
			$this->get_map($this->parent_id);   	
        }
	}

    private function verbose($msg, $array = null) {
    	if($this->verbose === true) {
    		echo '<b>Console: </b>'. $msg .'<br />';
    		if($array != null) {
    			var_dump($array);
    		}
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
			$lang = pll_get_post_language($id);
			$map['children'][$lang][$detail] = $value;
		}		
		update_post_meta($this->parent_id, '_sdl_translation_map', $map);
		return true;
	}
	public function get_old($id = null){
		if($id === null) {
			$id = $this->parent_id;
		}
		if($this->map == null) {
			$this->map = $this->get_map($this->id);
		}

		$old = array();
		foreach($this->map['children'] as $lang => $meta) {
			if($meta['updated'] < $this->map['parent']['updated'] && $meta != null) {
				$old[$lang] = $meta;
			}
		}
		$this->verbose('Listing out of date translations: ', $old);
		return $old;
	}
	public function add_to_map($id, $options_set = null) {
		if($this->map === null) {
			$this->get_source_id($input);
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
				$target = pll_get_post_language($id);
			}
			$this->map = $this->add_child_map($this->map, $id, $target, $options_set);
			$this->verbose('Added to post map', $this->map);
		}
		update_post_meta($this->parent_id, '_sdl_translation_map', $this->map);
		return $this->map;
	}
	public function add_in_progress($id, $lang, $options_set){
		$this->map = $this->get_source_map($id);
		$this->map['in_progress'][$lang] = $options_set;
		update_post_meta($this->parent_id, '_sdl_translation_map', $this->map);
		return $this->map;
	}
	public function remove_in_progress($id){
		$this->map = $this->get_source_map($id);
		$lang = pll_get_post_language($id);

		$options_set = $this->map['in_progress'][$lang];

		$this->map = $this->add_to_map($this->map['parent']['id'], $options_set);
		unset($this->map['in_progress'][$lang]);

		update_post_meta($this->parent_id, '_sdl_translation_map', $this->map);
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
				'lang' => pll_get_post_language($id),
				'locale' => format_locale(pll_get_post_language($id, 'locale')),
				'updated' => get_the_modified_date('U', $id)
			),
			'children' => array(),
			'in_progress' => array()
		);
		$languages = $this->get_post_translations($id);
		foreach($languages as $lang => $post) {
			if($post != $id) {
				if(is_int($post)) {
					echo $post;
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
			'lang' => pll_get_post_language($post),
			'locale' => format_locale(pll_get_post_language($post, 'locale')),
			'updated' => get_the_modified_date('U', $post)
		);
		if($options_set != null) {
			$this->map['parent']['produced_by'] = $options_set;
		}
		$this->verbose('Updated parent details in post map');
		return $map;
	}
	private function add_child_map($child_id, $child_lang, $options_set = null){
		$this->map['children'][$child_lang] = array(
			'id' => $child_id,
			'lang' => $child_lang,
			'locale' => format_locale(pll_get_post_language($child_id, 'locale')),
			'updated' => get_the_modified_date('U', $child_id)
		);
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
}

?>