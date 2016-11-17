<?php
class language {
	function GetLanguage() {
		$myLang = "en";
		if (!isset($_GET['lang'])) {
			if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
				$langs = explode(",", $_SERVER["HTTP_ACCEPT_LANGUAGE"]);
				if (count($langs) > 0) {
					$match  = 0;
					foreach($langs as $lang) {
						if (preg_match("/\D{2}$/", $lang)) {
							$myLang = $lang;
							if (strpos($myLang, "-")) {
								$split = preg_split("/-/", $myLang);
								$myLang = $split[0];
							}
						}
					}
				}
			}
		} else {
			$myLang = $this->MassageLanguage($_GET['lang']);
		}
		return $myLang;
	}
	
	function MassageLanguage($lang) {
		if (preg_match('/\d{1}/', $lang) != 0) { // Window-Eyes language ids
			switch ($lang) {
				case 11:
					$lang = 'ko';
					break;
				case 7:
					$lang = 'no';
					break;
				case 1:
					$lang = 'pl';
					break;
				case 0:
				default:
					$lang = 'en';
					break;
			}
		}
		return $lang;
	}
}
?>