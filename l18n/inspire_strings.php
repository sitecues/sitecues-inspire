<?php
$lang = (isset($_GET['lang'])) ? $_GET['lang'] : 'en';
$lines = file("inspire_strings.$lang");
$strings = array();
foreach($lines as $line) {
	$parts = preg_split('/=/', $line);
	$strings[trim($parts[0])] = trim($parts[1]);
}
$format = (isset($_GET['f'])) ? $_GET['f'] : '';
switch ($format) {
	case 'js':
		$result = 'var strings = ' . json_encode($strings);
		header("Content-Length: " . strlen($result));
		header("Content-Type: text/javascript");
		header("Content-Disposition: attachment; filename=inspire_strings.$lang.js");
		echo $result;
		break;
	default:
		echo json_encode($strings);
		break;
}
exit();
?>