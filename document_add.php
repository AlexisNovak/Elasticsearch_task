<?php
require('lib/init.php');
requireLogin();

$notifyMsgUpload = '';

//echo ini_get('post_max_size');
//echo ini_get('max_file_uploads');

if (!empty($_POST['pdf_upload'])) {
	set_time_limit(100000);
	session_start();
	$sup_id = $_POST['sup_id'];
	session_write_close();
	$pdf_files = $mainClass->getFilesArray($_FILES['file']);
	
	if(sizeof($pdf_files) > 1)
		die('more than 1 pdf, critical error');
	
	if(empty($pdf_files[0]['name'])) {
		$notifyMsgUpload = $mainClass->alert('error', 'Please select a file.');
	} else {
		$allowed_mime_types = array('text/pdf', 'application/pdf', 'text/html');
		$upload_report = array('names_success' => array(), 'names_fail' => array(), 'names_already' => array());
		session_start();
		$_SESSION['upload_status'][$sup_id] = [
			'fileNameCurrent' => '',
			'fileCountTotal' => sizeof($pdf_files),
			'fileCountCurrent' => 0,
			'sectionCountTotal' => 0,
			'sectionCountCurrent' => 0,
			'paragraphCountTotal' => 0,
			'paragraphCountCurrent' => 0
		];
		session_write_close();
		foreach ($pdf_files as $pdf_file) {
			session_start();
			$_SESSION['upload_status'][$sup_id]['sectionCountTotal'] = 0;
			$_SESSION['upload_status'][$sup_id]['sectionCountCurrent'] = 0;
			$_SESSION['upload_status'][$sup_id]['paragraphCountTotal'] = 0;
			$_SESSION['upload_status'][$sup_id]['paragraphCountCurrent'] = 0;
			session_write_close();
			if (in_array($pdf_file['type'], $allowed_mime_types)) {
				$file_info = new SplFileInfo($pdf_file['name']);
				$file_extension = $file_info->getExtension();

				if($file_extension != 'pdf' && $file_extension != 'html') {
					array_push($upload_report['names_fail'], $pdf_file['name']);
				}
				
				$pdf_file['new_name'] = $file_extension . '_' . date('Y-m-d-H-i-s') . '_' . uniqid() . '.' . $file_extension;
				if(move_uploaded_file($pdf_file["tmp_name"], "documents/{$pdf_file['new_name']}")) {
					session_start();
					$_SESSION['upload_status'][$sup_id]['fileCountCurrent'] = $_SESSION['upload_status'][$sup_id]['fileCountCurrent'] + 1;
					$_SESSION['upload_status'][$sup_id]['fileNameCurrent'] = $pdf_file['name'];
					session_write_close();
					
					$tags = explode(',', $_POST['tags']);
					$tags = array_unique($tags);
					
					$mentions = explode(',', $_POST['mentions']);
					$mentions = array_unique($mentions);
					
					$add_pdf = $documentClass->addDocument($pdf_file['name'], $pdf_file['new_name'], $userDetails->id, $tags, $mentions, $sup_id);
					//$add_pdf = false;
					
					if($add_pdf === true)
						array_push($upload_report['names_success'], $pdf_file['name']);
					else
						array_push($upload_report['names_already'], $pdf_file['name']);
						
				} else {
					array_push($upload_report['names_fail'], $pdf_file['name']);
				}
			} else {
				array_push($upload_report['names_fail'], $pdf_file['name']);
			}
		}
		
		$notifyMsgUpload = sizeof($upload_report['names_fail']) > 0 ? $mainClass->alert('error', 'Failed uploads: ' . implode(', ', $upload_report['names_fail'])) : '';
		$notifyMsgUpload .= sizeof($upload_report['names_already']) > 0 ? $mainClass->alert('warning', 'Already uploaded: ' . implode(', ', $upload_report['names_already'])) : '';
		$notifyMsgUpload .= sizeof($upload_report['names_success']) > 0 ? $mainClass->alert('success', 'Uploaded: ' . implode(', ', $upload_report['names_success'])) : '';
	}
}

include('templates/default/header.php');
?>
			<div class="login-form">
				<?php echo $notifyMsgUpload; ?>
				<form method="post" enctype="multipart/form-data" id="upload-form">
				<input type="hidden" name="sup_id" value="<?php echo date('Y-m-d-H-i-s') . '_' . uniqid(); ?>">
				<div class="h1 text-blue">Upload Document</div>		
				
				
					<?php $errorMsgTags = ''; ?>
					<?php $tags = ''; ?>
					<label>Tags</label>
					<div class="form-group<?php if($errorMsgTags != '') echo ' has-error'; ?>">
						<?php if($errorMsgTags != '') echo '<span class="help-block with-errors"><ul class="list-unstyled"><li>' . $errorMsgTags . '</li></ul></span>'; ?>
						<input type="text" id="tags" name="tags" value="<?php echo htmlspecialchars($tags, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Tags">
					</div>
					<script>
					$('#tags').tagsInput({defaultText: ''});
					</script>
					
					<?php $errorMsgMentions = ''; ?>
					<?php $mentions = ''; ?>
					<label>Mention people</label>
					<div class="form-group<?php if($errorMsgMentions != '') echo ' has-error'; ?>">
						<?php if($errorMsgMentions != '') echo '<span class="help-block with-errors"><ul class="list-unstyled"><li>' . $errorMsgMentions . '</li></ul></span>'; ?>
						<input type="text" id="mentions" name="mentions" value="<?php echo htmlspecialchars($mentions, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Mentions">
					</div>
					<script>
					$('#mentions').tagsInput({
						autocomplete_url: 'api/mention_autocomplete.php',	
						autocomplete: {
							selectFirst: true,
							width: '2100px',
							autoFill: true
						},
						defaultText: ''
					});
					</script>
					
					<div class="form-group input-group">
						<label class="input-group-btn">
							<span class="btn btn-primary">
								Browse&hellip; <input type="file" name="file[]" style="display: none;">
							</span>
						</label>
						<input type="text" class="form-control" readonly>
					</div>

					<div class="form-actions form-group ">
						<button type="submit" name="pdf_upload" class="full-width" value="submit">Upload</button>
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