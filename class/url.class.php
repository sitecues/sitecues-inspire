<?php
require_once('issue.class.php');
require_once('event.class.php');

class url {
	public $url;
	public $niceUrl;
	public $realUrl;
	public $enabled;
	public $siteid;
	public $validation;
	
	private $source;
	private $sourceErr;
	private $showProgress;
	
	function __construct($showProgress) {
		$this->showProgress = $showProgress;
		$this->enabled = 100;
	}
	
	public function getUrl($url, $siteid) {
		$this->url = $url;
		$this->siteid = $siteid;
		$this->niceUrl = $this->getDomain($this->url);
		$this->realurl = parse_url($this->url, PHP_URL_SCHEME) == null ? "http://{$this->url}" : $this->url;
		// if ($this->showProgress) {
			// eventHandler::fireProgress(eventHandler::E_GETSOURCE, "{$this->url}");
		// }
		// $this->getSource();
		// if ($this->showProgress) {
			// eventHandler::fireProgress(eventHandler::E_PARSESOURCE, "{$this->url}");
		// }
		// $this->parseSource();
		if ($this->showProgress) {
			eventHandler::fireProgress(eventHandler::E_GETVALIDATION, "{$this->url}");
		}
		$this->validation = $this->getValidation();
		return $this;
	}
	
	private function getDomain($url) {
		// Make sure that the url always begins with a protocol
		$url = parse_url('http://' . str_replace(array('https://', 'http://'), '', $url), PHP_URL_HOST);
		if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $url, $regs)) {
			return $regs['domain'];
        }
		return null;
	}
	
	private function getValidation() {
		$ch = curl_init();
		curl_setopt_array($ch, array(
      CURLOPT_URL => "http://nom.sitecues.com:3123/?urls={$this->url}|{$this->siteid}",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => false,
			CURLOPT_CONNECTTIMEOUT => 15,
			CURLOPT_TIMEOUT => 15
		));
    $result = curl_exec($ch);
    $err = curl_error($ch);
		curl_close($ch);
		return base64_encode(json_encode(array('error' => $err, 'result' => base64_encode($result))));
	}
}
?>