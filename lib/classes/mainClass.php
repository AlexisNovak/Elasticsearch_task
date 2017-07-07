<?php
class mainClass {
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
	
	public function getExtraUsersTiers() {
		return [0, 5, 10, 20];
	}
	
	public function alert($type, $text) {
		$types = array('error' => 'danger', 'success' => 'success', 'warning' => 'warning');
		return '<div class="alert alert-' . $types[$type] . '" role="alert">' . $text . '</div>';
	}
	
	public function getFilesArray(&$file_post) {
		$file_ary = array();
		$file_count = count($file_post['name']);
		$file_keys = array_keys($file_post);

		for ($i=0; $i<$file_count; $i++) {
			foreach ($file_keys as $key) {
				$file_ary[$i][$key] = $file_post[$key][$i];
			}
		}
		return $file_ary;
	}

	private function getPaginationPages($page_count, $page_limit, $page_current, $adjacents) {
		if($page_count == 0)
			return array(1);
		
		$result = array();
		if (isset($page_count, $page_limit) === true) {
			$result = range(1, ceil($page_count / $page_limit));
			if (isset($page_current, $adjacents) === true) {
				if (($adjacents = floor($adjacents / 2) * 2 + 1) >= 1) {
					$result = array_slice($result, max(0, min(count($result) - $adjacents, intval($page_current) - ceil($adjacents / 2))), $adjacents);
				}
			}
		}		
		return $result;
	}
	
	public function getPagination($table_name, $limit, $conditions = []) {
		$db = getDB();

		
		/*$conditions_string = implode(' AND ', array_map(function ($entry) {
			return $entry[0] . ' ' . $entry[1] . ' :' . $entry[0];
		}, $conditions));*/
		$conditions_array = [];
		for($i = 0; $i < sizeof($conditions); $i++) {
			$condition_string = $conditions[$i][0] . ' ' . $conditions[$i][1] . ' :' . $conditions[$i][0] . '_' . $i;
			array_push($conditions_array, $condition_string);
		}
		$conditions_string = implode(' AND ', $conditions_array);
				
		if(sizeof($conditions) == 0) {
			$stmt = $db->prepare("SELECT COUNT(*) FROM " . $table_name);
		} else {
			$stmt = $db->prepare("SELECT COUNT(*) FROM " . $table_name . ' WHERE ' . $conditions_string);
			$cond_i = 0;
			foreach($conditions as $condition)
				$stmt->bindParam(':' . $condition[0] . '_' . $cond_i++, $condition[2], $condition[3]);;
		}
	
		$stmt->execute();
		$total = $stmt->fetchColumn();
	

		
		$pages = ceil($total / $limit);

		$page = min($pages, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, array(
			'options' => array(
				'default'   => 1,
				'min_range' => 1,
			),
		)));

		
		$offset = ($page - 1)  * $limit;
		if($offset < 0)
			$offset = 0;
		if($page < 1)
			$page = 1;

		$pagination_html = '<div class="text-center"><ul class="pagination">';
		$adjacent_links = 4;
		
		unset($_GET['page']);
		$params_urls = http_build_query($_GET);
		
		$pagination = $this->getPaginationPages($total, $limit, $page, $adjacent_links);
		//first page
		$pagination_html .= $page > 1 ? '<li><a href="?' . $params_urls . '&page=1">&laquo;</a></li>' : '<li class="disabled"><a>&laquo;</a></li>';
		//previous page
		$pagination_html .= $page > 1 ? '<li><a href="?' . $params_urls . '&page=' . ($page - 1) . '">&lsaquo;</a></li>' : '<li class="disabled"><a>&lsaquo;</a></li>';
		for($i = 0; $i < sizeof($pagination); $i++) {
			$pagination_html .= '<li' . ($pagination[$i] == $page ? ' class="active"' : '') . '><a href="?' . $params_urls . '&page=' . $pagination[$i] . '">' . $pagination[$i] . '</a></li>';
		}
		//next page
		$pagination_html .= $page < $pages ? '<li><a href="?' . $params_urls . '&page=' . ($page + 1) . '">&rsaquo;</a></li>' : '<li class="disabled"><a>&rsaquo;</a></li>';
		//last page
		$pagination_html .= $page < $pages ? '<li><a href="?' . $params_urls . '&page=' . $pages . '">&raquo;</a></li>' : '<li class="disabled"><a>&raquo;</a></li>';
		
		$pagination_html .= '</ul></div>';
				
		return array('html' => $pagination_html, 'page' => $page, 'offset' => $offset, 'limit' => $limit, 'conditions' => $conditions, 'table_name' => $table_name);
	}
}
?>