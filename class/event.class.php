<?
ob_start();

class eventHandler {
	const E_COMPLETE = 0;
	const E_GETSOURCE = 100;
	const E_PARSESOURCE = 101;
	const E_GETVALIDATION = 102;
	
	public static function fireProgress($msg, $val) {
		self::fireEvent($msg, $val);
	}
	
	private static function fireEvent($msg, $value) {
		$value = base64_encode($value);
		$msg = "data: {\"msg\":\"$msg\", \"value\":\"$value\"}\n";
		$msg .= "\n\n";	
		if (!headers_sent()) {
			header('Content-Type: text/event-stream');
			header('Cache-Control: no-cache');
		}
		echo $msg;
		if (ob_get_contents()) {
			ob_end_flush();
		}
		flush();
		usleep(100);
	}	
}

?>