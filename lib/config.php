<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '-1');
/* DATABASE CONFIGURATION */
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'postgres');
define('DB_PASSWORD', 'N736GrB6cZSNYXB9aL9ckZ9H');
define('DB_DATABASE', 'dms');
define("BASE_URL", "http://app.fetchafile.com/");
define("ROOT_DIR", $_SERVER['DOCUMENT_ROOT'] . '');


function getDB() {
	$dbhost = DB_SERVER;
	$dbuser = DB_USERNAME;
	$dbpass = DB_PASSWORD;
	$dbname = DB_DATABASE;
	try {
		$dbConnection = new PDO("pgsql:host=$dbhost;port=5432;dbname=$dbname", $dbuser, $dbpass);
		$dbConnection->exec("set names utf8");
		$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $dbConnection;
	} catch (PDOException $e) {
		echo 'Connection failed: ' . $e->getMessage();
	}

}
?>
