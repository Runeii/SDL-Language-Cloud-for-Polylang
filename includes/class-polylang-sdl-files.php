<?php

class Polylang_SDL_Files {

	public $translation_storage_path;
	private $verbose = true;

    public function __construct() {
    	$uploads = wp_upload_dir();
		$this->translation_storage_path = $uploads['basedir'] . '/translations/';
    	if(!file_exists($this->translation_storage_path)) {
    		mkdir($this->translation_storage_path);
    	}
    }
    private function verbose($msg) {
    	if($this->verbose === true) {
    		echo '<b>Console: </b>'. $msg .'<br />';
    	}
    }

    public function getFolder($method) {
    	switch ($method)
	    {
	        case "received":
	            $folder = $this->translation_storage_path . 'received/';
	            if(!file_exists($folder)) {
		    		mkdir($folder);
		    	}
		    	return $folder;
	        case "extracted":
	            $folder = $this->translation_storage_path . 'received/unpacked/';
	           	if(!file_exists($folder)) {
		    		mkdir($folder);
		    	}
		    	return $folder;
	        case "converted":
	            $folder = $this->translation_storage_path . 'converted/';
	            if(!file_exists($folder)) {
		    		mkdir($folder);
		    	}
		    	return $folder;
	        default:
	            $folder = $this->translation_storage_path;	            
	            if(!file_exists($folder)) {
		    		mkdir($folder);
		    	}
		    	return $folder;
	    }	
    }
	/*
	// Zip file handling
	*/
	public function extract($file, $folder = null) {
		$zip = new ZipArchive;
		$resource = $zip->open($file);
		if ($resource === TRUE) {
			$zip->extractTo($this->getFolder('extracted') . $folder);
			$zip->close();
			$this->verbose("Translated files unzipped to: " . $this->getFolder('extracted') . $folder);
    		unlink($file);
			return $this->getFolder('extracted') . $folder;
		} else {
			$this->verbose("Error: Zip file is corrupted/not a zip file");
			return false;
		}
	}
}

?>