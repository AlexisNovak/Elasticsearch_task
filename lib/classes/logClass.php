<?php
class logClass {
	public function logAction($uid, $action_type, $param1 = '', $param2 = '', $param3 = '', $param4 = '', $param5 = '', $param6 = '') {
		try {
			//action types: 1 - login, 2 - search, 3 - click section, 4 - session ping
			
			$db = getDB();			
			
			if($action_type == 1) {
				$stmt = $db->prepare("INSERT INTO user_log(user_id, ip, session_id, browser, time, action_type, duration, duration_last_update) VALUES (:user_id, :ip, :session_id, :browser, CURRENT_TIMESTAMP, :action_type, 1, CURRENT_TIMESTAMP)");
			} else if($action_type == 2) {
				$stmt = $db->prepare("INSERT INTO user_log(user_id, ip, session_id, browser, time, action_type, param1, param2) VALUES (:user_id, :ip, :session_id, :browser, CURRENT_TIMESTAMP, :action_type, :param1, :param2)");
				$stmt->bindParam("param1", $param1, PDO::PARAM_STR);
				$stmt->bindParam("param2", $param2, PDO::PARAM_STR);
			} else if($action_type == 3) {
				$stmt = $db->prepare("INSERT INTO user_log(user_id, ip, session_id, browser, time, action_type, param1, param2, param3) VALUES (:user_id, :ip, :session_id, :browser, CURRENT_TIMESTAMP, :action_type, :param1, :param2, :param3)");
				$stmt->bindParam("param1", $param1, PDO::PARAM_STR);
				$stmt->bindParam("param2", $param2, PDO::PARAM_STR);
				$stmt->bindParam("param3", $param3, PDO::PARAM_STR);
			} else if($action_type == 4) {
				$stmt = $db->prepare("SELECT id FROM user_log WHERE user_id=:user_id AND session_id=:session_id AND action_type=1 ORDER BY id DESC LIMIT 1");
				$stmt->bindParam("user_id", $uid, PDO::PARAM_INT);
				$session_id = session_id();
				$stmt->bindParam("session_id", $session_id, PDO::PARAM_STR);
				$stmt->execute();
				$data = $stmt->fetch(PDO::FETCH_OBJ);
				//print_r($data);
				if($data) {
					$stmt = $db->prepare("UPDATE user_log SET duration=duration+1, duration_last_update=CURRENT_TIMESTAMP WHERE id=:log_id AND duration_last_update < NOW() - INTERVAL '1' minute");
					$stmt->bindParam("log_id", $data->id, PDO::PARAM_INT);
					$stmt->execute();
				}
				$db = null;
				return true;
			}
						
			require_once ROOT_DIR . '/lib/classes/BrowserDetection.php';
			$browser = new BrowserDetection();
			
			$session_id = session_id();
			$ip = isset($_SERVER["HTTP_CF_CONNECTING_IP"]) ? $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"] : $_SERVER['REMOTE_ADDR'];
			$browser_name = $browser->getName() . ' ' . $browser->getVersion();
			$time = time();
			
			$stmt->bindParam("user_id", $uid, PDO::PARAM_INT);
			$stmt->bindParam("session_id", $session_id, PDO::PARAM_STR);
			$stmt->bindParam("ip", $ip, PDO::PARAM_STR);
			$stmt->bindParam("browser", $browser_name, PDO::PARAM_STR);
			$stmt->bindParam("action_type", $action_type, PDO::PARAM_STR);
			
			$stmt->execute();
			$db = null;
			return true;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	public function findLogs($pagination = null) {
		try {
			$db = getDB();
			
			//print_r($pagination);
			

			/*$conditions_string = implode(' AND ', array_map(function ($entry) {
				return $entry[0] . ' ' . $entry[1] . ' :' . $entry[0];
			}, $pagination['conditions']));
			echo '<br>';
			echo $conditions_string;*/
			
			$conditions_array = [];
			for($i = 0; $i < sizeof($pagination['conditions']); $i++) {
				$condition_string = $pagination['conditions'][$i][0] . ' ' . $pagination['conditions'][$i][1] . ' :' . $pagination['conditions'][$i][0] . '_' . $i;
				array_push($conditions_array, $condition_string);
			}
			$conditions_string = implode(' AND ', $conditions_array);
			//echo '<br>';
			//echo $conditions_string;
			
			if(sizeof($pagination['conditions']) == 0) {
				$stmt = $db->prepare("SELECT * FROM " . $pagination['table_name'] . ' ORDER BY id DESC LIMIT :limit OFFSET :offset');
			} else {
				$stmt = $db->prepare("SELECT * FROM " . $pagination['table_name'] . ' WHERE ' . $conditions_string . ' ORDER BY id DESC LIMIT :limit OFFSET :offset');
				$cond_i = 0;
				foreach($pagination['conditions'] as $condition)
					$stmt->bindParam(':' . $condition[0] . '_' . $cond_i++, $condition[2], $condition[3]);
			}
			$stmt->bindParam('limit', $pagination['limit'], PDO::PARAM_INT);
			$stmt->bindParam('offset', $pagination['offset'], PDO::PARAM_INT);
			
				
			$stmt->execute();
			$data = $stmt->fetchAll(PDO::FETCH_OBJ);
			return $data;

		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	public function findQuestions($keyword) {
		try {
			$db = getDB();
			$stmt = $db->prepare("SELECT DISTINCT param1 FROM user_log WHERE action_type=2 AND LOWER(param1) LIKE :param1");
			$keyword = '%' . $keyword . '%';
			$stmt->bindParam('param1', $keyword, PDO::PARAM_INT);
			$stmt->execute();
			$data = $stmt->fetchAll(PDO::FETCH_OBJ);
			return $data;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
}
?>