<?php
session_start();
if(!empty($_SESSION['uid'])) {
	$session_uid = $_SESSION['uid'];
} else {
	$session_uid = -1;
}
session_write_close();
?>