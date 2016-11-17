<?php
require_once('inspire.class.php');

class user {
	private $pdo = null;
	
	public $email;
	public $hash;
	public $lastLogin;
	
	function __construct() {
		$this->pdo = inspire::connect();
	}
	
	public function PopulateUser($_email) {
		$this->email = $_email;
		$qs = "select * from sitecues.user where email=:email;";
		$q = $this->pdo->prepare($qs);
		$q->execute(array(
			':email' => $this->email
		));
		if ($q->rowCount() == 1) {
			if ($r = $q->fetch(PDO::FETCH_ASSOC)) {
				$this->lastLogin = $r['lastLogin'];
				$this->UpdateLastLogin();
			}
		}
	}
	
	public function UpdateLastLogin() {
		$qs = "update sitecues.user set lastLogin=:lastLogin where email=:email";
		$q = $this->pdo->prepare($qs);
		$q->execute(array(
			':lastLogin' => date("Y-m-d H:i:s", time()),
			':email' => $this->email
		));
	}
}
?>