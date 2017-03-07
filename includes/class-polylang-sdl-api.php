<?php

class Polylang_SDL_API {

	private $authtoken;
	private $username;
	private $password;
	private $verbose = true;

    public function __construct($username = null, $password = null) {
    	$this->username = $username ?: get_site_option('sdl_auth_username');
		$this->password = $password ?: get_site_option('sdl_auth_password');
		$this->connect_authtoken();
    }
    private function verbose($msg) {
    	if($this->verbose === true) {
    		echo '<b>Console: </b>'. $msg .'<br />';
    	}
    }
	/*
	// Connection functions
	*/
	public function call($method, $url, $data = false, $auth = false) {
	    $curl = curl_init();
	    $url = 'https://languagecloud.sdl.com/tm4lc/api/v1' . $url;
	    switch ($method)
	    {
	        case "POST":
	            curl_setopt($curl, CURLOPT_POST, 1);

	            if ($data)
	                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
	            break;
	        case "PUT":
	            curl_setopt($curl, CURLOPT_PUT, 1);
	            break;
	        default:
	            if ($data)
	                $url = sprintf("%s?%s", $url, http_build_query($data));
	    }
	    //Is this an authorisation call, or otherwise?
	    if($auth === true) {
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			  'Content-Type: application/x-www-form-urlencoded',
			  'Expect:'
			));	
		} else {
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			  'Authorization: Bearer ' . $this->connect_authtoken()
			));
		}
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	    $result = curl_exec($curl);
		$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	    curl_close($curl);
	    $response = json_decode($result, true);
		if($httpcode == '200') { 
			return $response;
		} else {
			$this->verbose($httpcode . ": " . $response['Message']);
			return false;
		}
	}
	public function download($url, $data = false, $name, $where) {
		set_time_limit(0);
	    $url = 'https://languagecloud.sdl.com/tm4lc/api/v1' . $url;
	    $streamurl = sprintf("%s?%s", $url, http_build_query($data));
	    $curl = curl_init($streamurl);
	    $output = $where . $name . '.zip';
    	if(file_exists($output)) {
			$this->verbose("File exists, deleting:". $output);
    		unlink($output);
    	}
		$file = fopen ($output, 'w+');
		$this->verbose("Downloading to:". $output);

		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
		  'Authorization: Bearer ' . $this->connect_authtoken(),
		  'Content-Type: application/x-www-form-urlencoded'
		));
		curl_setopt($curl, CURLOPT_TIMEOUT, 50);
		curl_setopt($curl, CURLOPT_FILE, $file); 
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		curl_exec($curl); 
		$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		curl_close($curl);
		fclose($file);
		if($httpcode == '200') { 
			return $output;
		} else {
			$this->verbose($httpcode . ": Couldn't download project zip.");
			return false;
		}
	}
	/*
	// Connecting and authenticating
	*/
	public function connect_authtoken() {
		$this->authtoken = get_site_option('sdl_authtoken', null);
		if($this->connect_checkExpired()) {
			$this->verbose("We're getting a fresh token");
			$this->connect_authenticate();
		}
		return $this->authtoken['access_token'];
	}
	public function connect_checkExpired(){
		if(time() > $this->authtoken['expiry']) {
			$this->verbose("Token has expired");
			return true;
		} else {
			$this->verbose("We've already got a valid token");
			return false;
		}
	}
	public function connect_authenticate() {
		$args = array(
			'grant_type' => 'password',
			'username' => $this->username,
			'password' => $this->password
			);
		$response = $this->call('POST', '/auth/token', $args, true);
		if($response != false) {
			$this->authtoken = array();
			$this->authtoken = $response;
			$this->authtoken['expiry'] = time() + $this->authtoken['expires_in'];
			update_site_option('sdl_authtoken', $this->authtoken);
			$this->verbose("All good. We've connected");
		}
	}

	/*
	// Account level functions
	*/
	public function user_options() {
		$response = $this->call('GET', '/projects/options');
		if($response != false) {
			update_site_options('sdl_user_projectoptions', $response);
		}
	}

	/*
	// Project functions
	*/
	public function project_list() {
		return $this->call('GET', '/projects/list');
	}
	public function project_getInfo($id) {
		return $this->call('GET', '/projects/' . $id);
	}
	public function project_getStatusCode($id) {
		$response = $this->project_getInfo($id);
		return $response['Status'];
	}
	public function project_getStatus($id) {
		$response = $this->project_getStatusCode($id);
		return $this->statusDetails($response, 'Name');
	}
	public function project_updateStatus($id, $status) {
		if($status == 'approve') {
			return $this->call('POST', '/projects/' . $id);
		} else if($status == 'cancel') {
			//TODO: Need to check that status is awaiting approval
			return $this->call('DELETE', '/projects/' . $id);
		} else if($status == 'complete') {
			//TODO: Need to check that status is awaiting download
			return $this->call('DELETE', '/projects/' . $id);
		}
	}
	public function project_create($args) {
		/*
		// $args should be an array(
			'Name' => 'Name of the project !required',
			'Description' => 'Description for the project',
			'ProjectOptionsID' => 'ID of the options set for this project !required',
			'SrcLang' => 'Source language !required',
			'Files' => **Files that are attached** (a [ProjectFile] collection)
			'Metadata' => [ProjectMetadata],
			'TmSequenceId' => TM sequence identifier,
			'Vendors' => Sets the vendor ID for this project,
			'Due date' => When the project is due
		) */
		return $this->call('POST', '/projects/', $args);
	}
	public function statusDetails($code, $value = null) {
		$codes = array(
			array(
				'Name' => 'Preparing',
				'Description' => 'The project is being prepared.'
				),
			array(
				'Name' => 'ForApproval',
				'Description' => 'The project is awaiting approval.'
				),
			array(
				'Name' => 'InProgress',
				'Description' => 'The project is in progress.'
				),
			array(
				'Name' => 'ForDownload',
				'Description' => 'For project is awaiting download.'
				),
			array(
				'Name' => 'Completed',
				'Description' => 'The project has been completed.'
				),
			array(
				'Name' => 'PartialDownload',
				'Description' => 'The project is partially available for download.'
				),
			array(
				'Name' => 'InReview',
				'Description' => 'The project is being reviewed.'
				),
			array(
				'Name' => 'Reviewed',
				'Description' => 'The project has been reviewed and is having changes implemented.'
				),
			array(
				'Name' => 'FinalSignOff',
				'Description' => 'The file or project is awaiting final sign off.'
				),
			array(
				'Name' => 'SignedOff',
				'Description' => 'All files in the project were signed off. Final steps are being taken to make the project ready to retrieve.'
				),
			array(
				'Name' => 'ForVendorSelection',
				'Description' => 'The project is ready for vendor selection.'
				)
			);
		if($value === null) { 
			return $codes[$code];
		} else {
			return $codes[$code][$value];
		} 
	}
	/*
	// Translation
	*/
	public function translation_download($id) {
		$args = array(
			'projectId' => $id,
			'types' => 'TargetFiles'
		);
	    $SDL_Files = new Polylang_SDL_Files();
	    $folder = $SDL_Files->getFolder('received');
		$response = $this->download('/projects/'. $id . '/zip', $args, $id, $folder);
		if(!$response) {
			return false;
		}
		$files = $SDL_Files->extract($response, $id);
		if($files) {
			return $files;
		} else {
			return false;
		}
	}
}

?>