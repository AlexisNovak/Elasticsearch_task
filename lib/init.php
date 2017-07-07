<?php
include('lib/config.php');
include('lib/session.php');

include('lib/classes/mailClass.php');
include('lib/classes/mainClass.php');
include('lib/classes/documentClass.php');
include('lib/classes/userClass.php');
include('lib/classes/questionClass.php');
include('lib/classes/searchClass.php');
include('lib/classes/paymentClass.php');
include('lib/classes/logClass.php');

$mailClass = new mailClass();
$mainClass = new mainClass();
$documentClass = new documentClass();
$userClass = new userClass();
$questionClass = new questionClass();
$searchClass = new searchClass();
$paymentClass = new paymentClass();
$logClass = new logClass();

if(isLoggedIn())
	loadUserDetails();



/*----------------------------*/

function loadUserDetails() {
	global $userClass, $session_uid, $userDetails;
	$userDetails = $userClass->userDetails($session_uid);
}

function isLoggedIn() {
	global $session_uid;
	if($session_uid == -1)
		return false;
	else
		return true;
}

function requireLogin() {
	if(!isLoggedIn())	{
		$url = BASE_URL . 'login.php';
		header("Location: $url");
		exit();
	}
}
?>