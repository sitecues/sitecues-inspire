<?php
require_once('general_tools.class.php');
require_once('language.class.php');
require_once('mypdo.class.php');
require_once('login.class.php');
require_once('account.class.php');

class inspire {
	public $pdo;
	public $lang;
	private $general_tools;
	private $login;
	
	function __construct() {
		$this->pdo = self::connect();
		$this->lang = (new language())->GetLanguage();
		$this->general_tools = new general_tools();
	}
	
	public function getContent($login) {
		$this->login = $login;
		if (!file_exists("l18n/inspire_strings.{$this->lang}.js")) {
			$lang = 'en'; // Because we'd better always have English strings.
		}
		$html = strtr(file_get_contents('html\main.html'), array(
			'{{login}}' => $login->user->email,
			'{{lang}}' => $lang
		));
		echo $html;
	}
	
	public static function connect() {
		$pdo = mypdo::connect2('sitecuesadmin', 'M&2Qa}FAbApX_/,&', 'mysql:host=64.90.60.123;port=3306;dbname=sitecues');		
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $pdo;
	}
	
	public function getAccounts($search) {
		$search = $this->general_tools->sanitize($search);
		$f = (strlen($search)) ? " where name like '%$search%' " : "";
		$qs = "select id, name from sitecues.accounts $f order by name";
		$q = $this->pdo->prepare($qs);
		$q->execute();
		return $q->fetchAll();	
	}
	
	public function getAccount($get) {
		$progress = isset($_GET['progress']) ? $_GET['progress'] : true;
		return (new account($progress))->getAccount($this->general_tools->sanitize(array_keys($get)[0]));
	}
	
	public function getProject($get) {
		
	}

	public function getContacts($id) {
		$qs = "select s_name, s_email, t_name, t_email, a_name, a_email from sitecues.accounts where pid=:id";
		$q = $this->pdo->prepare($qs);
		$q->execute(array(
			':id'	=> $id
		));
		return $q->fetchAll();	
	}
	
	public static function getContactName($email) {
		$qs = "select name from sitecues.contacts where email=:email";
		$q = self::connect()->prepare($qs);
		$q->execute(array(
			':email'	=> $email
		));
		return $q->rowCount() == 1 ? $q->fetch(PDO::FETCH_NUM)[0] : '';	
	}

	public function addAccount($get) {
		$success = 0;
		$name = $this->general_tools->sanitize($get['name']);
		$description = $this->general_tools->sanitize($get['description']);
		$tier = $this->general_tools->sanitize($get['tier']);
		$sales_id = $this->general_tools->sanitize($get['sales_id']);
		$qs = "insert into sitecues.accounts (name, description, tier, sales_id, created, updated) values (:name, :description, :tier, :sales_id, NOW(), NOW())";
		try {
			$q = $this->pdo->prepare($qs);
			$q->execute(array(
				':name'	=> $name,
				':description' => $description,
				':tier' => $tier,
				':sales_id' => $sales_id
			));
			$success = $q->rowCount();
		} catch (PDOException $e) {
			$success = $e;
		}
		return array('success' => $success, 'name' => $name, 'id' => $this->pdo->lastInsertId());
	}
	
	public function addProject($get) {
		$qs = "insert into sitecues.projects (pid, url, siteid, created, updated, status, stage, s_name, s_email, t_name, t_email) values (:pid, :url, :siteid, :created, :updated, :status, :stage, :s_name, :s_email, :t_name, :t_email)";
		try {
			$q = $this->pdo->prepare($qs);
			$q->execute(array(
				':pid'	=> $this->general_tools->sanitize($get['pid']),
				':url' => $this->general_tools->sanitize($get['url']),
				':siteid' => $this->general_tools->sanitize($get['siteid']),
				':created'	=> date("Y-m-d", strtotime($this->general_tools->sanitize($get['created']))),
				':updated'	=> date("Y-m-d", strtotime($this->general_tools->sanitize($get['updated']))),
				':status'	=> $this->general_tools->sanitize($get['status']),
				':stage'	=> $this->general_tools->sanitize($get['stage']),
				':s_name'	=> $this->general_tools->sanitize($get['s_name']),
				':s_email'	=> $this->general_tools->sanitize($get['s_email']),
				':t_name'	=> $this->general_tools->sanitize($get['t_name']),
				':t_email'	=> $this->general_tools->sanitize($get['t_email'])
			));
			$success = $q->rowCount();
		} catch (PDOException $e) {
			$success = var_export($e, true);
		}
		return array('success' => $success);
	}
	
	public function getUrlStatus() {
		$qs = "select id, name from sitecues.status";
		$q = $this->pdo->prepare($qs);
		$q->execute();
		return $q->fetchAll(PDO::FETCH_ASSOC);	
	}

	public function getSitecuesContacts() {
		$qs = "select name, email from sitecues.contacts";
		$q = $this->pdo->prepare($qs);
		$q->execute();
		return $q->fetchAll(PDO::FETCH_ASSOC);	
	}
	
	public function getServiceTiers() {
		$qs = "select id, name from sitecues.service_tiers";
		$q = $this->pdo->prepare($qs);
		$q->execute();
		return $q->fetchAll(PDO::FETCH_ASSOC);	
	}

	public function getStages() {
		$qs = "select id, name from sitecues.stages";
		$q = $this->pdo->prepare($qs);
		$q->execute();
		return $q->fetchAll(PDO::FETCH_ASSOC);	
	}

	public function getStatus() {
		$qs = "select id, name from sitecues.status";
		$q = $this->pdo->prepare($qs);
		$q->execute();
		return $q->fetchAll(PDO::FETCH_ASSOC);	
	}

	public function getSitesTable($get) {
		return array('html' => $this->getAccount($get)->buildProjectsTable());
	}
  
  public function getAttachmentsTable($get) {
    return array('html' => $this->getAccount($get)->buildAttachmentsTable());
  }
	
	private function logit($id, $pid, $field, $oldval, $newval, $success) {
		$login = (new login())->DecryptData($_SESSION[login::SESSION_NAME]);
		$qs = "insert into sitecues.log (id, pid, changed, user, field, oldval, newval, success, ip, ua) values (:id, :pid, NOW(), :user, :field, :oldval, :newval, :success, :ip, :ua)";
		$q = $this->pdo->prepare($qs);
		$q->execute(array(
			':id'	=> $id,
			':pid'	=> $pid,
			':user'	=> $login->email,
			':field'	=> $field,
			':oldval'	=> $oldval,
			':newval'	=> $newval,
			':success'	=> $success,
			':ip'	=> $_SERVER['REMOTE_ADDR'],
			':ua'	=> $_SERVER['HTTP_USER_AGENT']
		));
	}
	
	public function updateProject($post) {
		$id = $this->general_tools->sanitize($post['id']);
		$pid = $this->general_tools->sanitize($post['pid']);
		// All of the project fields start with p_, but not in the database, so we need to
		// strip that prefix before using the field name.
		$field = preg_replace('/p_/', '', $this->general_tools->sanitize($post['field']));
		$val = $this->general_tools->sanitize($post['val']);
		// Get the current value before updating
		$qs = "select $field from sitecues.projects where id=:id and pid=:pid";
		try {
			$q = $this->pdo->prepare($qs);
			$q->execute(array(
				':id' => $id,
				':pid' => $pid
			));
			$oldval = json_encode($q->fetch(PDO::FETCH_ASSOC)[$field]);
		} catch (PDOException $e) {
			$oldval = $e;
		}
		$qs = "update sitecues.projects set $field=:val, updated=NOW() where id=:id and pid=:pid";
		$q = $this->pdo->prepare($qs);
		try {
			$q->execute(array(
				':id'	=> $id,
				':pid'	=> $pid,
				':val'	=> $val
			));
			$success = $q->rowCount();
		} catch (PDOException $e) {
			$success = $e;
		}
		// Log the change
		$this->logit($id, $pid, $field, $oldval, $val, $success);
		return array('id' => $id, 'pid' => $pid, 'field' => $field, 'val' => $val, 'success' => $success);
	}
	
	public function updateAccount($post) {
		$id = $this->general_tools->sanitize($post['id']);
		$field = $this->general_tools->sanitize($post['field']);
		$val = $this->general_tools->sanitize($post['val']);
		// Get the current value before updating
		$qs = "select $field from sitecues.accounts where id=:id";
		try {
			$q = $this->pdo->prepare($qs);
			$q->execute(array(
				':id' => $id
			));
			$oldval = json_encode($q->fetch(PDO::FETCH_ASSOC)[$field]);
		} catch (PDOException $e) {
			$oldval = $e;
		}
		$qs = "update sitecues.accounts set $field=:val, updated=NOW() where id=:id";
		$q = $this->pdo->prepare($qs);
		try {
			$q->execute(array(
				':id'	=> $id,
				':val'	=> (($field == 'updated') || ($field == 'created')) ? date("Y-m-d", strtotime($val)) : $val
			));
			$success = $q->rowCount();
		} catch (PDOException $e) {
			$success = $e;
		}
		// Log the change
		$this->logit($id, '', $field, $oldval, $val, $success);
		//return array('field' => $field, 'val' => $val, 'success' => $success);
		return (new account(false))->getAccount($id);
	}

	public function addAttachment($post, $files) {
		$qs = "insert into sitecues.attachments (pid, name, description, added, size, data) values (:pid, :name, :description, NOW(), :size, :data)";
		$q = $this->pdo->prepare($qs);
		$q->bindParam(':pid', $post['pid']);
		$q->bindParam(':name', $files['afile']['name'], PDO::PARAM_STR);
		$q->bindParam(':description', $post['description'], PDO::PARAM_STR);
		$q->bindParam(':size', $files['afile']['size'], PDO::PARAM_INT);
		$fh = fopen($files['afile']['tmp_name'], 'rb');
		$q->bindParam(':data', $fh, PDO::PARAM_LOB);		
		$q->execute();
		fclose($fh);
		return array('success' => $q->rowCount(), 'id' => $post['pid']);
	}
	
	public function deleteAttachment($post) {
		$qs = "delete from sitecues.attachments where id=:id and pid=:pid";
		$q = $this->pdo->prepare($qs);
		$q->execute(array(
			':id' => $post['id'],
			':pid' => $post['pid']
		));
		return array('success' => $q->rowCount(), 'id' => $post['pid']);
	}
	
	public function getAttachment($get) {
		$qs = "select * from sitecues.attachments where pid=:pid and id=:id";
		$q = $this->pdo->prepare($qs);
		$q->execute(array(
			':pid'	=> $this->general_tools->sanitize($get['pid']),
			':id'	=> $this->general_tools->sanitize($get['id'])
		));
		$a = $q->fetchAll(PDO::FETCH_OBJ)[0];
		
		$ext = strtolower(substr($a->name, strrpos($a->name, "."), strlen($a->name)));
		$filename = basename($a->name);
		switch ($ext) {
			case ".txt":
				$mimeType = "text/plain";
				$disposition = "attachment";
				break;
			case ".doc":
				$mimeType = "application/ms-word";
				$disposition = "attachment";
				break;
			case ".xls":
				$mimeType = "application/ms-excel";
				$disposition = "attachment";
				break;
			case ".msp":
			case ".exe":
				$mimeType = "application/octet-stream";
				$disposition = "attachment";
				break;
			case ".zip":
				$mimeType = "application/zip";
				$disposition = "attachment";
				break;
			default:
				$mimeType = "application/octet-stream";
				$disposition = "inline";
				break;
		}
		
		header("Content-Type: $mimeType");
		header("Content-Length: {$a->size}");
		header("Content-Disposition: $disposition; filename=\"$filename\"");
		echo $a->data;
	}
	
	public static function getStatusText($status) {
		$qs = "select name from sitecues.status where id=:id";
		$q = inspire::connect()->prepare($qs);
		$q->execute(array(
			':id'	=> $status
		));
		return $q->fetch()[0];
	}
  
  public function deepSearch($get) {
    $results = array();
    $str = $this->general_tools->sanitize($get['q']);
    $pdo = inspire::connect();
		$qs = "select name, id from sitecues.accounts where name like '%$str%' or sales_id like '%$str%'";
		$q = $pdo->prepare($qs);
		$q->execute();
		while ($r = $q->fetch(PDO::FETCH_OBJ)) {
      if (!array_key_exists($r->id, $results)) {
        $results[$r->id] = $r->name;
      }
    }
    
		$qs = "select pid, url from sitecues.projects where url like '%$str%' or s_name like '%$str%' or s_email like '%$str%' or t_name like '%$str%' or t_email like '%$str%'";
		$q = $pdo->prepare($qs);
		$q->execute();
    
		while ($r = $q->fetch(PDO::FETCH_OBJ)) {
      if (!array_key_exists($r->pid, $results)) {
        // Get the account name
        $as = "select name from sitecues.accounts where id=:pid";
        $a = $pdo->prepare($as);
        $a->execute(array(
          ':pid' => $r->pid
        ));
        $n = $a->fetchAll()[0];
        $results[$r->pid] = $n['name'];
      }
    }
    return $results;
  }
}
?>