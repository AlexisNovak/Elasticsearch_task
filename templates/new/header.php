<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title>Material Design Bootstrap</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.0/css/font-awesome.min.css">

    <!-- Bootstrap core CSS -->
    <link href="templates/new/css/bootstrap.min.css" rel="stylesheet">

    <!-- Material Design Bootstrap -->
    <link href="templates/new/css/mdb.min.css" rel="stylesheet">
	
    <link href="templates/new/css/awesomplete.css" rel="stylesheet">
    <link href="templates/new/css/jquery.tagsinput.min.css" rel="stylesheet">
	
	<!-- JQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
	
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
	
    <script type="text/javascript" src="templates/new/js/jquery.tagsinput.min.js"></script>
    <script type="text/javascript" src="templates/new/js/awesomplete.js"></script>

    <!-- Template styles -->
    <style rel="stylesheet">
        /* TEMPLATE STYLES */
        
        main {
            padding-top: 3rem;
            padding-bottom: 2rem;
        }
        
        .widget-wrapper {
            padding-bottom: 2rem;
            /*border-bottom: 1px solid #e0e0e0;*/
            margin-bottom: 2rem;
        }
         
        .reviews {
            text-align: center;
            border-top: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
            padding: 1rem;
            margin-top: 1rem;
            margin-bottom: 2rem;
        }
        
        .price {
            position: absolute;
            left: 0;
            top: 0;
            margin-top: -2px;
        }
        
        .price .tag {
            margin: 0;
        }
		
		.container {
			width: 95%;
		}
    </style>
	
	<script>			
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
		});
	</script>

</head>

<body>


    <header>

        <!--Navbar-->
		<!--
        <nav class="navbar navbar-toggleable-md navbar-dark bg-primary">
            <div class="container">
                <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarNav1" aria-controls="navbarNav1" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand" href="#">
                    <strong>Navbar</strong>
                </a>
                <div class="collapse navbar-collapse" id="navbarNav1">
                    <ul class="navbar-nav mr-auto">
                        <li class="nav-item active">
                            <a class="nav-link">Home <span class="sr-only">(current)</span></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link">Features</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link">Pricing</a>
                        </li>
                        <li class="nav-item dropdown btn-group">
                            <a class="nav-link dropdown-toggle" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Dropdown</a>
                            <div class="dropdown-menu dropdown" aria-labelledby="dropdownMenu1">
                                <a class="dropdown-item">Action</a>
                                <a class="dropdown-item">Another action</a>
                                <a class="dropdown-item">Something else here</a>
                            </div>
                        </li>
                    </ul>
                    <form class="form-inline waves-effect waves-light">
                        <input class="form-control" type="text" placeholder="Search">
                    </form>
                </div>
            </div>
        </nav>
		-->
	    <!--/.Navbar-->

    </header>

    <main>

        <!--Main layout-->
        <div class="container">
            <div class="row">

                <!--Sidebar-->
                <div class="col-lg-2">

                    <div class="widget-wrapper">
                        <!--<h4>Categories:</h4>
                        <br>-->
                        <div class="list-group">
							<?php if(!isLoggedIn()) { ?>
                            <a href="<?php echo BASE_URL; ?>" class="list-group-item">Home</a>
                            <a href="<?php echo BASE_URL . 'login.php'; ?>" class="list-group-item">Login</a>
                            <a href="<?php echo BASE_URL . 'register.php'; ?>" class="list-group-item">Register</a>
							<?php } else { ?>
                            <a class="list-group-item">Hey <?php echo $userDetails->first_name; ?></a>
							<a href="<?php echo BASE_URL . 'feed.php'; ?>" class="list-group-item">Feed</a>
                            <a href="<?php echo BASE_URL . 'document_add.php'; ?>" class="list-group-item">Upload a Document</a>
                            <a href="<?php echo BASE_URL . 'document_search.php'; ?>" class="list-group-item">Search Documents</a>
							<?php } ?>
                        </div>
                    </div>

                    <!--<div class="widget-wrapper">
                        <h4>Subscription form:</h4>
                        <br>
                        <div class="card">
                            <div class="card-block">
                                <p><strong>Subscribe to our newsletter</strong></p>
                                <p>Once a week we will send you a summary of the most useful news</p>
                                <div class="md-form">
                                    <i class="fa fa-user prefix"></i>
                                    <input type="text" id="form1" class="form-control">
                                    <label for="form1">Your name</label>
                                </div>
                                <div class="md-form">
                                    <i class="fa fa-envelope prefix"></i>
                                    <input type="text" id="form2" class="form-control">
                                    <label for="form2">Your email</label>
                                </div>
                                <button class="btn btn-primary">Submit</button>

                            </div>
                        </div>
                    </div>-->

                </div>
                <!--/.Sidebar-->

                <!--Main column-->
                <div class="col-lg-10">