<?
class mypdo {
	public $pdo;
	private $username;
	
	function __construct($username = 'wwwroot') {
		$this->username = $username;
		$result = mypdo::connect($this->username);
		$this->pdo = $result;
		return $result;
	}
	
	static function create() {
		return mypdo::connect('wwwroot');
	}
	
	static function connect($username, $host = 'localhost') {
		$credentials = json_decode(file_get_contents("c:/inetpub/sql_credentials.json"));
		$p = new PDO("mysql:host=$host", $username, $credentials->users->$username);
		$p->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $p;
	}
	
	static function connect2($username, $password, $host) {
		$p = new PDO("$host", $username, $password);
		$p->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $p;
	}
}
?>
