<?php
require('lib/init.php');

if(isLoggedIn()) {
	$url = BASE_URL . 'index.php';
	header("Location: $url"); // Page redirecting to home.php 
	exit();
}

$errorMsgFirstName = '';
$errorMsgLastName = '';
$errorMsgUsername = '';
$errorMsgPassword = '';
$errorMsgPassword2 = '';
$errorMsgEmail = '';

$notifyMsgRegister = '';

$first_name = '';
$last_name = '';
$username = '';
$password = '';
$password_2 = '';
$email = '';

if (!empty($_POST['register_submit'])) {	
	$first_name = $_POST['first_name'];
	$last_name = $_POST['last_name'];
	$username = $_POST['username'];
	$email = $_POST['email'];
	$password = $_POST['password'];
	$password_2 = $_POST['password_2'];
	
	/* Regular expression check */
	$username_check = preg_match('~^[A-Za-z0-9_]{3,20}$~i', $username);
	$email_check = preg_match('~^[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.([a-zA-Z]{2,10})$~i', $email);
	$password_check = preg_match('~^[A-Za-z0-9!@#$%^&*()_]{6,20}$~i', $password);

	if(!$username_check)
		$errorMsgUsername = 'Username must be between 3 and 20 characters.';
	
	if(!$email_check)
		$errorMsgEmail = 'Invalid Email.';
	
	if(!$password_check)
		$errorMsgPassword = 'Password must be between 6 and 20 characters.';
	
	if($password != $password_2)
		$errorMsgPassword2 = 'Confirmation password does not match.';
	
	if($username_check && $email_check && $password_check && $password == $password_2) {
		$userRegistration = $userClass->userRegistration($username, $password, $email, $first_name, $last_name);		
		
		if($userRegistration === 'USERNAME_ALREADY_EXISTS') {
			$errorMsgUsername = 'Username is already in use.';
		} else if($userRegistration === 'EMAIL_ALREADY_EXISTS') {
			$errorMsgEmail = 'Email is already in use.';
		} else if ($userRegistration) {
			$uid = $userRegistration;
			
			$url = BASE_URL . 'index.php';
			header("Location: $url"); // Page redirecting to login.php 
			exit();
		}
	}
}

include('templates/default/header.php');
?>
<div class="container-fluid content">
	<div class="main-container">
		<div class="col-xs-12 col-sm-6 col-sm-offset-3 col-md-4 col-md-offset-4 col-lg-4 col-lg-offset-4">
			<div class="login-form">
				<?php echo $notifyMsgRegister; ?>
								
				<div class="h1 text-blue">Signup</div>

				<form name="form" method="post">
					<div class="form-group<?php if($errorMsgFirstName != '') echo ' has-error'; ?>">
						<?php if($errorMsgFirstName != '') echo '<span class="help-block with-errors"><ul class="list-unstyled"><li>' . $errorMsgFirstName . '</li></ul></span>'; ?>
						<input type="text" name="first_name" value="<?php echo htmlspecialchars($first_name, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="First Name" required>
					</div>
					
					<div class="form-group<?php if($errorMsgLastName != '') echo ' has-error'; ?>">
						<?php if($errorMsgLastName != '') echo '<span class="help-block with-errors"><ul class="list-unstyled"><li>' . $errorMsgLastName . '</li></ul></span>'; ?>
						<input type="text" name="last_name" value="<?php echo htmlspecialchars($last_name, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Last Name" required>
					</div>
					
					<div class="form-group<?php if($errorMsgUsername != '') echo ' has-error'; ?>">
						<?php if($errorMsgUsername != '') echo '<span class="help-block with-errors"><ul class="list-unstyled"><li>' . $errorMsgUsername . '</li></ul></span>'; ?>
						<input type="text" name="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Username" required>
					</div>

					<div class="form-group<?php if($errorMsgPassword != '') echo ' has-error'; ?>">
						<?php if($errorMsgPassword != '') echo '<span class="help-block with-errors"><ul class="list-unstyled"><li>' . $errorMsgPassword . '</li></ul></span>'; ?>
						<input type="password" name="password" value="<?php echo htmlspecialchars($password, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Password" required>
					</div>
					
					<div class="form-group<?php if($errorMsgPassword2 != '') echo ' has-error'; ?>">
						<?php if($errorMsgPassword2 != '') echo '<span class="help-block with-errors"><ul class="list-unstyled"><li>' . $errorMsgPassword2 . '</li></ul></span>'; ?>
						<input type="password" name="password_2" value="<?php echo htmlspecialchars($password_2, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Confirm Password" required>
					</div>
					
					<div class="form-group<?php if($errorMsgEmail != '') echo ' has-error'; ?>">
						<?php if($errorMsgEmail != '') echo '<span class="help-block with-errors"><ul class="list-unstyled"><li>' . $errorMsgEmail . '</li></ul></span>'; ?>
						<input type="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Email" required>
					</div>
					
					<div class="form-actions form-group ">
						<input type="submit" name="register_submit" class="full-width" value="Sign up">
					</div>
					
				</form>

			</div>
		</div>
	</div>
</div>
<?php include('templates/default/footer.php'); ?>