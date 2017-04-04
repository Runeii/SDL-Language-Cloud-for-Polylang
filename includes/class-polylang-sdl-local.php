<?php

class Polylang_SDL_Local {

	private $verbose = false;
    private $post_structure;
    private $syslangs;
    private $post_model;

    public function __construct() {
        $this->syslangs = pll_languages_list();
        $this->post_model = new Polylang_SDL_Model;
    }

    private function verbose($msg) {
    	if($this->verbose === true) {
    		echo '<b>Console: </b>'. $msg .'<br />';
    	}
    }

    public function save_post_translation($structure){
        $this->post_structure = $structure;
        $this->post_structure['attributes']['target-language'] = explode('-', $this->post_structure['attributes']['target-language'])[0];
        
        //Check if a translation in this language is already attached to post - ie, if we're updating an existing translation
        $existing_id = pll_get_post($this->post_structure['original_id'], $this->post_structure['attributes']['target-language']);
        $final_id = $this->update_translation($existing_id);
        update_post_meta($final_id, '_sdl_source_id', $this->post_structure['original_id']);
        return $final_id;
    }
    public function get_term_translations($term_id){
        $results = array();
        foreach($this->syslangs as $lang) {
            $results[$lang] = pll_get_term($term_id, $lang);
        }
        return $results;
    }

    private function update_translation($id){
        if($id === null || $id === false) {
            $id = $this->create_translation_post();
            $this->verbose('We just created a new post translation. ID:' . $id);
        } else {
            $this->update_translation_post($id);
            $this->verbose('We just updated the existing translation. ID:' . $id);
        }
        if(array_key_exists('taxonomy', $this->post_structure)) {
            foreach($this->post_structure['taxonomy'] as $tax => $terms) {
                $trans_terms = $this->save_taxonomy_translations($tax, $terms);
                $this->post_structure['translated_taxonomies'][$tax] = $trans_terms;
            }
            $this->attach_taxonomy_translations($this->post_structure['translated_taxonomies'], $id);
        }
        if(array_key_exists('meta', $this->post_structure)) {
            $this->save_meta_translations($this->post_structure['meta'], $id);
        }
        return $id;
    }
    private function create_translation_post(){
        $args = array(
            'post_title' => html_entity_decode($this->post_structure['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'post_content' => html_entity_decode($this->post_structure['body'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'post_type' => get_post_type($this->post_structure['original_id']),
            'post_status' => get_post_status($this->post_structure['original_id']),
            'post_author' => get_the_author_meta($this->post_structure['original_id']),
            );
        $id = wp_insert_post($args);
        pll_set_post_language($id, $this->post_structure['attributes']['target-language']);
        $translations = $this->get_post_translations($this->post_structure['original_id']);
        $translations[$this->post_structure['attributes']['target-language']] = $id;
        pll_save_post_translations($translations);

        return $id;
    }
    private function update_translation_post($id){
        $args = array(
            'ID' => $id,
            'post_title' => html_entity_decode($this->post_structure['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'post_content' => html_entity_decode($this->post_structure['body'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            );
        $id = wp_update_post($args);
        return $id;
    }
    private function save_taxonomy_translations($tax, $terms){
        $results = array();
        foreach($terms as $id => $name) {
            $name = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $langmap = $this->get_term_translations($id);
            $target_lang = strtolower($this->post_structure['attributes']['target-language']);
            $target_id = $langmap[$target_lang];
            //Does term already exist in target language (ie, do we need to register a new term, or just update)
            if($target_id === null || $target_id === false) {
                $source = get_term( $id, $tax);
                $target_id = wp_insert_term($name, $tax, array(
                    'slug' => $source->slug . '–' . $this->post_structure['attributes']['target-language']
                    ));
                pll_set_term_language($target_id['term_id'], $this->post_structure['attributes']['target-language']);
                $langmap[$this->post_structure['attributes']['target-language']] =  $target_id['term_id'];
                pll_save_term_translations($langmap);
                $final_id = $target_id['term_id'];
            } else {
                wp_update_term($target_id, $tax, array(
                    'name' => $name
                ));
                $final_id = $target_id;
            }
            $results[] = $final_id;
        }
        return $results;
    }
    private function attach_taxonomy_translations($taxonomies, $id) {
        foreach($taxonomies as $tax => $terms) {
            wp_set_post_terms($id, $terms, $tax);
        }
    }
    private function save_meta_translations($meta, $id){
        foreach($meta as $name => $value) {
            update_post_meta(
                $id, 
                html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            );
        }
    }
}

?>