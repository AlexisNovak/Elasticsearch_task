<?php
require('lib/init.php');
requireLogin();

$answers = [];
if (!empty($_POST['search'])) {
	$answers_solr = $searchClass->search(
		'text:\'' . $_POST['content'] . '\'',
		'',
		'',
		1
	);
	
	$answers = $answers_solr;
}

include('templates/default/header.php');
?>
			<div class="login-form" style="max-width: 1000px;">
				<form method="post" enctype="multipart/form-data" id="upload-form">
				<input type="hidden" name="sup_id" value="<?php echo date('Y-m-d-H-i-s') . '_' . uniqid(); ?>">
				<div class="h1 text-blue">Search</div>		
				
					<div class="form-inline">
			
						<input style="width: 700px;" type="text" rows="1" id="content" name="content" class="form-control">
				
						<input style="width: 185px;" type="text" id="reportrange" name="date" class="form-control" placeholder="Date">
						
						<button type="submit" name="search" value="submit">Search</button>
					</div>
					<br />
					
					<?php
					foreach($answers as $answerKey => $file) {
						$filePath = str_replace('/var/www/html/', '', $file['id']);
						
						$fileName = explode('/', $filePath);
						$fileName = $fileName[sizeof($fileName) - 1];
						
						$fileType = explode('.', $fileName);
						$fileType = $fileType[sizeof($fileType) - 1];
						
						$fileSize = $file['size'] >> 10;
						
						$fileUpdateDate = strtotime($file['lastModified']);
						
						$fileUpdateAuthor = array_key_exists('author', $file) ? $file['author'] : 'UNKNOWN';
						
						echo '<img src="16px/' . $fileType . '.png">';
						echo ' - ';
						
						echo '<a href="' . $filePath . '">' . $filePath . '</a>';
						echo ' - ';
						
						echo strtoupper($fileType);
						echo ' - ';
						
						echo $fileSize . ' KB';
						echo '<br />';
						
						echo 'Last updated by '  . $fileUpdateAuthor . ' on ' . date('m/d/y', $fileUpdateDate) . ' at ' . date('h:i A', $fileUpdateDate);
						echo '<br /><br />';
						
						
					}
					?>
					
					<script type="text/javascript">
						$(function() {

							var start = moment().subtract(29, 'days');
							var end = moment();

							function cb(start, end) {
								$('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
							}

							$('#reportrange').daterangepicker({
								//startDate: start,
								//endDate: end,
								parentEl: "#logContainer",
								ranges: {
								   'Today': [moment(), moment()],
								   'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
								   'Last 7 Days': [moment().subtract(6, 'days'), moment()],
								   'Last 30 Days': [moment().subtract(29, 'days'), moment()],
								   'This Month': [moment().startOf('month'), moment().endOf('month')],
								   'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
								}
							}, cb);

							cb(start, end);
							
						});
						</script>

					<!--<div class="form-actions form-group ">
						<button type="submit" name="search" class="full-width" value="submit">Search</button>
					</div>-->
					
					
					
					<script>
					$("#upload-form").submit(function(event) {
						checkUploadStatus();
					});
					
					function checkUploadStatus() {
						var request = new XMLHttpRequest();

						setInterval(function() {
							request.abort();
							request.open('GET', 'api/uploadstatus.php?sup_id=' + $('[name=sup_id]').val(), true);
							request.send();
						}, 500);

						request.onreadystatechange = function(response) {
							if (request.readyState === 4) {
								if (request.status === 200) {
									var jsonOptions = JSON.parse(request.responseText);
									if(jsonOptions.fileCountCurrent > 0) {
										$(".progress-bars").show();
										if(jsonOptions.fileCountTotal > 0) {
											$("#progress-bar-file").width((((jsonOptions.fileCountCurrent) * 100) / jsonOptions.fileCountTotal) + '%');
											$("#progress-bar-file").text(jsonOptions.fileCountCurrent + ' / ' + jsonOptions.fileCountTotal);
										} else {
											$("#progress-bar-file").width(0);
											$("#progress-bar-file").text('');
										}
										
										if(jsonOptions.sectionCountTotal > 0) {
											$("#progress-bar-section").width((((jsonOptions.sectionCountCurrent) * 100) / jsonOptions.sectionCountTotal) + '%');
											$("#progress-bar-section").text(jsonOptions.sectionCountCurrent + ' / ' + jsonOptions.sectionCountTotal);
										} else {
											$("#progress-bar-section").width(0);
											$("#progress-bar-section").text('');
										}
										
										if(jsonOptions.paragraphCountTotal > 0) {
											$("#progress-bar-paragraph").width((((jsonOptions.paragraphCountCurrent) * 100) / jsonOptions.paragraphCountTotal) + '%');
											$("#progress-bar-paragraph").text(jsonOptions.paragraphCountCurrent + ' / ' + jsonOptions.paragraphCountTotal);
										} else {
											$("#progress-bar-paragraph").width(0);
											$("#progress-bar-paragraph").text('');
										}
									}
								}
							}
						};
					};
					</script>
					
				</form>
			</div>
<?php include('templates/default/footer.php'); ?>