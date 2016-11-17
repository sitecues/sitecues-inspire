<?php
session_start();
require_once('class/general_tools.class.php');
require_once('class/login.class.php');

$login = new login();
$general_tools = new general_tools();
$general_tools->RequireSSL();
$tok = 'inspireLoginToken';

if (isset($_GET['logout'])) {
	$login->Logout();
	unset($login);
	header("Location: index.php?error=Logged%20Out");
	exit();
}

// If there's a valid cookie, store the cookie data in the session variable
if ($login->HasValidCookie() || isset($_SESSION[login::SESSION_NAME])) {
	$data = $login->DecryptData(isset($_SESSION[login::SESSION_NAME]) ? $_SESSION[login::SESSION_NAME] : $login->GetCookie());
	$postVars['email'] = $data->email;
	$postVars['hash'] = $data->hash;
	if ($login->ValidateCredentials($postVars)) {
		DoLogin($login);
	}	
}

if (isset($_POST['validate'])) { // Validate credentials
	$err = '';
	if (empty($_SESSION[$tok]) || empty($_POST[$tok]) || $_POST[$tok] !== $_SESSION[$tok]) {
		// The form didn't originate from us.
		$err = 'Invalid%20source';
	} else {
		if ($login->ValidateCredentials($_POST)) {
			DoLogin($login);
		} else {
			$err = 'Invalid%20Credentials';
		}
	}
	unset($_SESSION[$tok]);
	header("Location: index.php?error=$err");
} else { // Prompt to log in
	$_SESSION[$tok] = md5(time() . rand(1,00));
	echo $login->LoginForm($_SESSION[$tok]);
}

function DoLogin($login) {
	$login->user->UpdateLastLogin();
	$_SESSION[login::SESSION_NAME] = $login->EncryptData();
	if (isset($_POST['rememberme']) && $_POST['rememberme'] == 1) {
		$login->SetCookie();
	}
	require_once('class/inspire.class.php');
	(new inspire())->getContent($login);
	exit();
}

