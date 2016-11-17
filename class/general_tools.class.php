<?
class general_tools {
	function sanitize($str) {
		// FILTER_SANITIZE_STRING - Strip tags, optionally strip or encode special characters.
		// FILTER_FLAG_STRIP_LOW - Strips characters that has a numerical value <32.
		// FILTER_FLAG_STRIP_HIGH - Strips characters that has a numerical value >127.
		$retval = filter_var( $str, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
		return $retval;
	}
	
	function isValidEmail($email) {
		$email = $this->sanitize($email);
		$emailParts = preg_split('/@/', $email);
		if (is_array($emailParts) && count($emailParts) > 1) {
			$domain = $emailParts[1];
			exec("nslookup -type=mx $domain 2>&1", $output);
			foreach($output as $line) {
				error_log($line);
				if (substr($line, 0, strlen($domain)) == $domain) {
					return true;
					// We should actually connect to the $mx and see if the user exists
					// by sending the $mx helo, mail from, rcpt to, and see if we get an OK response
					//$lineParts = preg_split('/\s/', $line);
					//$mx = $lineParts[count($lineParts) - 1];
				}
			}
		}
		return false;
	}
	
	public function RequireSSL() {
		if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
			header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
			exit();
		}
	}
	
	static function linkify($str) {
		return preg_replace('/([a-z]+\:\/\/[a-z0-9\-\.]+\.[a-z]+(:[a-z0-9]*)?\/?([a-z0-9\-\._\:\?\,\'\/\\\+&%\$#\=~])*[^\.\,\)\(\s])/i', '<a href="\1">\1</a>', $str);
	}
	
	static function prettyFilesize($bytes, $decimals = 2) {
		$sz = 'BKMGTP';
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
	}	
}
?>