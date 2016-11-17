<?php
class issue {
	public $key;
	public $link;
	public $priorityIcon;
	public $title;
	public $created;
	public $statusIcon;
	public $status;
	
	public function __construct($issue) {
		$this->key = $issue->key;
		$this->link = base64_encode("<a href='https://equinox.atlassian.net/browse/{$issue->key}' target='_new'>{$issue->key} {$issue->fields->summary}</a>");
		$this->priorityIcon = base64_encode($issue->fields->priority->iconUrl);
		$this->title = base64_encode($issue->fields->priority->name);
		$this->created = $issue->fields->created;
		$this->statusIcon = base64_encode($issue->fields->status->iconUrl);
		$this->status = $issue->fields->status->name;
	}
}
?>