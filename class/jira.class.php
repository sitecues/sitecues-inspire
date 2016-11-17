<?php
class jira {
	const JIRA_BASE = 'https://equinox.atlassian.net/rest/api/latest';
	private $ch;
	private $headers;
	
	function __construct() {
		$this->ch = curl_init();
		curl_setopt_array($this->ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_VERBOSE => true,
			CURLOPT_HTTPHEADER => array(
			"authorization: Basic YXNtaXRoQGFpc3F1YXJlZC5jb206L1Byb21hcmsxIUAjJCU=",
			),
		));
		
	}
	
	function __destruct() {
		curl_close($this->ch);
	}
	
	public function search($q) {
		//$url = self::JIRA_BASE . "/search?jql=" . urlencode("project=SC AND summary~$q");
		$url = self::JIRA_BASE . "/search?jql=project%3DSC+AND+summary~$q&fields=summary,created,priority,status";
		curl_setopt($this->ch, CURLOPT_URL, $url);
		try {
			$result = curl_exec($this->ch);
			$err = curl_error($this->ch);
		} catch (Exception $e) {
		}
		return $result;
	}
}
?>
