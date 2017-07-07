<?php
class userClass {
	/* User Login */
	public function userLogin($username, $password) {
		try {
			$db = getDB();
			$md5_password = md5($password); //Password encryption 
			$stmt = $db->prepare("SELECT id FROM users WHERE username=:username AND password=:md5_password");
			$stmt->bindParam("username", $username, PDO::PARAM_STR);
			$stmt->bindParam("md5_password", $md5_password, PDO::PARAM_STR);
			$stmt->execute();
			$count = $stmt->rowCount();
			$data = $stmt->fetch(PDO::FETCH_OBJ);
			$db = null;
			if ($count) {
				session_start();
				$_SESSION['uid'] = $data->id; // Storing user session value
				session_write_close();
				return $data->id;
			} else {
				return false;
			}
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}

	}

	/* User Registration */
	public function userRegistration($username, $password, $email, $first_name, $last_name) {
		try {
			//global $paymentClass, $mailClass;
			$db = getDB();
		
			$st = $db->prepare("SELECT id FROM users WHERE username=:username");
			$st->bindParam("username", $username, PDO::PARAM_STR);
			$st->execute();
			$count = $st->rowCount();
			
			if($count > 0) {
				$db = null;
				return 'USERNAME_ALREADY_EXISTS';
			}
			
			$st = $db->prepare("SELECT id FROM users WHERE email=:email");
			$st->bindParam("email", $email, PDO::PARAM_STR);
			$st->execute();
			$count = $st->rowCount();
			
			if($count > 0) {
				$db = null;
				return 'EMAIL_ALREADY_EXISTS';
			}
			
			$stmt = $db->prepare("INSERT INTO users(username, password, email, first_name, last_name) VALUES (:username, :md5_password, :email, :first_name, :last_name)");
			
			$stmt->bindParam("username", $username, PDO::PARAM_STR);
			$md5_password = md5($password); //Password encryption
			$stmt->bindParam("md5_password", $md5_password, PDO::PARAM_STR);
			$stmt->bindParam("email", $email, PDO::PARAM_STR);
			$stmt->bindParam("first_name", $first_name, PDO::PARAM_STR);
			$stmt->bindParam("last_name", $last_name, PDO::PARAM_STR);
			$stmt->execute();
			$uid = $db->lastInsertId('users_id_seq'); // Last inserted row id
		
			$db = null;
			$_SESSION['uid'] = $uid;
			
			/*$mail_subject = 'Welcome!';
			$mail_content =  'Hey ' . $email . ', welcome to GoFetchCode!';
			$mail_content_html = 'Hey ' . $email . ', welcome to GoFetchCode!';
			$mailClass->sendMail($email, $mail_subject, $mail_content, $mail_content_html);*/
			
			return $uid;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	public function requestPasswordReset($email) {
		try {
			global $mailClass;
			
			$db = getDB();
		
			$stmt = $db->prepare("SELECT id FROM users WHERE email=:email");
			$stmt->bindParam("email", $email, PDO::PARAM_STR);
			$stmt->execute();
			$data = $stmt->fetch(PDO::FETCH_OBJ);
			$count = $stmt->rowCount();

			//die('count: ' . $count);
			
			if($count == 0) {
				$db = null;
				return 'INVALID_EMAIL';
			}
			
			$length = 32;
			$reset_code = "";
			$characters = "0123456789abcdefghijklmnopqrstuvwxyz";
			for ($p = 0; $p < $length; $p++) {
				$reset_code .= $characters[mt_rand(0, strlen($characters) - 1)];
			}
			//"timestampRow" >= now();
			$stmt = $db->prepare("INSERT INTO user_password_reset(user_id, email, reset_code, expire) VALUES (:user_id, :email, :reset_code, current_timestamp + interval '1' day)");
			$stmt->bindParam("user_id", $data->id, PDO::PARAM_INT);
			$stmt->bindParam("email", $email, PDO::PARAM_STR);
			$stmt->bindParam("reset_code", $reset_code, PDO::PARAM_STR);
			$stmt->execute();
			
			$reset_link = BASE_URL . 'password_reset.php?reset_code=' . $reset_code . '&email=' . $email;
			$mail_subject = 'Reset your password';
			$mail_content =  'You have requested to reset your password, and you can do this through the following link:\r\n\r\n' . $reset_link;
			$mail_content_html = 'You have requested to reset your password, and you can do this through the following link:<br /><br /><a href="' . $reset_link . '">' . $reset_link . '</a>';
			$mailClass->sendMail($email, $mail_subject, $mail_content, $mail_content_html);
		
			$db = null;
			return true;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	public function getUserIdFromResetLink($reset_code, $email) {
		try {
			$db = getDB();
		
			$stmt = $db->prepare("SELECT * FROM user_password_reset WHERE email=:email AND reset_code=:reset_code AND expire > current_timestamp");
			$stmt->bindParam("email", $email, PDO::PARAM_STR);
			$stmt->bindParam("reset_code", $reset_code, PDO::PARAM_STR);
			$stmt->execute();
			$data = $stmt->fetch(PDO::FETCH_OBJ);
			$count = $stmt->rowCount();
			
			if($count == 0) {
				$db = null;
				return 'INVALID_RESET_LINK';
			}
			
			$db = null;
			return $data->user_id;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	public function resetPassword($uid, $password, $reset_code, $email) {
		try {
			$db = getDB();
				
			$stmt = $db->prepare("DELETE FROM user_password_reset WHERE email=:email AND reset_code=:reset_code");
			$stmt->bindParam("email", $email, PDO::PARAM_STR);
			$stmt->bindParam("reset_code", $reset_code, PDO::PARAM_STR);
			$stmt->execute();
			
			$this->changePassword($uid, $password);
			
			$db = null;
			return true;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	public function changePassword($uid, $password) {
		try {
			//global $mailClass, $userDetails;
			
			$db = getDB();
			$stmt = $db->prepare("UPDATE users SET password=:md5_password WHERE id=:uid");
			$stmt->bindParam("uid", $uid, PDO::PARAM_INT);
			$md5_password = md5($password); //Password encryption
			$stmt->bindParam("md5_password", $md5_password, PDO::PARAM_STR);
			$stmt->execute();
			$db = null;
			
			/*$mail_subject = 'Password changed';
			$mail_content = 'You recently changed your password.';
			$mail_content_html = 'You recently changed your password.';
			$mailClass->sendMail($userDetails->email, $mail_subject, $mail_content, $mail_content_html);*/
			
			return true;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	public function changeEmail($uid, $email) {
		try {
			$db = getDB();
			$stmt = $db->prepare("UPDATE users SET email=:email WHERE id=:uid");
			$stmt->bindParam("uid", $uid, PDO::PARAM_INT);
			$stmt->bindParam("email", $email, PDO::PARAM_STR);
			$stmt->execute();
			$db = null;
			return true;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	
	
	public function inviteUser($uid, $email) {
		try {
			global $mailClass, $userDetails;
			
			$length = 32;
			$invite_code = '';
			$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
			$invite_code_duplicate = true;
			
			$db = getDB();
			
			while($invite_code_duplicate === true) {
				$invite_code = '';
				for ($p = 0; $p < $length; $p++) {
					$invite_code .= $characters[mt_rand(0, strlen($characters) - 1)];
				}

				$st = $db->prepare("SELECT * FROM user_invites WHERE invite_code=:invite_code");
				$st->bindParam("invite_code", $invite_code, PDO::PARAM_STR);
				$st->execute();
				$invite_count = $st->rowCount();
				if($invite_count == 0)
					$invite_code_duplicate = false;
			}
			
			
			$stmt = $db->prepare("INSERT INTO user_invites(user_id, email, invite_code) VALUES (:uid, :email, :invite_code)");
			$stmt->bindParam("uid", $uid, PDO::PARAM_INT);
			$stmt->bindParam("email", $email, PDO::PARAM_STR);
			$stmt->bindParam("invite_code", $invite_code, PDO::PARAM_STR);
			$stmt->execute();
			$db = null;
			
			$invite_link = BASE_URL . 'register.php?invite_code=' . $invite_code;
			$mail_subject = 'You are invited';
			$mail_content =  $userDetails->email . ' has invited you to join his team on GoFetchCode. Click the following link to accept the invitation:\r\n\r\n' . $invite_link;
			$mail_content_html = $userDetails->email . ' has invited you to join his team on GoFetchCode. Click the following link to accept the invitation:<br /><br /><a href="' . $invite_link . '">' . $invite_link . '</a>';
			$mailClass->sendMail($email, $mail_subject, $mail_content, $mail_content_html);
		
			return true;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	public function inviteRevoke($uid, $invite_code) {
		try {
			$db = getDB();
			$stmt = $db->prepare("DELETE FROM user_invites WHERE user_id=:uid AND invite_code=:invite_code");
			$stmt->bindParam("uid", $uid, PDO::PARAM_INT);
			$stmt->bindParam("invite_code", $invite_code, PDO::PARAM_STR);
			$stmt->execute();
			$db = null;
			return true;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	public function invitedUserDelete($uid, $user_id) {
		try {
			$db = getDB();
			$stmt = $db->prepare("DELETE FROM users WHERE id=:user_id AND owner_id=:uid");
			$stmt->bindParam("uid", $uid, PDO::PARAM_INT);
			$stmt->bindParam("user_id", $user_id, PDO::PARAM_INT);
			$stmt->execute();
			$db = null;
			return true;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	public function getInviteInfo($invite_code) {
		try {
			$db = getDB();
			
			$stmt = $db->prepare("SELECT * FROM user_invites WHERE invite_code=:invite_code");
			$stmt->bindParam("invite_code", $invite_code, PDO::PARAM_STR);
			$stmt->execute();
			$data = $stmt->fetch(PDO::FETCH_OBJ);
			$db = null;
			return $data;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	public function getInvitesInfo($uid) {
		try {
			$db = getDB();
			
			$stmt = $db->prepare("SELECT extra_users FROM users WHERE id=:uid");
			$stmt->bindParam("uid", $uid, PDO::PARAM_INT);
			$stmt->execute();
			$data_user = $stmt->fetch(PDO::FETCH_OBJ);
			
			$st = $db->prepare("SELECT * FROM users WHERE owner_id=:uid");
			$st->bindParam("uid", $uid, PDO::PARAM_INT);
			$st->execute();
			$data_users = $st->fetchAll(PDO::FETCH_OBJ);
			$invited_users_count = $st->rowCount();
			
			$st2 = $db->prepare("SELECT * FROM user_invites WHERE user_id=:uid");
			$st2->bindParam("uid", $uid, PDO::PARAM_INT);
			$st2->execute();
			$data_invites = $st2->fetchAll(PDO::FETCH_OBJ);
			$pending_invites_count = $st2->rowCount();

			$invites_count = isSubscribed() ? ($data_user->extra_users - $invited_users_count - $pending_invites_count) : 0;
			$invites_info = (object) array('invites_count' => $invites_count, 'pending_invites' => $data_invites, 'users' => $data_users);
			$db = null;
			return $invites_info;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	public function addSubscriptionLocations($uid, $locations) {
		$db = getDB();
		foreach($locations as $location) {
			$stmt = $db->prepare("INSERT INTO user_subscription_locations(user_id, state_id) VALUES (:user_id, :state_id)");
			$stmt->bindParam("user_id", $uid, PDO::PARAM_INT);
			$stmt->bindParam("state_id", $location, PDO::PARAM_INT);
			$stmt->execute();
		}
	}
	
	public function getSubscriptionLocations($uid) {
		try {
			global $userDetails;

			$db = getDB();
			$stmt = $db->prepare("SELECT * FROM user_subscription_locations WHERE user_id=:user_id");
			
			if($userDetails->owner_id > -1)
				$stmt->bindParam("user_id", $userDetails->owner_id, PDO::PARAM_INT);
			else
				$stmt->bindParam("user_id", $uid, PDO::PARAM_INT);
			
			$stmt->execute();
			$data = $stmt->fetchAll(PDO::FETCH_OBJ); //User data
			$db = null;
			return $data;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}

	/* User Details */
	public function userDetails($uid) {
		try {
			$db = getDB();
			$stmt = $db->prepare("SELECT * FROM users WHERE id=:uid");
			$stmt->bindParam("uid", $uid, PDO::PARAM_INT);
			$stmt->execute();
			$data = $stmt->fetch(PDO::FETCH_OBJ); //User data
			$db = null;
			return $data;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	public function userDetailsByUsername($username) {
		try {
			$db = getDB();
			$stmt = $db->prepare("SELECT * FROM users WHERE username=:username");
			$stmt->bindParam("username", $username, PDO::PARAM_STR);
			$stmt->execute();
			$data = $stmt->fetch(PDO::FETCH_OBJ); //User data
			$db = null;
			return $data;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	public function pageRequireSubscription($uid) {
		if(!$this->isSubscribed($uid)) {
			$url = BASE_URL . 'payment.php';
			header("Location: $url");
			exit();
		}
	}
	
	/* Have Active Paid Subscription */
	public function isSubscribed($uid) {
		$dateNow = date('Y-m-d H:i:s');
		$timeStampNow = strtotime($dateNow);
		
		$user_details = $this->userDetails($uid);
				
		if($user_details->owner_id > -1)
			$timeStampUntil = strtotime($this->userDetails($user_details->owner_id)->co_subscribed_until);
		else
			$timeStampUntil = strtotime($user_details->co_subscribed_until);

		return $timeStampUntil > $timeStampNow;
	}
	
	public function extendSubscription($uid) {
		try {
			$timeStampUntilNew = date("Y-m-d", strtotime("+1 month", time()));
			if($this->isSubscribed($uid)) {
				$timeStampUntil = strtotime($this->userDetails($uid)->co_subscribed_until);
				$timeStampUntilNew = date("Y-m-d", strtotime("+1 month", $timeStampUntil));
			}

			$db = getDB();
			$stmt = $db->prepare("UPDATE users SET co_subscribed_until=:co_subscribed_until WHERE id=:uid");
			$stmt->bindParam("uid", $uid, PDO::PARAM_INT);
			$stmt->bindParam("co_subscribed_until", $timeStampUntilNew, PDO::PARAM_STR);
			$stmt->execute();
			$db = null;
			return true;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	public function addBookmark($uid, $section_id) {
		try {
			$db = getDB();
			
			$stmt = $db->prepare("SELECT id FROM user_bookmarks WHERE user_id=:user_id AND section_id=:section_id");
			$stmt->bindParam("user_id", $uid, PDO::PARAM_INT);
			$stmt->bindParam("section_id", $section_id, PDO::PARAM_STR);
			$stmt->execute();
			$data = $stmt->fetch(PDO::FETCH_OBJ);
			$bookmarked = false;
			if(!$data) {
				$stmt = $db->prepare("INSERT INTO user_bookmarks(user_id, section_id) VALUES (:user_id, :section_id)");
				$stmt->bindParam("user_id", $uid, PDO::PARAM_INT);
				$stmt->bindParam("section_id", $section_id, PDO::PARAM_STR);
				$stmt->execute();
				$bookmarked = true;
			} else {
				$stmt = $db->prepare("DELETE FROM user_bookmarks WHERE user_id=:user_id AND section_id=:section_id");
				$stmt->bindParam("user_id", $uid, PDO::PARAM_INT);
				$stmt->bindParam("section_id", $section_id, PDO::PARAM_STR);
				$stmt->execute();
			}
			
			$db = null;
			return $bookmarked;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	public function getBookmarks($uid) {
		try {
			$db = getDB();
			
			$stmt = $db->prepare("SELECT section_id FROM user_bookmarks WHERE user_id=:user_id");
			$stmt->bindParam("user_id", $uid, PDO::PARAM_INT);
			$stmt->execute();
			$data = $stmt->fetchAll();
			
			$bookmarks = [];
			foreach($data as $bookmark_id) {
				array_push($bookmarks, $bookmark_id['section_id']);
			}
			
			$db = null;
			return $bookmarks;
		} catch (PDOException $e) {
			echo '{"error":{"text":' . $e->getMessage() . '}}';
		}
	}
	
	public function logAction($uid, $action_type, $param1 = '', $param2 = '', $param3 = '', $param4 = '', $param5 = '', $param6 = '') {
		try {
			/*
			"id" SERIAL PRIMARY KEY,
			"user_id" bigint DEFAULT NULL,
			"ip" varchar(45) DEFAULT NULL,
			"browser" varchar(100) DEFAULT NULL,
			"time" timestamp DEFAULT NULL,
			"action_type" smallint DEFAULT NULL,
			"duration" bigint DEFAULT NULL,
			"param1" varchar(1000) DEFAULT NULL,
			"param2" varchar(1000) DEFAULT NULL,
			"param3" varchar(1000) DEFAULT NULL
			*/
			
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
}
?>