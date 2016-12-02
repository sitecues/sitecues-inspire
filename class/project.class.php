<?php
require_once('general_tools.class.php');
require_once('inspire.class.php');
require_once('url.class.php');
require_once('attachment.class.php');
require_once('jira.class.php');
require_once('event.class.php');

class project {
	public $pid;
	public $id;
	public $url;
	public $siteid;
	public $created;
	public $updated;
	public $status;
	public $statusText;
	public $stage;
	public $s_name;
	public $s_email;
	public $t_name;
	public $t_email;
	public $progress;
	public $tables;
	public $description;
	
	private $general_tools;
	private $pdo;
	private $showProgress;
	
	function __construct($showProgress) {
		$this->showProgress = $showProgress;
		$this->general_tools = new general_tools();
		$this->pdo = inspire::connect();
		$this->tables = array();
	}
	
	public function getProject($pid, $id) {
		$this->pid = $pid;
		$this->id = $id;
		$qs = "select * from sitecues.projects where pid=:pid and id=:id";
		$q = $this->pdo->prepare($qs);
		$q->execute(array(
			':pid'	=> $this->pid,
			':id'	=> $this->id
		));
		$this->parseProject($q->fetch(PDO::FETCH_OBJ));
		return $this;
	}
	
	private function parseProject($p) {
		$this->siteid = $p->siteid;
		$this->url = (new url($this->showProgress))->getUrl($p->url, $this->siteid);
		$this->created = date("m/d/Y", strtotime($p->created));
		$this->updated = date("m/d/Y", strtotime($p->updated));
		$this->status = $p->status;
		$this->statusText = inspire::getStatusText($this->status);
		$this->stage = $p->stage;
		$this->s_name = urlencode(htmlspecialchars_decode($p->s_name,  ENT_QUOTES | ENT_HTML401)); // So names like O'Leary will print correctly, instead of O&#x37Leary.
		$this->s_email = $p->s_email;
		$this->t_name = urlencode(htmlspecialchars_decode($p->t_name,  ENT_QUOTES | ENT_HTML401));
		$this->t_email = $p->t_email;
		$this->description = $p->description;
		$this->buildIssuesTable();
		$this->buildAttachmentsTable();
	}
	
	private function getIssues() {
		$issues = array();
		$jira = json_decode((new jira())->search($this->url->niceUrl));
		if (is_object($jira) && isset($jira->issues)) {
			foreach ($jira->issues as $issue) {
				if (!isset($this->issues[$issue->key])) {
					$issues[$issue->key] = new issue($issue);
				}
			}
		}
		return $issues;
	}
	
	private function getAttachments() {
		$attachments = array();
		$qs = "select * from sitecues.attachments where pid=:id";
		$q = $this->pdo->prepare($qs);
		$q->execute(array(
			':id'	=> $this->id
		));
		while ($a = $q->fetch(PDO::FETCH_OBJ)) {
			$attachments[] = new attachment($a);
		}
		return $attachments;
	}
	
	private function buildIssuesTable() {
		$issues = $this->getIssues();
		$foundCount = 0;
		$table = "
		<table id='issuetable' cellspacing='0' cellpadding='10'>
			<thead>
				<tr>
					<th>Priority</th>
					<th>URL</th>
					<th>Issue</th>
					<th>Created</th>
					<th>Status</th>
				</tr>
			</thead>
			<tbody>";
		foreach ($issues as $issue) {
			$foundCount++;
			$priorityIcon = base64_decode($issue->priorityIcon);
			$title = base64_decode($issue->title);
			$link = base64_decode($issue->link);
			$created = date("m/d/Y", strtotime($issue->created));
			$table .= "
			<tr>
				<td align='center'>
					<img src='$priorityIcon' title='$title'>
				</td>
				<td style='width: 25%;' align='center'>{$this->url->niceUrl}</td>
				<td style='width: 25%;'>$link</td>
				<td style='width: 25%;' align='center'>$created</td>
				<td style='width: 25%;' align='center'>{$issue->status}</td>
			</tr>";
		}
		
		$table .= "</tbody></table>";
		
		if (!$foundCount) {
			$table = "<div id='noissues'></div>";
		}

		$this->tables['issues'] = base64_encode($table);
	}

	private function buildAttachmentsTable() {
		$attachments = $this->getAttachments();
		$foundCount = 0;
		$table = "
		<table id='attachmentstable' cellspacing='0' cellpadding='10'>
			<thead>
				<tr>
					<th></th>
					<th>Name</th>
					<th>Description</th>
					<th>Size</th>
					<th>Date</th>
					<th></th>
				</tr>
			</thead>
			<tbody>";
		if ($attachments) {
			foreach ($attachments as $attachment) {
				$foundCount++;
				$ext = strtolower(substr($attachment->name, strrpos($attachment->name, "."), strlen($attachment->name)));
				$icon = file_exists("..\img\$ext.png") ? "..\img\$ext.png" : "..\img\generic.png";
				$table .= "
				<tr>
					<td style='width: 5%;' align='center'>
						<img src='$icon' title='$ext'>
					</td>
					<td style='width: 20%;' align='center'><a href='..\inspire.php?action=getFile&id={$attachment->id}&pid={$attachment->pid}'>{$attachment->name}</a></td>
					<td style='width: 20%;' align='center'>{$attachment->description}</td>
					<td style='width: 5%;' align='center'>{$attachment->size}</td>
					<td style='width: 5%;' align='center'>" . date("m/d/Y", strtotime($attachment->added)) . "</td>
					<td style='width: 1%;' align='center'><img src='../img/close.png' aria-label='Delete {$attachment->name}' title='Delete {$attachment->name}' onClick='deleteAttachment({$attachment->id}, {$attachment->pid});'></td>
				</tr>";
			}
		}
		$table .= "</tbody></table>";
		
		if (!$foundCount) {
			$table = "<div id='noattachments'></div>";
		}

		$this->tables['attachments'] = base64_encode($table);
	}
}
?>