<?php

class salesforce {
	private $endpoints = {
		'token' : 'https://login.salesforce.com/services/oauth2/token';
	};
	private $grant_type = 'password';
	private $client_id = '3MVG9ytVT1SanXDlg9WJoeYqrHQDz.MjY8koVXUmHLYFsYIKE_ih3gq34Nu8_T3uwIojBdZhuxaAwel_Zt8QE';
	private $client_secret = '2757278898594492788';
	private $username = 'mzablatsky@aisquared.com';
	private $password = 'sitecues';
	private $ch;
	
	function __construct() {
		$this->ch = curl_init();
		curl_setopt_array($this->ch, array(
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_HEADER => false,
			CURLOPT_VERBOSE => true,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT, 30
		));
	}
	
	function __destruct() {
		curl_close($this->ch);
	}
	
	function getAccessToken() {
		curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_URL, $this->endpoint->token);
		$parms = 'grant_type=password&client_id={$this->client_id}&client_secret={$this->client_secret}&username={$this->username}&password=mypassword123456';
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, "postvar1=value1&postvar2=value2&postvar3=value3");
		$result = curl_exec($ch);
	}
}
?>