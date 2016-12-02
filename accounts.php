<?php
require_once('class/inspire.class.php');
require_once('class/account.class.php');
require_once('class/event.class.php');

if (count($_GET) == 0) {
	// $pdo = inspire::connect();
	// // Stages
	// $qs = "select * from sitecues.stages order by id";
	// $q = $pdo->prepare($qs);
	// $q->execute();
	// $stages = $q->fetchAll(PDO::FETCH_OBJ);
	// $stageCounts = array();
	// foreach ($stages as $id => $stage) {
		// $qs = "select count(id) as count from sitecues.accounts where stage=:stage";
		// $q = $pdo->prepare($qs);
		// $q->execute(array(
			// ':stage' => $stage->id
		// ));
		// $r = $q->fetch(PDO::FETCH_OBJ);
		// $stageCounts[$stage->name] = $r->count;
	// }
	// // Status
	// $qs = "select * from sitecues.status order by id";
	// $q = $pdo->prepare($qs);
	// $q->execute();
	// $statusi = $q->fetchAll(PDO::FETCH_OBJ);
	// $statusCounts = array();
	// foreach ($statusi as $id => $status) {
		// $qs = "select count(id) as count from sitecues.account_urls where status=:status";
		// $q = $pdo->prepare($qs);
		// $q->execute(array(
			// ':status' => $status->id
		// ));
		// $r = $q->fetch(PDO::FETCH_OBJ);
		// $statusCounts[$status->name] = $r->count;
	// }
	
	// $stageStr = "";
	// foreach ($stageCounts as $name => $count) {
		// $stageStr .= "	data.addRow(['$name', $count]);\n";
	// }

	// $statusStr = "";
	// foreach ($statusCounts as $name => $count) {
		// $statusStr .= "	data.addRow(['$name', $count]);\n";
	// }
	
	// $html = strtr(file_get_contents('html\chart.html'), array(
		// '{{stageCounts}}' => $stageStr,
		// '{{statusCounts}}' => $statusStr
	// ));
	$html = "Updated charts coming soon...";
	echo $html;
} else {
	// header('Content-Type: text/event-stream');
	// header('Cache-Control: no-cache');
	// ob_start();
	$showProgress = isset($_GET['progress']) ? $_GET['progress'] : 0;
	$inspire = new inspire();
	eventHandler::fireEvent(1, null, $showProgress);
	$account = $inspire->getAccount($_GET);
	eventHandler::fireEvent(2, null, $showProgress);
	$sites_table = $inspire->buildSitesTable($account->urls);
	eventHandler::fireEvent(3, null, $showProgress);
	$issues_table = $inspire->buildIssuesTable($account->urls);
	eventHandler::fireEvent(8, null, $showProgress);
	$attachments_table = $inspire->buildAttachmentsTable($account->attachments);
	eventHandler::fireEvent(4, null, $showProgress);
	$html = strtr(file_get_contents('html\account.html'), array(
		'{{site_count}}' => $account->urlCount,
		// '{{issue_count}}' => $account->issueCount,
		// '{{proj}}' => json_encode($account),
		'{{account_id}}' => $account->id,
		'{{account_name}}'	=> $account->name,
		'{{account_description}}' => $account->description,
		// '{{s_name}}' => $account->s_name,
		// '{{s_email}}' => $account->s_email,
		// '{{t_name}}' => $account->t_name,
		// '{{t_email}}' => $account->t_email,
		// '{{a_email}}' => $account->a_email,
		'{{sites_table}}'	=> $sites_table,
		// '{{issues_table}}'	=> $issues_table,
		// '{{attachments_table}}' => $attachments_table,
		'{{notes}}' => base64_decode($account->notes),
		'{{history}}' => base64_decode($account->history)
	));
	eventHandler::fireEvent(0, $html, $showProgress);
}
?>