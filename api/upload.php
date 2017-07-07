<?php
include('../lib/config.php');
include('../lib/session.php');
$userDetails = $userClass->userDetails($session_uid);

include('../lib/classes/mainClass.php');
include('../lib/classes/documentClass.php');
$mainClass = new mainClass();
$documentClass = new documentClass();


$notifyMsgUpload = '';



//echo ini_get('post_max_size');
//echo ini_get('max_file_uploads');

if (!empty($_POST['upload_type'])) {
	if($_POST['upload_type'] != 'document')
		return;
	
	set_time_limit(100000);
	session_start();
	unset($_SESSION['upload_status']);
	session_write_close();
	$pdf_files = $mainClass->getFilesArray($_FILES['file']);
	
	if(empty($pdf_files[0]['name'])) {
		$notifyMsgUpload = $mainClass->alert('error', 'Please select a file.');
	} else {
		$allowed_mime_types = array('text/pdf', 'application/pdf', 'text/html');
		$upload_report = array('names_success' => array(), 'names_fail' => array(), 'names_already' => array());
		session_start();
		$_SESSION['upload_status'] = [
			'fileNameCurrent' => '',
			'fileCountTotal' => sizeof($pdf_files),
			'fileCountCurrent' => 0,
			'sectionCountTotal' => 0,
			'sectionCountCurrent' => 0,
			'paragraphCountTotal' => 0,
			'paragraphCountCurrent' => 0
		];

		//$_SESSION['upload_status']['fileCountTotal'] = sizeof($pdf_files);
		//$_SESSION['upload_status']['fileCountCurrent'] = 0;
		session_write_close();
		foreach ($pdf_files as $pdf_file) {
			session_start();
			$_SESSION['upload_status']['fileCountCurrent'] = $_SESSION['upload_status']['fileCountCurrent'] + 1;
			$_SESSION['upload_status']['fileNameCurrent'] = $pdf_file['name'];
			$_SESSION['upload_status']['sectionCountTotal'] = 0;
			$_SESSION['upload_status']['sectionCountCurrent'] = 0;
			$_SESSION['upload_status']['paragraphCountTotal'] = 0;
			$_SESSION['upload_status']['paragraphCountCurrent'] = 0;
			session_write_close();
			if (in_array($pdf_file['type'], $allowed_mime_types)) {
				$file_info = new SplFileInfo($pdf_file['name']);
				$file_extension = $file_info->getExtension();

				if($file_extension != 'pdf' && $file_extension != 'html') {
					array_push($upload_report['names_fail'], $pdf_file['name']);
				}
				
				$pdf_file['new_name'] = $file_extension . '_' . date('Y-m-d-H-i-s') . '_' . uniqid() . '.' . $file_extension;
				if(move_uploaded_file($pdf_file["tmp_name"], $_SERVER['DOCUMENT_ROOT'] . "/documents/{$pdf_file['new_name']}")) {
					$state = strtoupper($_POST['state']);
					setcookie('state_id', $state, time() + (86400 * 30 * 12), "/");
					$_COOKIE['state_id'] = $state;
					$status = 2;
			
					$add_pdf = $documentClass->addDocument($pdf_file['name'], $pdf_file['new_name'], $userDetails->id, $state, $status);
					if($add_pdf === true)
						array_push($upload_report['names_success'], $pdf_file['name']);
					else
						array_push($upload_report['names_already'], $pdf_file['name']);
						
				} else {
					array_push($upload_report['names_fail'], $pdf_file['name']);
				}
			} else {
				array_push($upload_report['names_fail'], $pdf_file['name']);
			}
		}
		
		$notifyMsgUpload = sizeof($upload_report['names_fail']) > 0 ? $mainClass->alert('error', 'Failed uploads: ' . implode(', ', $upload_report['names_fail'])) : '';
		$notifyMsgUpload .= sizeof($upload_report['names_already']) > 0 ? $mainClass->alert('warning', 'Already uploaded: ' . implode(', ', $upload_report['names_already'])) : '';
		$notifyMsgUpload .= sizeof($upload_report['names_success']) > 0 ? $mainClass->alert('success', 'Uploaded: ' . implode(', ', $upload_report['names_success'])) : '';
	}
}

echo $notifyMsgUpload;
?>