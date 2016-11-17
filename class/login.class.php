<?php
require_once('general_tools.class.php');
require_once('inspire.class.php');
require_once('user.class.php');

class login {
	const COOKIE_NAME = 'cookie_inspire_ai2in';
	const SESSION_NAME = 'session_inspire_ai2in';
	const CRYPT_KEY = 'asGMv2Ffm6cCasAcR3ekA8Xm3g8kmYAN';
	
	private $pdo = null;
	private $general_tools = null;
	public $user = null;

	function __construct() {
		$this->pdo = inspire::connect();
		$this->general_tools = new general_tools();
		$this->user = new user();
	}
	
	public function UpdatePassword($email, $oldPass, $newPass) {
		$qs = "update sitecues.user set hash=? where email=? and hash=?;";
		$q = $this->pdo->prepare($qs);
		$q->execute(array($this->generatePassHash($newPass), $email, $this->generatePassHash($oldPass)));		
		return ($q->rowCount() == 1);
	}
	
	public function ValidateCredentials($postVars) {
		$validUser = false;
		$this->user->email = $this->general_tools->sanitize($postVars['email']);
		if (isset($postVars['hash'])) {
			$this->user->hash = $this->general_tools->sanitize($postVars['hash']);
		} else {
			$this->user->hash = $this->generatePassHash($this->general_tools->sanitize($postVars['password']));	
		}		
		$qs = 'select email from sitecues.user where email=:email and hash=:hash;';
		$q = $this->pdo->prepare($qs);
		$q->execute(array(
			':email' => $this->user->email,
			':hash'		=> $this->user->hash
		));
		$rowCount = $q->rowCount();
		if ($rowCount == 1) {
			if ($r = $q->fetch()) {
				$this->user->PopulateUser($r['email']);
				$validUser = true;
			}
		}
		return $validUser;
	}
	
	public function generatePassHash($password) {
		return hash_hmac('sha256', $password, login::CRYPT_KEY, false);
	}
	
	private function encrypt($data) {
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, login::CRYPT_KEY, $data, MCRYPT_MODE_ECB, $iv);
		return trim(base64_encode($encrypted));
	}
	
	private function decrypt($data) {
		$encrypted = base64_decode($data);
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, login::CRYPT_KEY, $data, MCRYPT_MODE_ECB);
		return trim($decrypted);
	}

	public function EncryptData() {
		$encoded = json_encode($this->user);
		$encrypted = $this->encrypt($encoded);
		return $encrypted;
	}
	
	public function DecryptData($sessionData) {
		$retval = null;
		$decrypted = $this->decrypt(base64_decode($sessionData));
		if (strlen($decrypted) > 0) {
			$retval = json_decode($decrypted);
		}
		return $retval;
	}

	public function SetCookie() {
		setcookie(login::COOKIE_NAME, $this->EncryptData(), time() + 60 * 60 * 24 * 30, "/", "gwmicro.com", true, true);
	}
	
	public function GetCookie() {
		return $_COOKIE[login::COOKIE_NAME];
	}
	
	public function HasValidCookie() {
		$retval = false;
		if (isset($_COOKIE[login::COOKIE_NAME])) {
			// Validate the data stored in the cookie.
			$cookied = $this->decrypt(base64_decode($_COOKIE[login::COOKIE_NAME]));
			if (strlen($cookied) > 0) {
				$cookie = json_decode($cookied);
				if (is_object($cookie)) {
					$qs = 'select email from sitecues.user where email=:email and hash=:hash;';
					$q = $this->pdo->prepare($qs);
					$q->execute(array(
						':email' => $this->general_tools->sanitize($cookie->email),
						':hash'		=> $this->general_tools->sanitize($cookie->hash)
					));
					$retval = ($q->rowCount() == 1);
				}
			}
		}
		return $retval;
	}

	public function LoginForm($auth_token) {
		$form = "
		<!DOCTYPE html>
		<head>
		<link rel='stylesheet' type='text/css' href='css/inspire.css'>
		<link rel='stylesheet' type='text/css' href='css/login.css'>
		<script type='text/javascript'>
		var lang = 'en';
		</script>
		<script type='text/javascript' src='l18n/inspire_strings.php?lang=en&f=js'></script>
		<script type='text/javascript'>
		function getParameterByName(name) {
			name = name.replace(/[\[]/, \"\\[\").replace(/[\]]/, \"\\]\");
			var regex = new RegExp(\"[\\?&]\" + name + \"=([^&#]*)\"),
				results = regex.exec(location.search);
			return results === null ? \"\" : decodeURIComponent(results[1].replace(/\+/g, \" \"));
		}
		window.onload = function() {
			var err = getParameterByName('error');
			if (err.length > 0) {
				var errDiv = document.createElement('div');
				errDiv.setAttribute('style', 'color: #ff0000; font-size: 1.2em;');
				errDiv.setAttribute('role', 'log');
				errDiv.setAttribute('aria-live', 'assertive');
				document.getElementById('fields').insertBefore(errDiv, document.getElementById('fields').firstChild);
				errDiv.appendChild(document.createTextNode(err));
			}
			document.getElementById('email').focus();
		}
		</script>
		</head>
		<body>
		<div class='box'>
			<h2 id='logo'>INSPIRE</h2>
			<hr>
			<form id='credForm' method='post' action='index.php' accept-charset='UTF-8'>
				<div id='fields'>
					<p>
						<label for='email'>Email</label>
						<input type='text' value='' name='email' id='email'>
					</p>
					<p>
						<label for='password'>Password</label>
						<input type='password' value='' name='password' id='password' autocomplete='off'>
					</p>
				</div>
				<div>
					<label for='remember_me'>Remember Me</label>
					<input type='checkbox' value='1' name='rememberme' id='rememberme'>
					&nbsp;
					<button type='submit' id='btnLogin'>Log in</button>
				</div>
				<input type='hidden' value='✓' name='validate'>
				<input type='hidden' value='$auth_token' name='inspireLoginToken'>
			</form>    
		</div>
		</body>
		</html>
		";
		return $form;
	}
	
	public function Logout() {
		setcookie(login::COOKIE_NAME, null, time() - 3600, "/", "gwmicro.com", true, true);
		unset($_COOKIE[login::COOKIE_NAME]);
		unset($_SESSION[login::SESSION_NAME]);
		session_destroy();
	}
}
?>