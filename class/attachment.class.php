<?php

class attachment {
	public $id;
	public $pid;
	public $name;
	public $description;
	public $size;
	public $added;
	
	function __construct($a) {
		$this->id = $a->id;
		$this->pid = $a->pid;
		$this->name = $a->name;
		$this->description = $a->description;
		$this->size = $a->size;
		$this->added = $a->added;
	}
	
	public function getAttachment() {
	}
}
?>