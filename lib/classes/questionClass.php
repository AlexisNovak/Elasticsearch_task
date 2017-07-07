<?php
class questionClass {
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
	
	/* Add a question */
	public function addQuestion($user_id, $state_id, $status, $question, $paragraph_num) {
		try {
			$db = getDB();
			$question = trim($question);
			if(substr($question, -1) != '?')
				$question .= '?';
			
			$st = $db->prepare("SELECT id FROM questions WHERE LOWER(question)=:question AND state_id=:state_id");
			$question_lower =  strtolower($question);
			$st->bindParam("question", $question_lower, PDO::PARAM_STR);
			$st->bindParam("state_id", $state_id, PDO::PARAM_INT);
			$st->execute();
			$count = $st->rowCount();
			if($count == 0) {
				$stmt = $db->prepare("INSERT INTO questions (user_id, state_id, status, question, paragraph_num) VALUES (:user_id, :state_id, :status, :question, :paragraph_num)");
				$stmt->bindParam("user_id", $user_id, PDO::PARAM_INT);
				$stmt->bindParam("state_id", $state_id, PDO::PARAM_INT);
				$stmt->bindParam("status", $status, PDO::PARAM_INT);
				$stmt->bindParam("question", $question, PDO::PARAM_STR);
				$stmt->bindParam("paragraph_num", $paragraph_num, PDO::PARAM_STR);
				$stmt->execute();
				$qid = $db->lastInsertId('questions_id_seq');
			} else {
				$qid = -1;
			}
			$db = null;
			return $qid;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	/* Edit a question */
	public function editQuestion($id, $question, $paragraph_num, $user_id, $state_id, $status) {
		try {
			$db = getDB();
			//if($user_id > 0)
				//$stmt = $db->prepare("UPDATE questions SET question=:question, paragraph_num=:paragraph_num, state_id=:state_id, status=:status WHERE id=:id AND user_id=:user_id");
			//else
				$stmt = $db->prepare("UPDATE questions SET question=:question, paragraph_num=:paragraph_num, state_id=:state_id, status=:status WHERE id=:id");
			
			$stmt->bindParam("question", $question, PDO::PARAM_STR);
			$stmt->bindParam("paragraph_num", $paragraph_num, PDO::PARAM_STR);
			$stmt->bindParam("state_id", $state_id, PDO::PARAM_INT);
			$stmt->bindParam("status", $status, PDO::PARAM_INT);		
			$stmt->bindParam("id", $id, PDO::PARAM_INT);
			//if($user_id > 0)
				//$stmt->bindParam("user_id", $user_id, PDO::PARAM_INT);
			
			$stmt->execute();
			$db = null;
			return true;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	/* Delete a question*/
	public function deleteQuestion($id, $user_id) {
		try {
			$db = getDB();
			//if($user_id > 0)
				//$stmt = $db->prepare("DELETE FROM questions WHERE id=:id AND user_id=:user_id");
			//else
				$stmt = $db->prepare("DELETE FROM questions WHERE id=:id");
			
			$stmt->bindParam("id", $id, PDO::PARAM_INT);
			//if($user_id > 0)
				//$stmt->bindParam("user_id", $user_id, PDO::PARAM_INT);
			$stmt->execute();
			return true;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	/* Count questions by state */
	public function countQuestionsByState($conditions = []) {
		try {
			$db = getDB();
			$conditions_string = implode(' AND ', array_map(function ($entry) {
				return $entry[0] . ' ' . $entry[1] . ' :' . $entry[0];
			}, $conditions));
					
			if(sizeof($conditions) == 0) {
				$stmt = $db->prepare("SELECT state_id, COUNT(*) FROM questions GROUP BY state_id");
			} else {
				$stmt = $db->prepare("SELECT state_id, COUNT(*) FROM questions WHERE " . $conditions_string . " GROUP BY state_id");
				foreach($conditions as $condition)
					$stmt->bindParam(':' . $condition[0], $condition[2], $condition[3]);
			}
			$stmt->execute();
			$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

			return $data;

		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}

	/* Find questions */
	public function findQuestionsLinked($keyword, $state_id, $user_id, $pagination = null) {
		try {
			$db = getDB();
			$whereArray = array();
			$whereString = '';
			
			if($keyword != '')
				array_push($whereArray, 'question=:keyword');
			if($state_id != 0)
				array_push($whereArray, 'state_id=:state_id');
			//if($user_id != '')
				//array_push($whereArray, 'user_id=:user_id');
			
			array_push($whereArray, 'link_id>0');
			
			if(count($whereArray) > 0) {
				$whereString = ' WHERE ' . implode(' AND ', $whereArray);
			}
			
			$stmt = $db->prepare("SELECT id, question, answer, section_name, link_id, user_id, state_id, status FROM questions" . $whereString . ' ORDER BY id LIMIT :limit OFFSET :offset');
			$stmt->bindParam(':limit', $pagination['limit'], PDO::PARAM_INT);
			$stmt->bindParam(':offset', $pagination['offset'], PDO::PARAM_INT);
			
			if($keyword != '')
				$stmt->bindParam("keyword", $keyword, PDO::PARAM_STR);
			if($state_id != 0)
				$stmt->bindParam("state_id", $state_id, PDO::PARAM_INT);
			//if($user_id != '')
				//$stmt->bindParam("user_id", $user_id, PDO::PARAM_INT);
				
			$stmt->execute();
			$count = $stmt->rowCount();
			
			if($count > 0) {
				$data = $stmt->fetchAll(); //User data
				
				
				$questions_array = array();

				
				//1) how to make french fries  A1222.12   ->>> OUR MATCH IS: section A1222.12 “POTATO AND ALL ITS GLORY” FROM file  “POTATOISLIFE.PDF”
				
				foreach($data as $question) {
					$db = getDB();
					$st = $db->prepare("SELECT id, document_id, type, paragraph_text, page_num FROM sections_by_document WHERE id=:id");
					$st->bindParam("id", $question['link_id'], PDO::PARAM_INT);
					$st->execute();
					$count = $st->rowCount();
					if($count > 0) {
						$data2 = $st->fetch(PDO::FETCH_OBJ);
						
						$db = getDB();
						$st3 = $db->prepare("SELECT file_name_original, file_name FROM documents WHERE id=:id");
						$st3->bindParam("id", $data2->document_id, PDO::PARAM_INT);
						$st3->execute();
						$data3 = $st3->fetch(PDO::FETCH_OBJ);
						
						$question_string = '<i>' . $question['question'] . ' ' . $question['section_name'] . '</i><br />';
						$question_string .= 'Subsection ' . $data2->paragraph_text . '<br />';
						if($data2->page_num)
							$question_string .= '<b>Page ' . $data2->page_num . ',</b> ';
						//$question_string .= 'File <a href="' . BASE_URL . 'pdfs/' . $data3->file_name . '">' . $data3->file_name_original . '</a>';
						$question_string .= '<b>File </b><a href="#" onClick="window.open(\'' . BASE_URL . 'documents/' . $data3->file_name . '\', \'_blank\', \'width=700, height=1000\')">' . $data3->file_name_original . '</a>';
						//<a href="#" onClick="MyWindow=window.open('http://www.google.com','MyWindow',width=600,height=300); return false;">Click Here</a>

						array_push($questions_array, $question_string);
					}
				}
				
				return $questions_array;
			} else {
				return array();
			}

		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	//user_id, state_id, status, question, paragraph_num
	/* Find questions */
	public function findQuestions($keyword, $state_id, $user_id, $pagination = null) {
		try {
			$db = getDB();
			$whereArray = array();
			$whereString = '';
			
			if($keyword != '')
				array_push($whereArray, 'question=:keyword');
			if($state_id != 0)
				array_push($whereArray, 'state_id=:state_id');
			//if($user_id != '')
				//array_push($whereArray, 'user_id=:user_id');
			
			if(count($whereArray) > 0) {
				$whereString = ' WHERE ' . implode(' AND ', $whereArray);
			}
				
			$stmt = $db->prepare("SELECT id, question, paragraph_num, user_id, state_id, status FROM questions" . $whereString . ' ORDER BY id LIMIT :limit OFFSET :offset');
			$stmt->bindParam(':limit', $pagination['limit'], PDO::PARAM_INT);
			$stmt->bindParam(':offset', $pagination['offset'], PDO::PARAM_INT);
	
			if($keyword != '')
				$stmt->bindParam("keyword", $keyword, PDO::PARAM_STR);
			if($state_id != 0)
				$stmt->bindParam("state_id", $state_id, PDO::PARAM_INT);
			//if($user_id != '')
				//$stmt->bindParam("user_id", $user_id, PDO::PARAM_INT);
				
			$stmt->execute();
			$data = $stmt->fetchAll(); //User data
			return $data;

		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}

	/* Find a question */
	public function findQuestion($id, $question) {
		try {
			$db = getDB();
			if($question == '') {
				$stmt = $db->prepare("SELECT id, question, paragraph_num, user_id, state_id, status FROM questions WHERE id=:id");
				$stmt->bindParam("id", $id, PDO::PARAM_INT);
			} else {
				$stmt = $db->prepare("SELECT id, question, paragraph_num, user_id, state_id, status FROM questions WHERE LOWER(question)=:question");
				$question_lower = strtolower($question);
				$stmt->bindParam("question", $question_lower, PDO::PARAM_STR);
			}
				
			$stmt->execute();
			$data = $stmt->fetch(PDO::FETCH_OBJ); //User data
			return $data;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
}
?>