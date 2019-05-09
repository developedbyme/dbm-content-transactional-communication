<?php
	function dbm_content_tc_get_keywords_in_text($text) {
		$matches = array();
		preg_match_all('/%[a-zA-Z0-9\\-_]+%/', $text, $matches);
		
		$used_keywords = array();
		foreach($matches[0] as $match) {
			$used_keywords[] = substr($match, 1, strlen($match)-2);
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
	
	function dbm_content_tc_parse_address_list($address_list) {
		$pattern = '/^(?:"?((?:[^"\\\\]|\\\\.)+)"?\s)?<?([a-z0-9._%\\-+]+@[a-z0-9.-]+\\.[a-z]{2,4})>?$/i';
		if (($address_list[0] != '<') and preg_match($pattern, $address_list, $matches)) {
			return array(
				array(
					'name' => stripcslashes($matches[1]),
					'email' => $matches[2]
				)
			);
		} else {
			$parts = str_getcsv($address_list);
			$result = array();
			foreach($parts as $part) {
				if (preg_match($pattern, $part, $matches)) {
					$item = array();
					if ($matches[1] != '') $item['name'] = stripcslashes($matches[1]);
					$item['email'] =  $matches[2];
					$result[] = $item;
				}
			}
			return $result;
		}
	}
	
	function dbm_content_tc_get_template_with_replacements($post_id, $replacements) {
		
		$return_object = array();
		
		$post = get_post($post_id);
		
		$base_text = apply_filters('the_content', $post->post_content);
		
		$used_keywords = dbm_content_tc_get_keywords_in_text($base_text);
		$body_replacements = dbm_content_tc_get_replacement_array($used_keywords, $replacements);
		
		$replaced_text = str_replace(array_keys($body_replacements), array_values($body_replacements), $base_text);
		$return_object['body'] = $replaced_text;
		
		if(dbm_has_post_relation($post_id, 'transactional-template-types/email')) {
			
			$base_title = get_post_meta($post_id, 'dbmtc_email_subject', true);
			$used_keywords = dbm_content_tc_get_keywords_in_text($base_title);
			$title_replacements = dbm_content_tc_get_replacement_array($used_keywords, $replacements);
			
			$replaced_title = str_replace(array_keys($title_replacements), array_values($title_replacements), $base_title);
			$return_object['title'] = $replaced_title;
		}
		
		return $return_object;
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
		
		return $tc_id;
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
		
		return $tc_id;
	}
	
	function dbm_content_tc_create_internal_message_group($title, $user_ids = array(), $add_standard_settings = true) {
		$new_id = dbm_create_data($title, 'internal-message-group', 'admin-grouping/internal-message-groups');
		
		update_post_meta($new_id, 'users_to_notify', $user_ids);
		
		foreach($user_ids as $user_id) {
			add_post_meta($new_id, 'user_access', $user_id);
		}
		
		if($add_standard_settings) {
			dbm_add_post_relation($new_id, 'internal-message-group-types/standard');
		}
		dbm_add_post_relation($new_id, 'internal-message-group-status/open');
		
		$args = array(
			'ID' => $new_id,
			'post_status' => 'private'
		);
		
		wp_update_post($args);
		
		return $new_id;
	}
	
	function dbm_content_tc_create_internal_message($title, $body, $from_user, $group_id = 0, $add_standard_settings = true) {
		
		$new_id = dbm_create_data($title, 'internal-message', 'admin-grouping/internal-messages');
		
		$args = array(
			'ID' => $new_id,
			'post_content' => $body,
			'post_author' => $from_user,
			'post_status' => 'private'
		);
		
		if($group_id > 0) {
			$args['post_parent'] = $group_id;
			$group_term = dbm_get_owned_relation($group_id, 'internal-message-group');
			
			$parent_term = dbm_get_relation_by_path('internal-message-groups');
		
			dbm_replace_relations($new_id, $parent_term, array($group_term->term_id));
		}
		
		if($add_standard_settings) {
			dbm_add_post_relation($new_id, 'internal-message-types/message');
		}
		
		wp_update_post($args);
		
		return $new_id;
	}
	
	function dbm_content_tc_notify_for_new_message($message_id) {
		$group_query = dbm_new_query('dbm_data')->set_argument('post_status', 'private')->add_type_by_path('internal-message-group')->add_relations_from_post($message_id, 'internal-message-groups');
		$group_id = $group_query->get_post_id();
		
		$url = null;
		
		$view_page_id = dbm_new_query('page')->add_relation_by_path('global-pages/view-internal-message')->get_post_id();
		if($view_page_id) {
			$url = get_permalink($view_page_id);
			$url .= '?group='.$group_id.'&message='.$message_id;
		}
		
		$message_post = get_post($message_id);
		$message = apply_filters('the_content', get_post_field('post_content', $message_id));
		
		$from_user = get_user_by('id', $message_post->post_author);
		
		$replacements = array(
			'link' => $url,
			'message' => $message,
			'title' => $message_post->post_title,
			'from-email' => $from_user->user_email,
			'from-name' => $from_user->display_name,
			'group-id' => $group_id
		);
		
		$email_query = dbm_new_query('dbm_additional')->add_relation_by_path('global-transactional-templates/new-internal-message');
		$email_id = $email_query->get_post_id();
		
		$template = dbm_content_tc_get_template_with_replacements($email_id, $replacements);
		
		$communications = array();
		
		$user_ids = get_post_meta($group_id, 'users_to_notify', true);
		foreach($user_ids as $user_id) {
			if($user_id !== $from_user->ID || true) {
				$current_user = get_user_by('id', $user_id);
				$email = $current_user->user_email;
			
				$user_replacements = array(
					'to-email' => $email,
					'to-name' => $current_user->display_name,
				);
			
				$title = $template['title'];
				$body = $template['body'];
			
				$communications[] = dbm_content_tc_send_email($title, $body, $email, apply_filters('dbm_content_tc/default_from_email', get_option('admin_email')));
			}
		}
		
		return $communications;
	}
?>