<?php
set_include_path( '..' . DIRECTORY_SEPARATOR);
require('lib/init.php');


if(isset($_GET['question'])) {
	echo json_encode($searchClass->search(
		$_GET['question'] . '*',
		'entity_type:2 AND state_id:' . $_GET['state_id'],
		'',
		5
	));
} else if(isset($_GET['section'])) {
	echo json_encode($searchClass->search(
		$_GET['section'] . '*',
		'entity_type:1 AND document_type:3 AND state_id:' . $_GET['state_id'],
		'',
		5
	));
} else if(isset($_GET['question_log'])) {	
	$questions = [];
	
	$found_questions = $logClass->findQuestions($_GET['question_log']);
		
	if($found_questions) {
		foreach ($found_questions as $log) {
			if (!in_array($log->param1, $questions))
				array_push($questions, $log->param1);
			
			if(sizeof($questions) == 5)
				break;
		}
		echo json_encode($questions);
	}
}
?>