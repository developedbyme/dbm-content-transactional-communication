<?php
	function dbm_content_tc_get_keywords_in_text($text) {
		$matches = array();
		preg_match_all('/%[a-zA-Z0-0\\-_]+%/', $text, $matches);
		
		$used_keywords = array();
		foreach($matches[0] as $match) {
			$used_keywords[] = substr($match, 1, count($match)-2);
		}
	
		array_unique($used_keywords);
	
		return $used_keywords;
	}

	function dbm_content_tc_get_replacement_array($used_keywords, $keywords_map) {
		$replacements = array();
	
		foreach($used_keywords as $index => $keyword) {
			if(isset($keywords_map[$keyword])) {
				$replacements['%'.$keyword.'%'] = $keywords_map[$keyword];
			}
		}
	
		return $replacements;
	}
	
	function dbm_content_tc_perform_replacements($text, $replacements) {
		$replaced_text = str_replace(array_keys($replacements), array_values($replacements), $text);
	
		return $replaced_text;
	}

	function dbm_content_tc_replace_keywords($text, $keywords_map) {
	
		$used_keywords = dbm_content_tc_get_keywords_in_text($text);
		$replacements = dbm_content_tc_get_replacement_array($used_keywords, $keywords_map);
	
		return dbm_content_tc_perform_replacements($text, $replacements);
	}
	
	function dbm_content_tc_create_transactional_communication($type) {
		$current_date = new \DateTime();
		
		$new_id = dbm_create_data('(not set) - ' . $type . ' - ' . $current_date->format('Y-m-d H:i:s'), 'transactional-communication', 'admin-grouping/sent-communications');
		
		$parent_term = dbm_get_relation_by_path('transactional-template-types');
		$type_term = dbm_get_relation_by_path('transactional-template-types/'.$type);
		
		dbm_replace_relations($new_id, $parent_term, array($type_term->term_id));
		
		return $new_id;
	}
	
	function dbm_content_tc_send_email($title, $content, $to, $from, $tc_id = 0, $additional_data = null) {
		if($tc_id === 0) {
			$tc_id = dbm_content_tc_create_transactional_communication('email');
		}
		
		$current_date = new \DateTime();
		
		update_post_meta($tc_id, 'title', $title);
		update_post_meta($tc_id, 'content', $content);
		update_post_meta($tc_id, 'to', $to);
		update_post_meta($tc_id, 'from', $from);
		update_post_meta($tc_id, 'additional_data', $additional_data);
		
		$args = array(
			'ID' => $tc_id,
			'post_title' => $to . ' - email - ' . $current_date->format('Y-m-d H:i:s'),
			'post_status' => 'publish'
		);
		
		wp_update_post($args);
		
		$send_result = apply_filters('dbm_content_tc/send_email', false, $title, $content, $to, $from, $tc_id, $additional_data);
		update_post_meta($tc_id, 'send_result', $send_result);
	}
	
	function dbm_content_tc_send_text_message($content, $to, $from, $tc_id = 0, $additional_data = null) {
		if($tc_id === 0) {
			$tc_id = dbm_content_tc_create_transactional_communication('text-message');
		}
		
		$current_date = new \DateTime();
		
		update_post_meta($tc_id, 'content', $content);
		update_post_meta($tc_id, 'to', $to);
		update_post_meta($tc_id, 'from', $from);
		update_post_meta($tc_id, 'additional_data', $additional_data);
		
		$args = array(
			'ID' => $tc_id,
			'post_title' => $to . ' - text-message - ' . $current_date->format('Y-m-d H:i:s'),
			'post_status' => 'publish'
		);
		
		wp_update_post($args);
		
		$send_result = apply_filters('dbm_content_tc/send_text_message', false, $content, $to, $from, $tc_id, $additional_data);
		update_post_meta($tc_id, 'send_result', $send_result);
	}
?>