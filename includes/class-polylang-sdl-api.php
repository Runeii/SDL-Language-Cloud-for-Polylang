<?php

class Polylang_SDL_API {
	private $authtoken;
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
	    if($auth !== true) {
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			  'token_type: access_token',
			  'access_token: ' . $this->connect_authtoken()
			));
		} else {
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			  'Content-Type: application/x-www-form-urlencoded',
			  'Expect:'
			));	
		}                                                               
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	    $result = curl_exec($curl);
	    curl_close($curl);
	    return json_decode($result, true);
	}
	public function connect_authtoken() {
		$authtoken = $this->authtoken;
		if(!isset($authtoken) || $authtoken == '' || time() > $authtoken['expiry']) {
			echo 'We\'re getting a fresh token';
			$var = $this->connect_authenticate();
		}
	}
	public function connect_authenticate($user, $pass) {
		$args = array(
			'grant_type' => 'password',
			'username' => $user,
			'password' => $pass
			);
		$response = $this->call('POST', '/auth/token', $args, true);
		if(isset($response['error'])) {
			return false;
		} else {
			$this->authtoken = array();
			$this->authtoken = $response;
			$this->authtoken['expiry'] = time() + $this->authtoken['expires_in'];
			return true;
		}
	}
}

?>