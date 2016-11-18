<?php
require_once('general_tools.class.php');
require_once('inspire.class.php');
require_once('url.class.php');
require_once('attachment.class.php');
require_once('event.class.php');

class account {
	public $id;
	public $name;
	public $description;
	public $agreement;
	public $s_name;
	public $s_email;
	public $t_name;
	public $t_email;
	public $a_email;
	public $tier;
	public $urls;
	public $stage;
	public $status;
	public $notes;
	public $issues;
	public $attachments;
	public $history;
	public $progress;
	public $urlCount;
	public $issueCount;
	
	private $general_tools;
	private $pdo;
	
	function __construct($name, $showProgress = 0) {
		$this->general_tools = new general_tools();
		$this->pdo = inspire::connect();
		$this->name = $name; //$this->general_tools->sanitize($name);
		if (strlen($this->name) > 0) {
			eventHandler::fireEvent(5, null, $showProgress);
			$this->getAccount();
			eventHandler::fireEvent(6, null, $showProgress);
			$this->getUrls($showProgress);
			$this->urlCount = $this->getUrlCount();
			eventHandler::fireEvent(7, null, $showProgress);
			$this->issueCount = $this->getIssueCount();
			$this->getAttachments();
		} else {
			$this->urls = array();
			$this->urlCount = 0;
			$this->issueCount = 0;
		}
	}
	
	function getAccount() {
		$qs = "select * from sitecues.accounts where name=:name";
		$q = $this->pdo->prepare($qs);
		$q->execute(array(
			':name'	=> $this->name
		));
		$r = $q->fetch(PDO::FETCH_OBJ);
		$this->id = $r->id;
		$this->description = $r->description;
		$this->tier = $r->tier;
		$this->notes = $r->notes;
		$this->history = $r->history;
	}
	
	function getUrls($showProgress) {
		$qs = "select * from sitecues.projects where pid=:id";
		$q = $this->pdo->prepare($qs);
		$q->execute(array(
			':id'	=> $this->id
		));
		while ($r = $q->fetch(PDO::FETCH_OBJ)) {
			$this->urls[] = new url($r, $showProgress);
		}
	}
	
	function getUrlCount() {
		return count($this->urls);
	}
	
	function getIssueCount() {
		$issueCount = 0;
		foreach ($this->urls as $url) {
			$issueCount += count($url->issues, COUNT_RECURSIVE);
		}
		return $issueCount;
	}
	
	function getAttachments() {
		$qs = "select * from sitecues.attachments where pid=:id";
		$q = $this->pdo->prepare($qs);
		$q->execute(array(
			':id'	=> $this->id
		));
		while ($a = $q->fetch(PDO::FETCH_OBJ)) {
			$this->attachments[] = new attachment($a);
		}
	}
}
?>