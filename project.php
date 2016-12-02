<?php
require_once('class/project.class.php');

if (isset($_GET['action'])) {
	switch ($_GET['action']) {
		case 'getProject':
			$project = (new project())->getProject($_GET['pid'], $_GET['id']);
			eventHandler::fireProgress(eventHandler::E_COMPLETE, "");
			echo json_encode($project);
			break;
		default:
			break;
	}
}

?>