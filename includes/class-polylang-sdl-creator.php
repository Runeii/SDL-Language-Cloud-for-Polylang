<?php

class Polylang_SDL_Create_XLIFF {

	private $xliff_storage_path;
	private $verbose = false;

	private $doc;
	private $attributes = array(
		'version' => '1.2',
		'original' => 'UNIQUEIDENTIFIER',
		'source-language' => 'en',
		'target-language' => 'de',
		'external-file' => 'http://www.example.com',
		);
	private $structure;
	private $DOMCanvas;

    public function __construct() {
    	$this->doc = new DOMDocument('1.0', get_bloginfo('charset'));
        $this->doc->preserveWhiteSpace = false;
        $this->doc->formatOutput = true;
        $this->doc->xmlStandalone = false;
        $files = new Polylang_SDL_Files;
        $this->xliff_storage_path = $files->getFolder('converted');
    }
    private function verbose($msg) {
    	if($this->verbose === true) {
    		echo '<b>Console: </b>'. $msg .'<br />';
    	}
    }
    private function create_unique($id){
        $type = get_post_type($id);
        $unique = $type . '_' . $id;
        return $unique;
    }

    public function output($id, $src, $target){
    	$attributes = array(
    		'target-language' => $target,
            'original' => $this->create_unique($id),
			'source-language' => $src,
    		'external-file' => get_permalink($id)
    	);
    	$this->attributes = array_replace($this->attributes, $attributes);

    	$this->build_structure($id);
    	$this->build_XLIFF();
		$this->doc->save($this->xliff_storage_path . $this->attributes['original'] . '.xliff');
        return $this->xliff_storage_path . $this->attributes['original'] . '.xliff';
    }

    private function build_structure($id){
	    $this->structure = array(
    		'title' => get_the_title($id),
    		'body' => $this->build_post_content($id)
    	);

    	$taxonomies = get_post_taxonomies($id);
    	foreach($taxonomies as $taxonomy) {
    		if(pll_is_translated_taxonomy($taxonomy)) {
    			$terms = wp_get_post_terms($id, $taxonomy);
    			foreach($terms as $term) {
    				$this->structure['taxonomy'][$term->taxonomy][] = array(
                        'id' => $term->term_id,
                        'name' => $term->name
                        );
    			}
    		}
    	}
        $post_meta = get_post_custom($id);
        foreach($post_meta as $name => $value) {
            //Wordpress stores system meta with the "_" prefix. We don't want these fields.
            if($name[0] != "_" ) { 
                $this->structure['meta'][$name] = $value[0];
            }
        }
	}
    private function build_post_content($id) {
        $content = apply_filters('the_content', get_post_field('post_content', $id));
        $content = str_replace('<br>', '<br />', $content);
        $content = str_replace('<br />', '<br class="xliff-newline" />', $content);
        return $content;
    }

    private function build_XLIFF() {
    	$this->create_file();

    	$header = $this->create_header();
		$this->DOMCanvas->appendChild($header);

    	$body = $this->doc->createElement('body');
    	foreach($this->structure as $name => $content) {
            if($name == 'taxonomy') {
                $tax_i = 0;
                foreach($content as $tax => $value) { 
                    foreach($value as $entry) {
                        $unit = $this->create_transUnit('taxonomy_' . $tax_i, $entry['name'], 'taxonomy', $tax, $entry['id']); 
                    }
                    $body->appendChild($unit);
                    $tax_i++;
                }
            } else if($name == 'meta') {
                $meta_i = 0;
                foreach($content as $meta => $value) {
                    $unit = $this->create_transUnit('meta_' . $meta_i, $value, 'meta', $meta);
                    $body->appendChild($unit);
                    $meta_i++;
                }
            } else {
                $unit = $this->create_transUnit($name, $content);
                $body->appendChild($unit);
            }
    	}
		$this->DOMCanvas->appendChild($body);

    	$root = $this->create_root();
		$this->doc->appendChild($root);
    }
    private function create_root(){
	   	$root = $this->doc->createElement('xliff');
		$root->setAttribute( "version", $this->attributes['version']  );
		$root->setAttribute( "xmlns", "urn:oasis:names:tc:xliff:document:1.2" );
		$root->appendChild($this->DOMCanvas);
		return $root;
    }
    private function create_file(){
		$file = $this->doc->createElement('file');
		$file->setAttribute( "datatype", "plaintext" );
		$file->setAttribute( "original", $this->attributes['original']  );
		$file->setAttribute( "source-language", $this->attributes['source-language'] );
		$file->setAttribute( "target-language", $this->attributes['target-language']  );
		$this->DOMCanvas = $file;
    }
    private function create_header(){
    	$header = $this->doc->createElement('header');
		$resource = $this->doc->createElement('resource');
		$externalfile = $this->doc->createElement('external-file');
		$externalfile->setAttribute( "href", $this->attributes['external-file'] );
		$resource->appendChild($externalfile);
		$header->appendChild($resource);
		return $header;
    }
    private function create_transUnit($name, $content, $res_name = null, $res_value = null , $id = null){
		$unit = $this->doc->createElement('trans-unit');
		$unit->setAttribute( "restype", 'string');
		$unit->setAttribute( "datatype", 'html');
        
        $unit->setAttribute( "id", $name);

        if($res_name === null) {
            $unit->setAttribute( "resname", $name);
        } else {
            $unit->setAttribute( "resname", $res_name);
            $unit->setAttribute( 'wp_' . $res_name, $res_value);
        }
        if($id !== null) {
            $unit->setAttribute( "wp_id", $id);
        }
		$source = $this->doc->createElement('source');
  		$source_cdata = $this->doc->createCDATASection($content);
  		$source->appendChild($source_cdata);
  		$unit->appendChild($source);

		$target = $this->doc->createElement('target');
  		$target_data = $this->doc->createCDATASection($content);
  		$target->appendChild($target_data);
  		$unit->appendChild($target);

		return $unit;
    }
}

?>