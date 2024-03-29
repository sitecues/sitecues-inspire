<?php
session_start();
require_once('class/inspire.class.php');

$inspire = new inspire();

if (isset($_GET['action'])) {
	$response = '{}';
	switch ($_GET['action']) {
		case 'getAccounts':
			$response = $inspire->getAccounts(isset($_GET['search']));
			break;
		case 'refreshAccount':
		case 'getAccount':
			$response = $inspire->getAccount($_GET);
			break;
		case 'addAccount':
			$response = $inspire->addAccount($_GET);
			break;
		case 'addProject':
			$response = $inspire->addProject($_GET);
			break;
		case 'getSitesTable':
			$response = $inspire->getSitesTable($_GET);
			break;
    case 'getAttachmentsTable':
      $response = $inspire->getAttachmentsTable($_GET);
      break;
		case 'getSitecuesContacts':
			$response = $inspire->getSitecuesContacts();
			break;
		case 'getServiceTiers':
			$response = $inspire->getServiceTiers();
			break;
		case 'getStages':
			$response = $inspire->getStages();
			break;
		case 'getStatus':
			$response = $inspire->getStatus();
			break;
		case 'newAccountSrc':
			$response = array('html' => file_get_contents('html\new_account.html'));
			break;
		case 'newProjectSrc':
			$html = preg_replace('/{{date}}/', date('m/d/Y', time()), file_get_contents('html\new_project.html'));
			$response = array('html' => $html);
			break;
		case 'newAttachmentSrc':
			$response = array('html' => file_get_contents('html\new_attachment.html'));
			break;
		case 'confirmSrc':
			$response = array('html' => file_get_contents('html\confirm.html'));
			break;
		case 'updateProject':
			$response = $inspire->updateProject($_POST);
			break;
		case 'getUrlStatus':
			$response = $inspire->getUrlStatus();
			break;
		case 'addAttachment':
			$response = $inspire->addAttachment($_POST, $_FILES);
			break;
		case 'getFile':
			$inspire->getAttachment($_GET);
			exit();
			break;
		case 'deleteAttachment':
			$response = $inspire->deleteAttachment($_POST);
			break;
		case 'updateAccount':
			$response = $inspire->updateAccount($_POST);
			break;
    case 'deepSearch':
      $response = $inspire->deepSearch($_GET);
      break;
		default:
			break;
	}

	echo json_encode($response);
	exit();
}
?>