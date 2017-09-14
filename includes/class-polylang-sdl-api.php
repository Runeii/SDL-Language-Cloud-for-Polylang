<?php

class Polylang_SDL_API {

	private $authtoken;
	private $username;
	private $password;
	private $verbose = false;

    public function __construct($test = false, $username = null, $password = null) {
    	$this->username = $username ?: get_site_option('sdl_settings_account_username');
			$this->password = $password ?: get_site_option('sdl_settings_account_password');
			if(isset($this->username) && $this->username !== '' || $this->username !== null) {
	    	if($this->password != null && $password === null) {
					$this->password = $this->decrypt($this->password);
	    	}
				$this->authtoken = get_site_option('sdl_authtoken');
				//While working offline, going to completely disable this section
				//See also loggedin test, below

				if($test === false){
					$this->verbose('This is not a test.');
					$this->connect_authtoken();
				}
			}
    }
    public function verbose($msg, $array = null) {
    	if($this->verbose === true) {
    		var_dump('Error: ' . $msg, $array);
    	}
    }
	/*
	// Connection functions
	*/
	public function call($method, $url, $data = false, $auth = false) {
    $url = 'https://languagecloud.sdl.com/tm4lc/api/v1' . $url;
		$args = array();
    //Is this an authorisation call, or otherwise?
    if($auth === true) {
			$args['headers'] = array(
        'Content-Type' => 'application/x-www-form-urlencoded',
			  'Expect:'
    	);
		} elseif ($method === 'JSON') {
			$args['headers'] = array(
        'Content-Type' => 'application/json',
	      'Authorization' => 'Bearer ' . $this->connect_authtoken()
    	);
		} else {
			$args['headers'] = array(
        'Authorization' => 'Bearer ' . $this->connect_authtoken(),
    	);
		}
		$this->verbose('Call to: '. $url);
    switch ($method) {
			case "JSON":
				$data = json_encode($data);
      case "POST":
        $args['body'] = $data;
		    $response = wp_remote_post($url, $args);
        break;
      case "DELETE":
        $args['method'] = 'DELETE';
				$response = wp_remote_request( $url, $args );
        break;
      default:
				if ($data) {
	        $url = sprintf("%s?%s", $url, http_build_query($data));
				}
		    $response = wp_remote_get($url, $args);
				break;
    }
		$code = wp_remote_retrieve_response_code($response);
		$body = wp_remote_retrieve_body($response);
		if($code == '200') {
			return json_decode($body, true);
		} else {
			$this->verbose('Call failed. Error '. $code . ': ' . wp_remote_retrieve_response_message($response));
			return $code;
		}
	}

	public function file_upload($xliff, $optionsid) {
		set_time_limit(0);
		$url = 'https://languagecloud.sdl.com/tm4lc/api/v1/files/' . $optionsid;

		$boundary = wp_generate_password( 24 );
		$headers = array(
			'Authorization' => 'Bearer ' . $this->connect_authtoken(),
			'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
			'Expect' => ''
		);
		$post = array('ProjectOptionsID' => $optionsid);

		// Due to wp_remote_post not working with multipart/form-data, below is a hack to workaround.
		$payload = '';
		foreach ( $post as $name => $value ) {
			$payload .= '--' . $boundary;
			$payload .= "\r\n";
			$payload .= 'Content-Disposition: form-data; name="' . $name . '"' . "\r\n\r\n";
			$payload .= $value;
			$payload .= "\r\n";
		}
		// Upload the file
		if ( $xliff ) {
		 $payload .= '--' . $boundary;
		 $payload .= "\r\n";
		 $payload .= 'Content-Disposition: form-data; name="' . 'upload' . '"; filename="' . basename( $xliff ) . '"' . "\r\n";
		 $payload .= "\r\n";
		 $payload .= file_get_contents( $xliff );
		 $payload .= "\r\n";
		}
		$payload .= '--' . $boundary . '--';
		$response = wp_remote_post( $url,
			array(
				'headers'    => $headers,
				'body'       => $payload,
			)
		);
		$code = wp_remote_retrieve_response_code($response);
		$body = wp_remote_retrieve_body($response);
		if($code == '201') {
			return json_decode($body, true);
		} else {
			$this->verbose('Upload failed. HTTP code: '. $code);
			return $code;
		}
	}
	//$response = $this->download('/projects/'. $id . '/zip', $args, $id, $folder);
	public function download($url, $data = false, $name, $where) {
		set_time_limit(0);
    $url = 'https://languagecloud.sdl.com/tm4lc/api/v1' . $url;
    $streamurl = sprintf("%s?%s", $url, http_build_query($data));
    $curl = curl_init($streamurl);
		$this->verbose('Stream URL: '. $streamurl);
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
		if($this->authtoken === false || $this->connect_checkExpired()) {
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
		if(is_array($response)) {
			$this->connect_saveAuthToken($response);
			$this->verbose("All good. We've connected");
			return true;
		} else {
			$this->verbose("We failed to reauthenticate. Error code: " . $httpcode);
			return $httpcode;
		}
	}
	private function connect_saveAuthToken($token){
		$this->authtoken = array();
		$this->authtoken = $token;
		$this->authtoken['expiry'] = time() + $this->authtoken['expires_in'];
		update_site_option('sdl_authtoken', $this->authtoken);
	}
	/*
	// User testing functions
	*/
	public function test_loggedIn() {
		$this->authtoken = get_site_option('sdl_authtoken', null);
		if($this->authtoken != false) {
			if($this->connect_checkExpired()) {
				return $this->testCredentials();
			} else {
				return true;
			}
		} else {
			return false;
		}
	}
	public function testCredentials($u = null, $p = null){
		$username = $u ?: $this->username;
		$password = $p ?: $this->password;

		$args = array(
			'grant_type' => 'password',
			'username' => $username,
			'password' => $password
			);
		$response = $this->call('POST', '/auth/token', $args, true);
		if(is_array($response)) {
			$this->connect_saveAuthToken($response);
			return true;
		} else {
			return false;
		}
	}

	/*
	// Account level functions
	*/
	public function user_options() {
		$response = $this->call('GET', '/projects/options');
		if(is_array($response)) {
			update_site_option('sdl_settings_projectoptions_all', $response);
			$options = array(
				0 => array(
					'Source' => array(),
					'Target' => array()
					)
				);

			foreach($response as $option) {
				$options[$option['Id']] = array(
					'Source' => array(),
					'Target' => array()
					);
				foreach($option['LanguagePairs'] as $pair) {
					if(!in_array($pair['Source']['CultureCode'], $options[$option['Id']]['Source']) ) {
						$options[$option['Id']]['Source'][] = $pair['Source']['CultureCode'];
					}
					if(!in_array($pair['Target']['CultureCode'], $options[$option['Id']]['Target'])){
						$options[$option['Id']]['Target'][] = $pair['Target']['CultureCode'];
					}
				}
			}
			update_site_option('sdl_settings_projectoptions_pairs', $options);
			return $response;
		} else {
			return false;
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
		return $this->system_statusDetails($response, 'Name');
	}
	public function project_updateStatus($id, $status) {
		if($status == 'approve') {
			return $this->call('POST', '/projects/' . $id);
		} elseif($status == 'cancel') {
			//TODO: Need to check that status is awaiting approval
			return $this->call('DELETE', '/projects/' . $id);
		} elseif($status == 'complete') {
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
		return $this->call('JSON', '/projects', $args);
	}

	/*
	// System functions
	*/
	public function system_languages() {
		return $this->call('GET', '/resources/uilanguages');
	}
	public function system_statusDetails($code, $value = null) {
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
	public function translation_create($id, $args, $redirect = null){
		if(!is_array($id)) {
			$id = array($id);
		}
		if(!isset($args['Name'])) {
			if(sizeof($id) > 1) {
				$args['Name'] = 'Bulk translation – ' . date('H:i jS M');
			} else {
				$args['Name'] = get_the_title($id[0]);
			}
		}
		$args['Name'] = substr($args['Name'], 0, 49);
		if(!isset($args['Description'])) {
			$args['Description'] = 'Quick translation project';
		}
		$convertor = new Polylang_SDL_Create_XLIFF;
		$args['Files'] = $convertor->package_post($id, $args);
		$targets = $args['Targets'];
		unset($args['Targets']);
		$response = $this->project_create($args);
		if(is_array($response)) {
			$inprogress = get_option('sdl_translations_inprogress');
			if($inprogress == null) {
				$inprogress = array();
			}
			$inprogress[] = $response['ProjectId'];
			update_option('sdl_translations_inprogress', $inprogress);
			foreach($id as $post) {
				$post_model = new Polylang_SDL_Model;
				foreach($targets as $target) {
					$map = $post_model->add_in_progress($post, $target, $args['ProjectOptionsID']);
				}
			}
			$reply = array('translation_success' =>  count($id));
		}
		else {
			$reply = array('translation_error' => urlencode('API error ' . $response .'. Sent: ' . JSON_encode($args)));
		}
		return $reply;
	}
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
	private function decrypt($string) {
	    $output = false;

	    $encrypt_method = "AES-256-CBC";
	    $secret_key = wp_salt();
	    $secret_iv = 'managedtranslation';
	    $key = hash('sha256', $secret_key);

	    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
	    $iv = substr(hash('sha256', $secret_iv), 0, 16);

	    $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);

	    return $output;
	}
}

?>
