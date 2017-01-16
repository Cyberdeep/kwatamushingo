<?php
	class RestAPI
	{
		public $publicKey = "*************";
		public $privateKey = "*************";
		public $currentSessionId="0";
		
		/*
		This function gets the SSO cookie.
		*/
		function getLogin()
		{
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://your_url");
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; rv:11.0) Gecko/20100101 Firefox/11.0');
			curl_setopt($ch, CURLOPT_HEADER,1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			$content = curl_exec($ch);

			// get cookies
			$cookies = array();
			preg_match_all('/set-cookie:(?<cookie>\s{0,}.*)$/im', $content, $cookies);
			$cookie = implode("",$cookies['cookie']);

			return $cookie;
		}
		/*
		Example function to get Session id.
		*/
		function getSession()
		{
			$restAPI = new RestAPI();
			$ssoCookie = $restAPI->getLogin();
			$sessionId = $restAPI->callAPI("GET","sesion",$ssoCookie);
			return $sessionId;
		}

		
		
		/** Returns a base64 encoded SHA256 HMAC using the supplied body and consumer key **/
		function getSignature($body){
			return base64_encode(hash_hmac('sha256', $body, $this->privateKey, true));
		}
		
		/**
		Call the relevant REST Web service and return the results 
		*/
		function callAPI($method, $service, $cookie, $data = false)
		{
			$serviceRootURL = "http://your_second_url".$service;
			
			//HTTP HEADER ARRAY
			$headerArray = array('X-Service: '.$this->publicKey);
			
			$curl = curl_init();

			switch ($method)
			{
				case "POST":
					curl_setopt($curl, CURLOPT_POST, TRUE);

					if ($data){
						$data = json_encode($data);
						//Add the post data to the body
						curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
						
						//Add the Base 64 encoded HMAC to the X-Signature header parameter
						$signature = $this->getSignature($data);
						array_push($headerArray, 'X-Signature: '.$signature );
						print_r($data." <br> ".$signature);
					}
						
					break;
					
				case "PUT":
					curl_setopt($curl, CURLOPT_PUT, TRUE);
					break;
				
				default: //DEFAULTS to GET
					if ($data){
						$serviceRootURL = sprintf("%s?%s", $serviceRootURL, http_build_query($data));
					}
			}
			
			
			curl_setopt($curl, CURLOPT_URL, $serviceRootURL);
			
			//Add private key to header if it has been retrieved
			if($this->currentSessionId!="0"){
				array_push($headerArray, 'X-Session: '.$this->currentSessionId );
			}
			 
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); 
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headerArray);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
			//curl_setopt($curl, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17');
		    curl_setopt($curl, CURLOPT_AUTOREFERER, true); 
		    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		    curl_setopt($curl, CURLOPT_VERBOSE, 1);
		    curl_setopt($curl, CURLOPT_COOKIE, $cookie);
			
			$result = curl_exec($curl);
			
			print_r($result);
	
			curl_close($curl);

			//Return JSON decoded array
			return json_decode($result, true);
		}
		
		

	}
?>