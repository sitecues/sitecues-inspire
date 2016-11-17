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
	
	function __construct() {
		$this->enabled = false;
		$this->siteid = 0;
	}
	
	public function getUrl($url) {
		$this->url = $url;
		$this->niceUrl = $this->getDomain($this->url);
		$this->realurl = parse_url($this->url, PHP_URL_SCHEME) == null ? "http://{$this->url}" : $this->url;
		eventHandler::fireProgress(eventHandler::E_GETSOURCE, "{$this->url}");
		$this->getSource();
		eventHandler::fireProgress(eventHandler::E_PARSESOURCE, "{$this->url}");
		$this->parseSource();
		eventHandler::fireProgress(eventHandler::E_GETVALIDATION, "{$this->url}");
		$this->validation = $this->getValidation();
		return $this;
	}
	
	private function getSource() {
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL => $this->url,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_HEADER => false,
			CURLOPT_VERBOSE => true,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => 10
		));
		$this->source = curl_exec($ch);
		$this->sourceErr = curl_errno($ch);
		curl_close($ch);
	}
	
	private function parseSource() {
		$dom = new DOMDocument;
		libxml_use_internal_errors(true); // Ignore warnings
		$dom->loadHTML($this->source);
		$xp = new DOMXPath($dom);
		$nodes = $xp->query('//script[@data-provider="sitecues"]');
		error_log("nodes length: " . $nodes->length);
		$this->enabled = ($nodes->length > 0) ? 0 : -1;
		if ($this->enabled == 0) {
			$matches = array();
			if (preg_match('/sitecues.config.siteId = \'(s-.+)\';/', $nodes[0]->nodeValue, $matches)) {
				if (count($matches) > 0) {
					$this->siteid = $matches[1];
				}
			}
		}
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
			CURLOPT_URL => "http://nom.sitecues.com:3123/?urls={$this->url}",
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_HEADER => false,
			CURLOPT_VERBOSE => true,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => 10
		));
		$result = curl_exec($ch);
		curl_close($ch);
		return base64_encode($result);
	}
}
?>