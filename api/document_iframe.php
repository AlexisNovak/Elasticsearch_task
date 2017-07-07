<?php
set_include_path( '..' . DIRECTORY_SEPARATOR);
require('lib/init.php');

if(isset($_GET['document_id'])) {
	$document = $documentClass->findDocument($_GET['document_id'], 0);
	if($document) {
		$file = '../documents/' . $document->file_name;
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		
		if($ext == 'pdf') {
			if (! file_exists($file)) die("$file does not exist!");
			if (! is_readable($file)) die("$file is unreadable!");

			header('Cache-Control: public'); 
			header('Content-Type: application/pdf');
			header('Content-Disposition: inline; filename="' . $document->file_name . '"');
			header('Content-Length: '.filesize($file));

			readfile($file);
		} else {
			echo $document->document_html;
		}
		//echo '<iframe src="' . BASE_URL . '/documents/' . $document->file_name . '" width="100%" height="600px">';
	} else {
		echo '';
	}
}
?>