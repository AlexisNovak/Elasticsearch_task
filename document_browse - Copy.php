<?php
require('lib/init.php');
requireLogin();

include('templates/default/header.php');
?>
			<div class="login-form">
				<form method="post" enctype="multipart/form-data" id="upload-form">
				<input type="hidden" name="sup_id" value="<?php echo date('Y-m-d-H-i-s') . '_' . uniqid(); ?>">
				<div class="h1 text-blue">Search</div>		
				
				
					<div class="form-group">
						<label>Content</label>
						<input type="text" rows="1" id="content" name="content" class="form-control">
					</div>
				
				
					<?php $errorMsgTags = ''; ?>
					<?php $tags = ''; ?>
					<label>Tags</label>
					<div class="form-group<?php if($errorMsgTags != '') echo ' has-error'; ?>">
						<?php if($errorMsgTags != '') echo '<span class="help-block with-errors"><ul class="list-unstyled"><li>' . $errorMsgTags . '</li></ul></span>'; ?>
						<input type="text" rows="1" id="tags" name="tags" value="<?php echo htmlspecialchars($tags, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Tags">
					</div>
					<script>
					$('#tags').tagsInput({defaultText: '', height: '47px'});
					</script>
					
					<?php $errorMsgMentions = ''; ?>
					<?php $mentions = ''; ?>
					<label>Made by</label>
					<div class="form-group<?php if($errorMsgMentions != '') echo ' has-error'; ?>">
						<?php if($errorMsgMentions != '') echo '<span class="help-block with-errors"><ul class="list-unstyled"><li>' . $errorMsgMentions . '</li></ul></span>'; ?>
						<input type="text" id="mentions" name="mentions" value="<?php echo htmlspecialchars($mentions, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Mentions">
					</div>
					<script>
					$('#mentions').tagsInput({
						autocomplete_url: 'api/mention_autocomplete.php',
						height: '47px',
						autocomplete: {
							selectFirst: true,
							width: '2100px',
							autoFill: true
						},
						defaultText: ''
					});
					</script>
					
					
					<div class="form-group">
						<input type="text" id="reportrange" name="date" class="form-control" placeholder="Date">
					</div>
					
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

					<div class="form-actions form-group ">
						<button type="submit" name="pdf_upload" class="full-width" value="submit">Search</button>
					</div>
					
					<div class="progress-bars" style="display: none;">
						<span id="progress-text-file">Uploading files</span>
						<div class="progress">
							<div id="progress-bar-file" class="progress-bar progress-bar-success progress-bar-striped active" style="background: #29b866; width:0%">

							</div>
						</div>
						
						<span id="progress-text-section">Creating sections</span>
						<div class="progress">
							<div id="progress-bar-section" class="progress-bar progress-bar-success progress-bar-striped active" style="background: #29b866; width:0%">
							
							</div>
						</div>
						
						<span id="progress-text-paragraph">Creating paragraphs</span>
						<div class="progress">
							<div id="progress-bar-paragraph" class="progress-bar progress-bar-success progress-bar-striped active" style="background: #29b866; width:0%">

							</div>
						</div>
					</div>
					
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