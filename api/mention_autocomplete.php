<?php
set_include_path( '..' . DIRECTORY_SEPARATOR);
require('lib/init.php');


if(isset($_GET['term'])) {
	$users = [];
	
	$user_keyword = strtolower('%' . $_GET['term'] . '%');
	
	$db = getDB();
	$stmt = $db->prepare("SELECT * FROM users WHERE lower(username) LIKE :user_keyword OR lower(first_name) LIKE :user_keyword OR lower(last_name) LIKE :user_keyword LIMIT 5");
	$stmt->bindParam("user_keyword", $user_keyword, PDO::PARAM_STR);
	$stmt->execute();
	$data = $stmt->fetchAll(PDO::FETCH_OBJ);
	
	$db = null;
	
	foreach($data as $user_data) {
		$user_label = $user_data->username . ' (' . $user_data->first_name . ' ' . $user_data->last_name . ')';
		$user = (object) array('id' => $user_data->id, 'label' => $user_label, 'value' => $user_data->username);
		array_push($users, $user);
	}
	
	$response = json_encode($users);
	echo $response;
}
?>