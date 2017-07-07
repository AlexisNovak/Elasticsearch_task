<!DOCTYPE html>
<html lang="en" style="overflow-y: scroll;">
	<head>
		<title>GoFetchCode</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="description">
		<meta name="author">
		<meta name="robots" content="noindex,nofollow">
		<link rel="shortcut icon" href="/favicon.ico">

		<link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Open+Sans:400,300,600&amp;subset=cyrillic,latin">
		<link rel="stylesheet" href="https://netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
			
		<link rel="stylesheet" href="templates/default/css/main_new.css">
		<link rel="stylesheet" href="templates/default/css/font-awesome.min.css">
		<link rel="stylesheet" href="templates/default/css/awesomplete.css" /> 
		<link rel="stylesheet" href="templates/default/css/jquery.tagsinput.min.css" />
		<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
		
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
		<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
		<script src="templates/default/js/jquery.tagsinput.min.js"></script>
			

		<script src="templates/default/js/awesomplete.js"></script> 
		
		<script>
			function toggleNavigation() {
				if(jQuery('.nav-bar').hasClass('nav-open')) {
					jQuery('.nav-bar').removeClass('nav-open');
				} else {
					jQuery('.nav-bar').addClass('nav-open');
					//<div class="overlay overlay--navigation ng-scope" ng-if="isNavOpen" ng-click="toggleNavigation()"></div>
				}
			}
			
			function pingSession() {
				var request = new XMLHttpRequest();
				request.open('GET', 'api/userlog.php?action=session_ping', true);
				request.send();
			}
			
			$(function() {
			  // We can attach the `fileselect` event to all file inputs on the page
			  $(document).on('change', ':file', function() {
				var input = $(this),
					numFiles = input.get(0).files ? input.get(0).files.length : 1,
					label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
				input.trigger('fileselect', [numFiles, label]);
			  });

			  // We can watch for our custom `fileselect` event like this
			  $(document).ready( function() {
				  $(':file').on('fileselect', function(event, numFiles, label) {

					  var input = $(this).parents('.input-group').find(':text'),
						  log = numFiles > 1 ? numFiles + ' files selected' : label;

					  if( input.length ) {
						  input.val(log);
					  } else {
						  if( log ) alert(log);
					  }

				  });
			  });
			  /*$('body').bind('copy cut paste', function(e) {
					if(e.target.name != 'keyword') {
					  console.log('dont copy man');
					  e.preventDefault();
					}
			  });
			  
				pingSession();
				setInterval(pingSession, 10000);*/
			});
		</script>
	</head>

	<body class="header-fixed">
		<div class="wrapper">
			<div class="ng-scope">
				<!--<style>
					@media (min-width: 100px) {
					 .user-navigation { display: none; }
					}
					@media (min-width: 0px) and (max-width: 500px) {
					 #ham { display: block; }
					 .header .logo { width: 150px;
						height: 45px; } 
					 .nav-bar {width: 173px;} 
					 .nav-bar a, .nav-bar .pseudo-link {padding: 14px;}
					}
					@media (min-width: 501px) and (max-width: 10000px) {
					 .user-navigation { display: block; }
					 #ham { display: none; }
					}
				</style>-->
	
				<header class="header">
					<div class="clearfix">
						<a class="col-xs-4 col-sm-2" href="<?php echo BASE_URL; ?>">
							<img class="logo" src="assets/img/GoFetchCode_Logo_53.png" alt="Logo">
						</a>
							

						<?php if(!isLoggedIn()) { ?>
						<div class="col-xs-8 col-sm-10">
							<div class="row user-navigation ng-scope">							
								<a href="<?php echo BASE_URL; ?>" class="btn btn-transparent btn-rounded">Home</a>
								<a href="<?php echo BASE_URL . 'login.php'; ?>" class="btn btn-transparent btn-rounded">Login</a>
								<a href="<?php echo BASE_URL . 'register.php'; ?>" class="btn btn-transparent btn-rounded">Register</a>
							</div>
						</div>
						<?php } else { ?>
						<div class="col-xs-12 col-sm-2 pull-right user-navigation">
							<span class="username ng-binding">Hi <?php echo $userDetails->first_name; ?></span>

							<button onClick="toggleNavigation()">
								<span class="sr-only">Toggle navigation</span>
								<span class="fa fa-bars"></span>
							</button>
						</div>
						
						<div class="nav-bar">
							<div class="close-nav">
								<a onClick="toggleNavigation()">X</a>
							</div>

							<ul class="list-unstyle">
								<li>
									<a onclick="$(this).next('ul').toggleClass('collapse');"><i class="fa fa-file" aria-hidden="true"></i>&nbsp;&nbsp;Documents<span class="caret"></span></a>
									<ul class="list-unstyle cbc-chapters-list collapse">
										<li class="ng-scope"><a href="<?php echo BASE_URL . 'document_list.php'; ?>">View Documents</a></li>
										<li class="ng-scope"><a href="<?php echo BASE_URL . 'document_add.php'; ?>">Upload Document</a></li>
									</ul>
								</li>
								<li>
									<a href="<?php echo BASE_URL . 'logout.php'; ?>"><i class="fa fa-sign-out" aria-hidden="true"></i>&nbsp;&nbsp;Logout</a>
								</li>
							</ul>
						</div>
						<?php } ?>
					</div>
				</header>

			</div>
		