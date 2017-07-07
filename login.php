<?php
require('lib/init.php');

if(isLoggedIn()) {
	$url = BASE_URL . 'document_add.php';
	header("Location: $url"); // Page redirecting to home.php 
	exit();
}

$errorMsgLogin = '';

if (!empty($_POST['login_submit'])) {	
	$username = $_POST['username'];
	$password = $_POST['password'];
	if (strlen(trim($username)) > 1 && strlen(trim($password)) > 1) {
		$uid = $userClass->userLogin($username, $password);
		if ($uid) {
			$userClass->logAction($uid, 1);
			$url = BASE_URL . 'index.php';
			header("Location: $url");
			exit();
		} else {
			$errorMsgLogin = 'Invalid username or password.';
		}
	} else {
		$errorMsgLogin = 'Invalid username or password.';
	}
}

include('templates/default/header.php');
?>
<div class="container-fluid content">
	<div class="main-container">
		<div class="col-xs-12 col-sm-6 col-sm-offset-3 col-md-4 col-md-offset-4 col-lg-4 col-lg-offset-4">
			<div class="login-form">
				<div class="h1 text-blue">Login</div>

				<form name="form" method="post">
					<div class="form-group<?php if($errorMsgLogin != '') echo ' has-error'; ?>">
						<?php if($errorMsgLogin != '') echo '<span class="help-block with-errors"><ul class="list-unstyled"><li>' . $errorMsgLogin . '</li></ul></span>'; ?>
						<input type="text" name="username" class="form-control" placeholder="Username" required>
					</div>

					<div class="form-group<?php if($errorMsgLogin != '') echo ' has-error'; ?>">
						<!--<span class="help-block with-errors"><ul class="list-unstyled"><li>Password is required</li></ul></span>-->
						<input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
					</div>

					<!--<div class="checkbox">
						<label>
							<input type="checkbox"> Keep me logged in
						</label>
					</div>-->

					<div class="form-actions form-group ">
						<input type="submit" name="login_submit" class="full-width" value="Login">
					</div>
				</form>

				<p class="text-center">
					<a href="password_recover.php">Forgot password?</a>
				</p>
			</div>
		</div>
	</div>
</div>
<?php include('templates/default/footer.php'); ?>