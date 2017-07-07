<?php
set_include_path( '..' . DIRECTORY_SEPARATOR);
require('lib/init.php');

//echo '{"bookmark_status": 0}';

if(isLoggedIn()) {
	if(isset($_GET['section_id'])) {
		$bookmarked = $userClass->addBookmark($userDetails->id, $_GET['section_id']);
		$bookmark_status = $bookmarked ? 1 : 0;
		
		$bookmarks = $userClass->getBookmarks($userDetails->id);

		echo '{"bookmark_status": ' . $bookmark_status .', "bookmarks": ' . json_encode($bookmarks) . '}';
	} else {
		echo 'section_id not set';
	}
} else {
	echo 'not logged in';
}
?>