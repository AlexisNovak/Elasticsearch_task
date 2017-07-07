<?php
$url = 'document_add.php';
header("Location: $url"); // Page redirecting to home.php 
exit();
	
//$memberPage = false;
include('lib/config.php');
include('lib/session.php');
$userDetails = $userClass->userDetails($session_uid);

include('templates/default/header.php');
?>
<div class="container">
	<center><label><b>Hello</b></label></center>
</div>
<?php include('templates/default/footer.php'); ?>

<?php
die();
error_reporting(E_ALL);
ini_set('display_errors', 1);


$path_info = parse_path();
switch($path_info['call_parts'][0]) {
  case 'privacypolicy': include 'privacypolicy.php';
    break;
  case 'users': include 'users.php';
    break;
  case 'news': include 'news.php';
    break;
  case 'products': include 'products.php';
    break;
  default:
    include 'questions.php';
}

function parse_path() {
  $path = array();
  if (isset($_SERVER['REQUEST_URI'])) {
    $request_path = explode('?', $_SERVER['REQUEST_URI']);

    $path['base'] = rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/');
    $path['call_utf8'] = substr(urldecode($request_path[0]), strlen($path['base']) + 1);
    $path['call'] = utf8_decode($path['call_utf8']);
    if ($path['call'] == basename($_SERVER['PHP_SELF'])) {
      $path['call'] = '';
    }
    $path['call_parts'] = explode('/', $path['call']);

    /*$path['query_utf8'] = urldecode($request_path[1]);
    $path['query'] = utf8_decode(urldecode($request_path[1]));
    $vars = explode('&', $path['query']);
    foreach ($vars as $var) {
      $t = explode('=', $var);
      $path['query_vars'][$t[0]] = $t[1];
    }*/
  }
return $path;
}
?>