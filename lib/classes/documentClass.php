<?php
class documentClass {
	public function __construct() {
		include ROOT_DIR . '/lib/classes/simpleHtmlDom.php';
		include ROOT_DIR . '/pdfparser/vendor/autoload.php';
	}
	
	public function getStates() {
		try {
			$db = getDB();
			$stmt = $db->prepare("SELECT id, name, short_name FROM states");
				
			$stmt->execute();
			$data = $stmt->fetchAll(); //User data
			return $data;

		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	public $statusArray = array('Disabled', 'For Review', 'Active');
  
	public function getStatus() {
		return $this->statusArray;
	}
	
	/* Add parsed data to sections_by_pdf */
	private function addDocumentDataToSql($document_id, $type, $paragraph_num, $paragraph_title, $paragraph_text, $paragraph_html, $page_num) {
		try {
			//type 1 - chapter, 2 - section, 3 - subsection
			
			if($paragraph_num != null)
				$paragraph_num = preg_replace('/\s+/', ' ', $paragraph_num);
			$db = getDB();
			$stmt = $db->prepare("INSERT INTO sections_by_document (document_id, type, paragraph_num, paragraph_title, paragraph_text, paragraph_html, page_num) VALUES (:document_id, :type, :paragraph_num, :paragraph_title, :paragraph_text, :paragraph_html, :page_num)");
			$stmt->bindParam("document_id", $document_id, PDO::PARAM_INT);
			$stmt->bindParam("type", $type, PDO::PARAM_INT);
			$stmt->bindParam("paragraph_num", $paragraph_num, PDO::PARAM_STR);
			$stmt->bindParam("paragraph_title", $paragraph_title, PDO::PARAM_STR);
			$stmt->bindParam("paragraph_text", $paragraph_text, PDO::PARAM_STR);
			$stmt->bindParam("paragraph_html", $paragraph_html, PDO::PARAM_STR);
			$stmt->bindParam("page_num", $page_num, PDO::PARAM_INT);
			$stmt->execute();
			$db = null;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	/* Add a PDF */
	public function addDocument($file_name_original, $file_name, $user_id, $tags, $mentions, $sup_id = '') {
		try {
			$file_hash = hash_file('sha256', ROOT_DIR . '/documents/' . $file_name);
			
			$db = getDB();
						
			$st = $db->prepare("SELECT id FROM documents WHERE file_hash=:file_hash");
			$st->bindParam("file_hash", $file_hash, PDO::PARAM_STR);
			$st->execute();
			$count = $st->rowCount();
			if ($count < 1) {
				$file_info = new SplFileInfo($file_name);
				$file_extension = $file_info->getExtension();
				
				$document_text = null;
				$document_html = null;
				
				if($file_extension == 'pdf') {
					$file_path = dirname(dirname((__DIR__))) . DIRECTORY_SEPARATOR  . 'documents' . DIRECTORY_SEPARATOR . basename($file_name, '.pdf');
					if (DIRECTORY_SEPARATOR == '\\') {
						$unlock_pdf = exec('"C:\Program Files\gs\gs9.20\bin\gswin64c" -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=' . ($file_path . '_unlocked.pdf') . ' -c .setpdfwrite -f ' . ($file_path . '.pdf'));
					} else {
						$unlock_pdf = exec('gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=' . ($file_path . '_unlocked.pdf') . ' -c .setpdfwrite -f ' . ($file_path . '.pdf'));
					}
				

					//include "pdfparser/vendor/autoload.php";
					$parser = new \Smalot\PdfParser\Parser();
					$pdf = $parser->parseFile(($file_path . '_unlocked.pdf'));
					
					
					$document_text = '';
					$page_end_index = array(0 => 0);
					$pdf_pages  = $pdf->getPages();

					foreach ($pdf_pages as $pdf_page) {
						$page_parsed = $pdf_page->getText();
						$document_text .= $page_parsed;
						$page_end_index[sizeof($page_end_index)] = $page_end_index[sizeof($page_end_index) - 1] + strlen($page_parsed);
					}
					$document_html = nl2br($document_text);
				} else if($file_extension == 'html') {
					//include 'lib/classes/simpleHtmlDom.php';
					$page_end_index = null;
					$file_path = dirname(dirname((__DIR__))) . DIRECTORY_SEPARATOR  . 'documents' . DIRECTORY_SEPARATOR . basename($file_name);
					//$document_text = file_get_contents($file_path);
					//$paragraph_no_html = strip_tags(str_ireplace(["<br />","<br>","<br/>"], "\r\n", $document_text));
					$document_file = file_get_html($file_path)->find('.print-section', 0);
					$document_text = $document_file->innertext;
					//die($document_text);
					$paragraph_no_html = $document_file->plaintext;				
				} else {
					die("INVALID FILE");
				}
													
				
				$db = getDB();
				$stmt = $db->prepare("INSERT INTO documents (file_name_original, file_name, file_hash, user_id, date_creation, document_text, document_html) VALUES (:file_name_original, :file_name, :file_hash, :user_id, :date_creation, :document_text, :document_html)");
				$stmt->bindParam("file_name_original", $file_name_original, PDO::PARAM_STR);
				$stmt->bindParam("file_name", $file_name, PDO::PARAM_STR);
				$stmt->bindParam("file_hash", $file_hash, PDO::PARAM_STR);
				$stmt->bindParam("user_id", $user_id, PDO::PARAM_INT);
				$dateNow = date('Y-m-d H:i:s');
				$stmt->bindParam("date_creation", $dateNow, PDO::PARAM_STR);
				if($file_extension == 'pdf') {
					$stmt->bindParam("document_text", $document_text, PDO::PARAM_STR);
					$stmt->bindParam("document_html", $document_html, PDO::PARAM_STR);
				} else if($file_extension == 'html') {
					$stmt->bindParam("document_text", $paragraph_no_html, PDO::PARAM_STR);
					$stmt->bindParam("document_html", $document_text, PDO::PARAM_STR);
				}
				$stmt->execute();
				
				$did = $db->lastInsertId('documents_id_seq');
				
				foreach($tags as $tag) {
					$tag = strtolower($tag);
					$tag_id = -1;
					
					$db = getDB();
					$st = $db->prepare("SELECT id, name FROM tags WHERE lower(name)=:tag LIMIT 1");
					$st->bindParam("tag", $tag, PDO::PARAM_STR);
					$st->execute();
					$data = $st->fetch(PDO::FETCH_OBJ);
					
					if ($data) {
						$tag_id = $data->id;
					} else {
						$db = getDB();
						$stmt = $db->prepare("INSERT INTO tags (name) VALUES (:tag)");
						$stmt->bindParam("tag", $tag, PDO::PARAM_STR);
						$stmt->execute();
						$tag_id = $db->lastInsertId('tags_id_seq');
					}
					
					$db = getDB();
					$stmt = $db->prepare("INSERT INTO document_tags (document_id, tag_id) VALUES (:document_id, :tag_id)");
					$stmt->bindParam("document_id", $did, PDO::PARAM_INT);
					$stmt->bindParam("tag_id", $tag_id, PDO::PARAM_INT);
					$stmt->execute();
				}
				
				foreach($mentions as $mention) {
					$mention = strtolower($mention);
					
					$db = getDB();
					$st = $db->prepare("SELECT id FROM users WHERE lower(username)=:mention LIMIT 1");
					$st->bindParam("mention", $mention, PDO::PARAM_STR);
					$st->execute();
					$data = $st->fetch(PDO::FETCH_OBJ);
					if ($data) {
						$stmt = $db->prepare("INSERT INTO document_mentions (document_id, user_id) VALUES (:document_id, :user_id)");
						$stmt->bindParam("document_id", $did, PDO::PARAM_INT);
						$stmt->bindParam("user_id", $data->id, PDO::PARAM_STR);
						$stmt->execute();
					}
				}
				
				

				//addDocumentDataToSql($document_id, $type, $paragraph_num, $paragraph_title, $paragraph_text, $paragraph_html, $page_num)
				
				
				if($file_extension == 'pdf')
					preg_match_all('/^(CHAPTER\s[A-Z0-9]+)\s+([A-Z0-9 ]+)$/ms', $document_text, $chapters_titles, PREG_SET_ORDER);
				else if($file_extension == 'html')
					preg_match_all('/<center><b>(CHAPTER)\s([0-9]+)\s(.+)<\/b><\/center>/ms', $document_text, $chapters_titles, PREG_SET_ORDER);
				
				foreach($chapters_titles as $chapter_title)
					if($file_extension == 'pdf')
						$this->addDocumentDataToSql($did, 1, null, $chapter_title[0], null, null, $this->getPageNumber($document_text, $chapter_title[0], $page_end_index));
					else if($file_extension == 'html')
						$this->addDocumentDataToSql($did, 1, $chapter_title[2], $chapter_title[3], null, null, $this->getPageNumber($document_text, $chapter_title[0], $page_end_index));
			
				
				
				
				if($file_extension == 'pdf')
					preg_match_all('/(SECTION)\s([0-9A-Z]+)\s?([\sA-Z0-9,-\[\]\&]+)\n/m', $document_text, $sections, PREG_SET_ORDER);
				else if($file_extension == 'html')
					preg_match_all('/(SECTION)\s([0-9]+)\s?(?:<b>)?([\s\(\)A-Z0-9\,\-\[\s\]\&]+)(?:<\/b>)?/m', $document_text, $sections, PREG_SET_ORDER);
				
				session_start();
				$_SESSION['upload_status'][$sup_id]['sectionCountTotal'] = sizeof($sections);
				$_SESSION['upload_status'][$sup_id]['sectionCountCurrent'] = 0;
				$_SESSION['upload_status'][$sup_id]['paragraphCountTotal'] = 0;
				$_SESSION['upload_status'][$sup_id]['paragraphCountCurrent'] = 0;
				session_write_close();
					
				foreach($sections as $section) {
					session_start();
					$_SESSION['upload_status'][$sup_id]['sectionCountCurrent'] = $_SESSION['upload_status'][$sup_id]['sectionCountCurrent'] + 1;
					$_SESSION['upload_status'][$sup_id]['paragraphCountTotal'] = 0;
					$_SESSION['upload_status'][$sup_id]['paragraphCountCurrent'] = 0;
					session_write_close();
					$this->addDocumentDataToSql($did, 2, $section[2], $section[3], null, null, $this->getPageNumber($document_text, $section[0], $page_end_index));
					$section_num = preg_replace("/[^A-Za-z0-9]/", '', $section[2]);
					
					if($file_extension == 'pdf')
						$subsection_pattern = '/(?<subsection>' . $section_num . '[0-9A-Z^\.]+(?<!\.))\s(?<title>[A-Za-z0-9\,\-\s\“\”\(\)]+?\.)(?<paragraph>.+?)(?=(\g<subsection>\s\g<title>)|SECTION|\Z)/ms';
					else if($file_extension == 'html')
						$subsection_pattern = '/(?<subsection><b>' . $section_num . '[0-9A-Z^\.]+(?<!\.))\s(?<title>[A-Za-z0-9\,\-\s\“\”\(\)]+?\.)(?<paragraph>.+?)(?=(\g<subsection>\s\g<title>)|SECTION|\Z)/ms';

					preg_match_all($subsection_pattern, $document_text, $sub_sections, PREG_SET_ORDER);
					session_start();
					$_SESSION['upload_status'][$sup_id]['paragraphCountTotal'] = sizeof($sub_sections);
					$_SESSION['upload_status'][$sup_id]['paragraphCountCurrent'] = 0;
					session_write_close();
					foreach($sub_sections as $sub_section) {
						session_start();
						$_SESSION['upload_status'][$sup_id]['paragraphCountCurrent'] = $_SESSION['upload_status'][$sup_id]['paragraphCountCurrent'] + 1;
						session_write_close();
						$paragraph_no_html = strip_tags(str_ireplace(["<br />","<br>","<br/>"], "\r\n", $sub_section['paragraph']));  
						$this->addDocumentDataToSql(
							$did, 
							3, 
							strip_tags($sub_section['subsection']), 
							strip_tags($sub_section['title']), 
							$paragraph_no_html, //only text
							$sub_section['paragraph'],  //text with html
							$this->getPageNumber($document_text, $sub_section[0], $page_end_index)
						);
					}
				}
				return true;
			} else {
				return false;
			}
			$db = null;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	/* Get Page Numbers */
	public function getPageNumber($pdf_parsed, $sub_section, $page_end_index) {
		if($page_end_index == null)
			return null;
		$sub_section_index = strrpos($pdf_parsed, $sub_section);
		for($i = 1; $i < sizeof($page_end_index); $i++) {
			if($sub_section_index < $page_end_index[$i]) {
				return $i;
			}
		}
		return 0;
	}
	
	
	/* Find PDFs */
	public function findDocuments($state_id, $user_id, $pagination = null) {
		try {
			$db = getDB();
			$whereArray = array();
			$whereString = '';
			
			if($state_id != 0)
				array_push($whereArray, 'state_id=:state_id');
			//if($user_id != '')
				//array_push($whereArray, 'user_id=:user_id');
			
			if(count($whereArray) > 0) {
				$whereString = ' WHERE ' . implode(' AND ', $whereArray);
			}
				
			$stmt = $db->prepare("SELECT id, file_name_original, file_name, user_id, date_creation, document_text, document_html, status FROM documents" . $whereString . ' ORDER BY id LIMIT :limit OFFSET :offset');
			$stmt->bindParam(':limit', $pagination['limit'], PDO::PARAM_INT);
			$stmt->bindParam(':offset', $pagination['offset'], PDO::PARAM_INT);
			
			if($state_id != 0)
				$stmt->bindParam("state_id", $state_id, PDO::PARAM_INT);
			//if($user_id != '')
				//$stmt->bindParam("user_id", $user_id, PDO::PARAM_INT);
				
			$stmt->execute();
			$data = $stmt->fetchAll();
			return $data;

		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	/* Find a PDF */
	public function findDocument($id, $user_id) {
		try {
			$db = getDB();
			//if($user_id > 0)
			//	$stmt = $db->prepare("SELECT id, file_name_original, file_name, user_id, date_creation, document_text, document_html, status FROM documents WHERE id=:id AND user_id=:user_id");
			//else
				$stmt = $db->prepare("SELECT id, file_name_original, file_name, user_id, date_creation, document_text, document_html, status FROM documents WHERE id=:id");
			$stmt->bindParam("id", $id, PDO::PARAM_INT);
			//if($user_id > 0)
			//$stmt->bindParam("user_id", $user_id, PDO::PARAM_INT);
			$stmt->execute();
			$data = $stmt->fetch(PDO::FETCH_OBJ); //User data
			return $data;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
}
?>