<?php
class searchClass {
	
	/* Ask Question */
	public function search_old($question, $state) {
		$answers = [];
		if($question == '')
			return $answers;
		
		$filter_state_query = $state > 0 ? '%20AND%20state_id:' . $state : '';
					
		$url = 'http://localhost:8983/solr/gfc/select?defType=edismax&indent=on';
		$url .= '&fq=entity_type:1%20AND%20document_type:3' . $filter_state_query;
		$url .= '&q=' . urlencode($question) . '*';
		$url .= '&qf=paragraph_num^4%20paragraph_title%20paragraph_text';
		$url .= '&rows=50&spellcheck=on&wt=json';

		$obj = json_decode(file_get_contents($url), true);
		$answer_num = 0;
		foreach($obj['response']['docs'] as $answer) {
			$answer_num++;
			if(!isset($answer['paragraph_num']))
				$answer['paragraph_num'] = '';
			if(!isset($answer['paragraph_text']))
				$answer['paragraph_text'] = '';
			if(!isset($answer['paragraph_html']))
				$answer['paragraph_html'] = $answer['paragraph_text'];
						
			$answer['answer_num'] = $answer_num;
			
			array_push($answers, $answer);
		}
		
		return $answers;
	}
	
	public function search($query, $fq, $qf, $rows) {
		$results = [];
		if($query == '')
			return $results;
		
		$url = 'http://datolabs.com:8983/solr/dms/select?indent=on&rows=50&start=0';
		
		if($fq != '')
			$url .= '&fq=' . rawurlencode($fq);
		if($qf != '')
			$url .= '&qf=' . rawurlencode($qf);

		$url .= '&q=' . urlencode($query);
		
		//$url .= '&rows=' . $rows . '&spellcheck=on&wt=json';

		$url .= '&wt=json&hl=on';
		//echo $url;

		$obj = json_decode(file_get_contents($url), true);
		/*foreach($obj['response']['docs'] as $result) {
			array_push($results, $result['question']);
		}*/
		return $obj['response']['docs'];
	}
}
?>