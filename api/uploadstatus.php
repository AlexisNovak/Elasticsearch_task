<?php
session_start();
session_write_close();

if(isset($_SESSION['upload_status']) && isset($_GET['sup_id']))
	echo json_encode($_SESSION['upload_status'][$_GET['sup_id']]);
else
	echo json_encode([]);

?>