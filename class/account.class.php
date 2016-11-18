<?php
require_once('inspire.class.php');
require_once('project.class.php');

class account {
	public $id;
	public $name;
	public $projects;
	public $notes;
	public $history;
	public $tier;
	
	private $pdo;
	
	function __construct() {
		$this->pdo = inspire::connect();
		$this->projects = array();
	}
	
	public function getAccount($id) {
		$where = preg_match('/\d+/', $id) ? "id=?" : "name=?";
		$qs = "select * from sitecues.accounts where $where";
		$q = $this->pdo->prepare($qs);
		$q->bindParam(1, $id, PDO::PARAM_STR);
		$q->execute();
		$r = $q->fetch(PDO::FETCH_OBJ);
		$this->id = $r->id;
		$this->name = $r->name;
		$this->notes = $r->notes;
		$this->history = $r->history;
		$this->getProjects();
		
		return $this;
	}
	
	private function getProjects() {
		$qs = "select id from sitecues.projects where pid=:pid";
		$q = $this->pdo->prepare($qs);
		$q->execute(array(
			':pid'	=> $this->id
		));
		while ($r = $q->fetch(PDO::FETCH_OBJ)) {
			$this->projects[] = (new project())->getProject($this->id, $r->id);
		}
	}
	
	private function buildProjectsTable() {
		$foundCount = 0;
		$table = "
		<table id='sitetable' cellspacing='0' cellpadding='10'>
			<thead>
				<tr>
					<th>
						<input src='../img/add.png' aria-label='Add Project' title='Add Project' onclick='newProject();' type='image' role='button'>
					</th>
					<th>Url</th>
					<th>Site ID</th>
					<th>Created</th>
					<th>Status</th>
					<th>Enabled</th>
				</tr>
			</thead>
			<tbody>";
		
		foreach ($this->projects as $project) {
			$foundCount++;
			$img = '';
			switch ($project->url->enabled) {
				case 0:
					$img = 'enabled';
					break;
				case -1:
					$img = 'disabled';
					break;
				default:
					$img = 'error';
					break;
			}
			$checked['txt'] = ucfirst($img);
			$valid = ($project->url->siteid == $project->siteid) ? 'green' : 'red';
			$table .= "
			<tr>
				<td>
					<input src='../img/gear.png' aria-label='Edit {$project->url->url}' title='Edit {$project->url->url}' onclick='editSite({$project->id});' type='image' role='button'>
				</td>
				<td style='width: 25%;' align='center'><a href='#{$project->url->realurl}' onclick='loadProject({$project->id});'>{$project->url->url}</a></td>
				<td style='width: 25%;' align='center'><span class='$valid'>{$project->siteid}</span></td>
				<td style='width: 25%;' align='center'>{$project->created}</td>
				<td style='width: 25%;' align='center'>{$project->statusText}</td>
				<td align='center'>
					<img src='../img/$img.png' aria-label='{$checked['txt']}' title='{$checked['txt']}'>
				</td>
			</tr>";
		}

		if (!$foundCount) {
			$table .= "<tr><td colspan='5' class='nosites'><script type='text/javascript'>document.writeln(parent.strings.IDS_NO_SITES);</script></td></tr>";
		}
		
		$table .= "</tbody></table>";
		
		return $table;
	}

	public function html() {
		$html = strtr(file_get_contents('html\account.html'), array(
			'{{account}}' => json_encode($this),
			'{{projects_count}}' => count($this->projects),
			'{{projects_table}}' => $this->buildProjectsTable(),
			'{{notes}}' => base64_decode($this->notes),
			'{{history}}' => base64_decode($this->history)
		));
		return $html;
	}	
}
?>