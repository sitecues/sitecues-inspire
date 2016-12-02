<?php

class salesforce {
	private $grant_type = 'password';
	private $client_id = '3MVG9ytVT1SanXDlg9WJoeYqrHQDz.MjY8koVXUmHLYFsYIKE_ih3gq34Nu8_T3uwIojBdZhuxaAwel_Zt8QE';
	private $client_secret = '2757278898594492788';
	private $username = 'mzablatsky@aisquared.com';
	private $password = 'sitecues';
	private $ch;

  // Token format:
  //  access_token
  //  instance_url
  //  id
  //  token_type
  //  issued_at
  //  signature
  public $token;
  private $baseUrl;
	
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
  
  public function init() {
    error_log("> > > salesforce::init");
    $this->token = $this->getAccessToken();
    $versions = $this->getVersions();
    $this->baseUrl = $this->token->instance_url . $versions[count($versions) - 1]->url;
    error_log(var_export($this, true));
    return $this;
  }
	
	private function getAccessToken() {
		curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_URL, 'https://login.salesforce.com/services/oauth2/token');
		$parms = "grant_type=password&client_id={$this->client_id}&client_secret={$this->client_secret}&username={$this->username}&password=sitecues";
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $parms);
		return json_decode(curl_exec($this->ch));
	}

  private function getVersions() {
    curl_setopt($this->ch, CURLOPT_POST, 0);
    curl_setopt($this->ch, CURLOPT_URL, $this->token->instance_url . '/services/data');
    return json_decode(curl_exec($this->ch));
  }
  
  public function search($q) {
    error_log("> > > salesforce::search($q)");
    curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer {$this->token->access_token}"));
    curl_setopt($this->ch, CURLOPT_POST, 0);
    $url = $this->baseUrl . '/parameterizedSearch/?q=' . urlencode($q);
    error_log("\turl: $url");
    curl_setopt($this->ch, CURLOPT_URL, $url);
    $result = curl_exec($this->ch);
    error_log("\tresult: $result");
    return json_decode($result);
  }
}
?>