<?php
require_once('class/inspire.class.php');
require_once('class/account.class.php');

if (isset($_GET['action'])) {
	switch ($_GET['action']) {
		case 'getAccount':
			$showProgress = isset($_GET['progress']) ? $_GET['progress'] : true;
			$account = (new account($showProgress))->getAccount($_GET['id']);
			eventHandler::fireProgress(eventHandler::E_COMPLETE, $account->html());
			break;
		default:
			break;
	}
} else {
    $pdo = inspire::connect();
    // Stages
    $qs = "select * from sitecues.stages order by id";
    $q = $pdo->prepare($qs);
    $q->execute();
    $stages = $q->fetchAll(PDO::FETCH_OBJ);
    $stageCounts = array();
    foreach ($stages as $id => $stage) {
      $qs = "select count(id) as count from sitecues.projects where stage=:stage";
      $q = $pdo->prepare($qs);
      $q->execute(array(
        ':stage' => $stage->id
      ));
      $r = $q->fetch(PDO::FETCH_OBJ);
      $stageCounts[$stage->name] = $r->count;
    }
    // Status
    $qs = "select * from sitecues.status order by id";
    $q = $pdo->prepare($qs);
    $q->execute();
    $statusi = $q->fetchAll(PDO::FETCH_OBJ);
    $statusCounts = array();
    foreach ($statusi as $id => $status) {
      $qs = "select count(id) as count from sitecues.projects where status=:status";
      $q = $pdo->prepare($qs);
      $q->execute(array(
        ':status' => $status->id
      ));
      $r = $q->fetch(PDO::FETCH_OBJ);
      $statusCounts[$status->name] = $r->count;
    }

    $stageStr = "";
    foreach ($stageCounts as $name => $count) {
      $stageStr .= "	data.addRow(['$name', $count]);\n";
    }

    $statusStr = "";
    foreach ($statusCounts as $name => $count) {
      $statusStr .= "	data.addRow(['$name', $count]);\n";
    }

    $html = strtr(file_get_contents('html\chart.html'), array(
      '{{stageCounts}}' => $stageStr,
      '{{statusCounts}}' => $statusStr
    ));

    echo $html;  
}
?>