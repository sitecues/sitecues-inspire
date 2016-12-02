<?php
require_once('inspire.class.php');
require_once('project.class.php');
require_once('salesforce.class.php');

class account {
	public $id;
	public $name;
	public $description;
	public $created;
	public $updated;
	public $sales_id;
	public $projects;
	public $notes;
	public $history;
	public $tier;
	
	private $pdo;
	private $showProgress;
	
	function __construct($showProgress) {
		$this->showProgress = $showProgress;
		$this->pdo = inspire::connect();
		$this->projects = array();
	}
	
	public function getAccount($id) {
		$where = "id";
		if (!preg_match('/^\d+$/', $id)) {
			$where = "name";
			$id = base64_decode($id);
		}
		$where = sprintf("%s='%s'", $where, $id);
		$qs = "select * from sitecues.accounts where $where";
		$q = $this->pdo->prepare($qs);
		$q->execute();
		$r = $q->fetch(PDO::FETCH_OBJ);
		$this->id = $r->id;
		$this->name = $r->name;
		$this->description = $r->description;
		$this->created = date("m/d/Y", strtotime($r->created));
		$this->updated = date("m/d/Y", strtotime($r->updated));
		$this->sales_id = $r->sales_id;
		$this->tier = $r->tier;
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
			$this->projects[] = (new project($this->showProgress))->getProject($this->id, $r->id);
		}
	}
	
	public function buildProjectsTable() {
		$foundCount = 0;
		$table = "
		<table id='sitetable' cellspacing='0' cellpadding='10'>
			<thead>
				<tr>
					<th>Url</th>
					<th>Site ID</th>
					<th>Status</th>
					<th>Validation Errors</th>
					<th>Created</th>
				</tr>
			</thead>
			<tbody>";
		
		foreach ($this->projects as $project) {
			$foundCount++;
			$enabled = '';
			switch (intval($project->url->enabled)) {
				case 0:
					$enabled = 'enabled';
					break;
				case -1:
					$enabled = 'disabled';
					break;
				default:
					$enabled = 'error';
					break;
			}

			$bgColor = '#F3F5B4';
			switch ($project->status) {
				case 4: // Live
					$bgColor = '#E6FCE2';
					break;
				default:
					// Not Live
					if (strtotime($project->updated) < strtotime('-7 days')) {
						$bgColor = '#FCD70B';
					}
					if (strtotime($project->updated) < strtotime('-14 days')) {
						$bgColor = '#D50A29';
					}
					break;
			}
      $validation = json_decode(base64_decode($project->url->validation));
      if (strlen($validation->error) > 0) {
        $valid = $validation->error;
      } else {
        $validations = json_decode(base64_decode($validation->result));
        if (count($validations) > 0) {
          $valid = '';
          foreach ($validations as $validation) {
            foreach ($validation->checks as $check => $val) {
              $valid .= "$check<br>";
            }
          }
        } else {
          $valid = '<script type="text/javascript">document.writeln(parent.strings.IDS_NONE);</script>';
        }
      }
      $table .= "
      <tr>
        <td style='background: $bgColor; width: 20%;' align='center'><a href='#{$project->url->realurl}' onclick='loadProject({$project->id});'>{$project->url->url}</a></td>
        <td style='background: $bgColor; width: 20%;' align='center'>{$project->siteid}</td>
        <td style='background: $bgColor; width: 20%;' align='center'>{$project->statusText}</td>
        <td style='background: $bgColor; width: 20%;' align='center'>$valid</td>
        <td style='background: $bgColor; width: 20%;' align='center'>{$project->created}</td>
      </tr>";
    }
    
		if (!$foundCount) {
			$table .= "<tr><td colspan='6' class='nosites'><script type='text/javascript'>document.writeln(parent.strings.IDS_NO_SITES);</script></td></tr>";
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