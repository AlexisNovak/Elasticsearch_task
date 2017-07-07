<?php
set_include_path( '..' . DIRECTORY_SEPARATOR);
require('lib/init.php');

if(isLoggedIn()) {
	if(isset($_GET['action'])) {
		if($_GET['action'] == 'session_ping') {
			$userClass->logAction($userDetails->id, 4);
			
			echo 'session_ping logged';
		} else if($_GET['action'] == 'section_click') {
			if(isset($_GET['question']) && isset($_GET['state']) && isset($_GET['section'])) {
				$question = $_GET['question'];
				$state = $_GET['state'];
				$section = $_GET['section'];

				$userClass->logAction($userDetails->id, 3, $question, $state, $section);
				
				echo 'section_click logged';
			} else {
				echo 'action params not set';
			}
		}
	} else {
		echo 'action not set';
	}
} else {
	echo 'not logged in';
}
?>