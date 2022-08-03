<?php
	function dbm_content_tc_get_keywords_in_text($text) {
		$matches = array();
		preg_match_all('/%[a-zA-Z0-9\\-_:]+%/', $text, $matches);
		
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
	
	function dbm_content_tc_send_email($title, $content, $to, $from = null, $tc_id = 0, $additional_data = null) {
		if($tc_id === 0) {
			$tc_id = dbm_content_tc_create_transactional_communication('email');
		}
		
		if(!$from) {
			$from = dbmtc_get_default_from_email();
		}
		
		$time_zone = get_option('timezone_string');
		if($time_zone) {
			date_default_timezone_set($time_zone);
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
	
	function dbm_content_tc_send_text_message($content, $to, $from = null, $tc_id = 0, $additional_data = null) {
		if($tc_id === 0) {
			$tc_id = dbm_content_tc_create_transactional_communication('text-message');
		}
		
		if(!$from) {
			$from = dbmtc_get_default_from_phone_number();
		}
		
		$time_zone = get_option('timezone_string');
		if($time_zone) {
			date_default_timezone_set($time_zone);
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
			
			$dbm_post = dbm_get_post($new_id);
			$dbm_post->add_outgoing_relation_by_name($group_id, 'message-in');
		}
		
		if($add_standard_settings) {
			dbm_add_post_relation($new_id, 'internal-message-types/message');
		}
		
		wp_update_post($args);
		
		return $new_id;
	}
	
	function dbm_content_tc_notify_for_new_message($message_id) {
		//var_dump("dbm_content_tc_notify_for_new_message");
		
		$message = dbmtc_get_internal_message($message_id);
		$message->notify();
		
		$all_communications = $message->get_sent_communications();
		
		return $all_communications[count($all_communications)-1];
	}
	
	function dbmtc_create_group($title, $type = null, $user_ids = array()) {
		$new_id = dbm_content_tc_create_internal_message_group($title, $user_ids, false);
		
		if($type) {
			dbm_add_post_relation($new_id, 'internal-message-group-types/'.$type);
		}
		
		return dbmtc_get_internal_message_group($new_id);
	}
	
	function dbmtc_get_internal_message_group($post_id) {
		return dbmtc_get_group($post_id);
	}
	
	global $dbmtc_items;
	$dbmtc_items = array();
	
	function dbmtc_get_group($post_id) {
		
		global $dbmtc_items;
		$item_id = 'group_'.$post_id;
		if(!isset($dbmtc_items[$item_id])) {
			$internal_message_group = new \DbmContentTransactionalCommunication\InternalMessageGroup($post_id);
		
			$dbmtc_items[$item_id] = $internal_message_group;
		}
		
		return $dbmtc_items[$item_id];
	}
	
	function dbmtc_get_internal_message($post_id) {
		global $dbmtc_items;
		$item_id = 'message_'.$post_id;
		if(!isset($dbmtc_items[$item_id])) {
			$internal_message = new \DbmContentTransactionalCommunication\InternalMessage($post_id);
			
			$dbmtc_items[$item_id] = $internal_message;
		}
		
		return $dbmtc_items[$item_id];
	}
	
	function dbmtc_get_internal_message_group_field($post_id) {
		global $dbmtc_items;
		$item_id = 'field_'.$post_id;
		if(!isset($dbmtc_items[$item_id])) {
			$internal_message_field = new \DbmContentTransactionalCommunication\InternalMessageGroupField($post_id);
			
			$dbmtc_items[$item_id] = $internal_message_field;
		}
		
		return $dbmtc_items[$item_id];
	}
	
	function dbmtc_send_email_template($template_slug, $to, $from = null, $replacements = array(), $additional_data = array()) {
		
		$template_id = dbm_new_query('dbm_additional')->add_relation_by_path($template_slug)->get_post_id();
		
		if(!$template_id) {
			return 0;
		}
		
		if(!$from) {
			$from = dbmtc_get_default_from_email();
		}
		
		$template = dbm_content_tc_get_template_with_replacements($template_id, $replacements);
		$new_id = dbm_content_tc_send_email($template['title'], $template['body'], $to, $from, 0, $additional_data);
		
		return $new_id;
	}
	
	function dbmtc_send_text_message_verification($phone_number) {
		$code = mt_rand(100000, 999999);
		
		$replacements = array(
			'code' => $code,
			'phone-number' => $phone_number
		);
		
		$hash_salt = 'Tw?otIAwI%ourB-:@VeZ4tGLY0=Twh)1J Wwhxc!5AOg:*L$Ff@CAY+d-iW47Ztm';
		//METODO: add filter around salt
		$hash = md5($phone_number.$hash_salt);
		
		$data_id = dbm_create_data('Phone number verification - '.$hash, 'address-verification', 'admin-grouping/address-verifications');
		update_post_meta($data_id, 'verification_hash', $hash);
		update_post_meta($data_id, 'verification_code', $code);
		update_post_meta($data_id, 'verified', false);
		
		wp_update_post(array(
			'ID' => $data_id,
			'post_status' => 'private'
		));
		
		$template_id = dbm_new_query('dbm_additional')->add_relation_by_path('global-transactional-templates/verify-phone-number')->get_post_id();
		
		$template = dbm_content_tc_get_template_with_replacements($template_id, $replacements);
		
		$clean_text = wp_strip_all_tags($template['body']);
		$communication_id = dbm_content_tc_send_text_message($clean_text, $phone_number, dbmtc_get_default_from_phone_number());
		
		update_post_meta($data_id, 'send_time', time());
		update_post_meta($data_id, 'communication_id', $communication_id);
		
		return $data_id = $data_id;
	}
	
	function dbmtc_setup_field_template($for_type, $field_name, $type = 'string', $storage_type = null, $meta = array()) {
		//echo('dbmtc_setup_field_template');
		
		$for_type_term = dbm_get_type_by_path($for_type);
		
		$existing_id = dbm_new_query('dbm_data')->set_argument('post_status', array('publish', 'private'))->add_type_by_path('field-template')->add_meta_query('dbmtc_key', $field_name)->add_meta_query('dbmtc_for_type', $for_type_term->term_id)->get_post_id();
		if($existing_id) {
			return $existing_id;
		}
		
		$new_id = dbm_create_data($field_name.' ('.$for_type.' field)', 'field-template', 'admin-grouping/field-templates');
		
		dbm_add_post_relation($new_id, 'field-type/'.$type);
		if($storage_type) {
			dbm_add_post_relation($new_id, 'field-storage/'.$storage_type);
		}
		
		foreach($meta as $key => $value) {
			update_post_meta($new_id, $key, $value);
		}
		
		update_post_meta($new_id, 'dbmtc_key', $field_name);
		update_post_meta($new_id, 'dbmtc_for_type', $for_type_term->term_id);
		
		wp_update_post(array(
			'ID' => $new_id,
			'post_status' => 'private'
		));
		
		return $new_id;
	}
	
	function dbmtc_get_default_from_email() {
		return apply_filters('dbm_content_tc/default_from_email', get_option('admin_email'));
	}
	
	function dbmtc_get_default_from_email_for_internal_message($internal_message_id) {
		//var_dump('dbmtc_get_default_from_email_for_internal_message');
		
		$return_value = apply_filters('dbm_content_tc/default_from_email_for_internal_message', null, $internal_message_id);
		if($return_value) {
			return $return_value;
		}
		
		return dbmtc_get_default_from_email();
	}
	
	function dbmtc_get_default_from_phone_number() {
		$site_name = substr(preg_replace('/[^a-zA-Z0-9 \\-]+/', '', get_bloginfo('name'), -1), 0, 8);
		
		return apply_filters('dbm_content_tc/default_from_phone_number', $site_name);
	}
	
	function dbmtc_create_timed_action($time, $action, $data) {
		$new_id = dbm_create_data($action.' ('.$time.')', 'timed-action', 'admin-grouping/timed-actions');
		
		$new_timed_action = new \DbmContentTransactionalCommunication\TimedAction\TimedAction($new_id);
		
		$new_timed_action->set_time($time);
		$new_timed_action->set_action($action);
		$new_timed_action->set_action_data($data);
		$new_timed_action->set_action_status('waiting');
		$new_timed_action->make_private();
		
		//METODO: clear cache
		
		return $new_timed_action;
	}
	
	function dbmtc_get_timed_action($id) {
		$new_timed_action = new \DbmContentTransactionalCommunication\TimedAction\TimedAction($id);
		
		return $new_timed_action;
	}
	
	function dbmtc_create_verification_generator() {
		$new_verification = new \DbmContentTransactionalCommunication\Verification\VerificationGenerator();
		
		return $new_verification;
	}
	
	function dbmtc_get_verification($id) {
		$new_verification = new \DbmContentTransactionalCommunication\Verification\Verification($id);
		
		return $new_verification;
	}
	
	function dbmtc_get_user($user_or_any_login) {
		$user = null;
		if($user_or_any_login instanceof \WP_User) {
			$user = $user_or_any_login;
		}
		else if(is_int($user_or_any_login)) {
			$user = get_user_by('id', $user_or_any_login);
		}
		else {
			$user = get_user_by('login', $user_or_any_login);
		
			if(!$user) {
				$user = get_user_by('email', $user_or_any_login);
			}
		}
		
		return $user;
	}
	
	function dbmtc_get_user_contact($user_or_any_login) {
		
		$user = dbmtc_get_user($user_or_any_login);
		
		if(!$user) {
			return null;
		}
		
		$contact = new \DbmContentTransactionalCommunication\Contact\UserContact($user->ID);
		
		return $contact;
	}
	
	function dbmtc_get_contact_for($id, $type = 'manual') {
		$contact = apply_filters('dbmtc/get_contact_for/'.$type, null, $id);
		
		return $contact;
	}
	
	function dbmtc_create_contact() {
		$contact = new \DbmContentTransactionalCommunication\Contact\Contact();
		
		return $contact;
	}
	
	function dbmtc_get_manual_contact($email) {
		
		//METODO: check for valid email
		
		$contact = new \DbmContentTransactionalCommunication\Contact\Contact();
		$contact->set_email($email);
		
		return $contact;
	}
	
	function dbmtc_get_contact_from_fields($group_id, $email_field_name = 'email', $name_field_name = 'name') {
		
		$group = dbmtc_get_group($group_id);
		
		$contact = new \DbmContentTransactionalCommunication\Contact\Contact();
		$contact->set_email($group->get_field_value($email_field_name));
		if($name_field_name) {
			$name = $group->get_field_value($name_field_name);
			$contact->set_name($name['firstName'], $name['lastName']);
		}
		
		
		return $contact;
	}
	
	function dbmtc_create_template($title, $content) {
		$template = new \DbmContentTransactionalCommunication\Template\Template();
		
		$template->set_content($title, $content);
		
		return $template;
	}
	
	function dbmtc_create_template_from_post($post_id) {
		$template = new \DbmContentTransactionalCommunication\Template\Template();
		
		$template->setup_from_post($post_id);
		
		return $template;
	}
	
	function dbmtc_send_template_as_email($template, $to_contact, $from_contact = null) {
		
		if(!$from_contact) {
			$from_contact = dbmtc_get_manual_contact(dbmtc_get_default_from_email());
		}
		
		$template->add_keywords_provider($from_contact->create_keywords_provider(), 'from');
		$template->add_keywords_provider($to_contact->create_keywords_provider(), 'to');
		
		$content = $template->get_content();
	
		if(!$content['title'] && !$content['content']) {
			throw new \Exception('No content');
		}
		
		$communication_id = dbm_content_tc_send_email($content['title'], $content['content'], $to_contact->get_contact_details('email'), $from_contact->get_contact_details('email'));
		
		$to_contact->link_to_communication($communication_id);
		
		return $communication_id;
	}
	
	function dbmtc_create_static_keywords_replacements($keywords = null) {
		$replacement = new \DbmContentTransactionalCommunication\Template\StaticKeywordReplacements();
		
		if($keywords) {
			$replacement->add_keywords($keywords);
		}
		
		return $replacement;
	}
	
	function dbmtc_create_wc_order_keywords_provider($order_or_id) {
		$provider = new \DbmContentTransactionalCommunication\Template\WcOrderKeywordsProvider();
		
		$order = wc_get_order($order_or_id);
		$provider->set_order($order);
		
		return $provider;
	}
	
	function dbmtc_create_wc_subscription_keywords_provider($subscription_or_id) {
		$provider = new \DbmContentTransactionalCommunication\Template\WcSubscriptionKeywordsProvider();
		
		$order = wcs_get_subscription($subscription_or_id);
		$provider->set_order($order);
		
		return $provider;
	}
	
	function dbmtc_create_filter_keywords_provider($filter_name = null, $data = null, $triggering_keywords = null) {
		$provider = new \DbmContentTransactionalCommunication\Template\FilterKeywordsProvider();
		
		if($filter_name) {
			$provider->set_filter_name($filter_name);
		}
		if($data) {
			$provider->set_data($data);
		}
		if($triggering_keywords) {
			$provider->set_triggering_keywords($triggering_keywords);
		}
		
		return $provider;
	}
	
	function dbmtc_send_email_from_template($template_id, $for_id, $for_type = 'manual', $from = null, $message_type = 'standard', $keywords = array()) {
		if(!$from) {
			$from = dbmtc_get_default_from_email();
		}
			
		$from_contact = dbmtc_get_manual_contact($from);
		
		$template = dbmtc_create_template_from_post($template_id);
		
		$to_contact = apply_filters('dbmtc/get_contact_for/'.$for_type, null, $for_id);
		if(!$to_contact) {
			throw new \Exception('No contact');
		}
		
		$template->add_keywords_provider($from_contact->create_keywords_provider(), 'from');
		$template->add_keywords_provider($to_contact->create_keywords_provider(), 'to');
	
		do_action('dbmtc/setup_template/'.$message_type, $template, $for_id);
		
		//METODO: add manual keywords
	
		$content = $template->get_content();
	
		if(!$content['title'] && !$content['content']) {
			throw new \Exception('No content');
		}
		
		return dbm_content_tc_send_email($content['title'], $content['content'], $to_contact->get_contact_details('email'), $from);
	}
	
	function dbmtc_get_or_create_type($type, $identifier) {
		$type_id = dbm_new_query('dbm_data')->include_private()->include_only_type($type)->add_meta_query('identifier', $identifier)->get_post_id();
		
		if(!$type_id) {
			$type_id = dbm_create_data($type.' '.$identifier, $type);
			$group = dbmtc_get_group($type_id);
			
			$group->add_type_by_name('type');
			$group->set_field('identifier', $identifier);
			$group->set_field('name', $identifier);
			$group->publish();
		}
		
		return $type_id;
	}
	
	function dbmtc_create_tag($tag) {
		return dbmtc_create_type('type/tag', $tag);
	}
	
	function dbmtc_tag_item($id, $tag) {
		$type_id = dbmtc_get_or_create_type('type/tag', $tag);
		
		$post = dbmtc_get_group($id);
		$added_tags = $post->object_relation_query('in:for:type/tag');
		
		if(!in_array($type_id, $added_tags)) {
			$tag_relation = dbmtc_get_group($post->add_incoming_relation_by_name($type_id, 'for'));
			$tag_relation->update_meta('startAt', time());
			
			dbmtc_add_action_to_process('tagAdded', array($id, $type_id), array('item' => $id, 'tag' => $tag));
		}
	}
	
	function dbmtc_untag_item($id, $tag) {
		$type_id = dbmtc_get_or_create_type('type/tag', $tag);
		
		$post = dbmtc_get_group($id);
		
		$has_relation = false;
		$relations = $post->get_encoded_incoming_relations_by_type('for', 'type/tag');
		
		foreach($relations as $relation_data) {
			if($relation_data['fromId'] === $type_id) {
				$relation = dbmtc_get_group($relation_data['id']);
				$relation->set_field('endAt', time());
				$has_relation = true;
			}
		}
		
		if($has_relation) {
			dbmtc_add_action_to_process('tagRemoved', array($id, $type_id), array('item' => $id, 'tag' => $tag));
		}
	}
	
	function dbmtc_add_trigger($post_id, $type, $valid_for = -1) {
		
		$post = dbmtc_get_group($post_id);
		
		$type_id = dbmtc_get_or_create_type('type/trigger-type', $type);
		$tigger = dbmtc_get_group(dbm_create_data('Trigger '.$type.' for '.$post_id, 'trigger'));
		$tigger->add_incoming_relation_by_name($type_id, 'for');
		
		$tigger_relation = dbmtc_get_group($post->add_incoming_relation_by_name($tigger->get_id(), 'for'));
	
		$tigger_relation->update_meta('startAt', time());
		
		if($valid_for > 0) {
			$tigger_relation->update_meta('endAt', time()+$valid_for);
		}
		
		$tigger->make_private();
		
		dbmtc_add_action_to_process('handleTrigger/'.$type, array($tigger->get_id()));
	}
	
	function dbmtc_add_single_trigger($post_id, $type, $valid_for = -1) {
		
		$has_trigger = false;
		
		$post = dbmtc_get_group($post_id);
		$trigger_ids = $post->object_relation_query('in:for:trigger');
		foreach($trigger_ids as $trigger_id) {
			$trigger_post = dbmtc_get_group($trigger_id);
			if($trigger_post->get_single_object_relation_field_value('in:for:type/trigger-type', 'identifier') === $type) {
				$has_trigger = true;
				break;
			}
		}
		
		if(!$has_trigger) {
			$type_id = dbmtc_get_or_create_type('type/trigger-type', $type);
			$tigger = dbmtc_get_group(dbm_create_data('Trigger '.$type.' for '.$post_id, 'trigger'));
			$tigger->add_incoming_relation_by_name($type_id, 'for');
			
			$tigger_relation = dbmtc_get_group($post->add_incoming_relation_by_name($tigger->get_id(), 'for'));
		
			$tigger_relation->update_meta('startAt', time());
			
			if($valid_for > 0) {
				$tigger_relation->update_meta('endAt', time()+$valid_for);
			}
			
			$tigger->make_private();
			
			dbmtc_add_action_to_process('handleTrigger/'.$type, array($tigger->get_id()));
		}
	}
	
	function dbmtc_has_trigger($post_id, $type) {
		
		$post = dbmtc_get_group($post_id);
		$trigger_ids = $post->object_relation_query('in:for:trigger');
		foreach($trigger_ids as $trigger_id) {
			$trigger_post = dbmtc_get_group($trigger_id);
			if($trigger_post->get_single_object_relation_field_value('in:for:type/trigger-type', 'identifier') === $type) {
				$has_trigger = true;
				return true;
			}
		}
		
		return false;
	}
	
	function dbmtc_remove_trigger($post_id, $type) {
		$post = dbmtc_get_group($post_id);
		
		$trigger_relations_ids = $post->get_incoming_relations('for', 'trigger');
		
		foreach($trigger_relations_ids as $trigger_relations_id) {
			$relation_post = dbmtc_get_group($trigger_relations_id);
			$trigger_post = dbmtc_get_group($relation_post->get_meta('fromId'));
			if($trigger_post->get_single_object_relation_field_value('in:for:type/trigger-type', 'identifier') === $type) {
				$relation_post->update_meta('endAt', time());
				$relation_post->clear_cache();
				$trigger_post->clear_cache();
			}
		}
		
		$post->clear_cache();
	}
	
	function dbmtc_add_action_to_process($type, $from_ids = null, $data = null, $time = null) {
		$action_type_id = dbmtc_get_or_create_type('type/action-type', $type);
		
		$action_id = dbm_create_data('Action: '.$type, 'action');
		
		$action_group = dbmtc_get_group($action_id);
		
		if($data) {
			$action_group->add_type_by_name('value-item');
			$action_group->set_field('value', $data);
		}
		
		if($from_ids) {
			if(!is_array($from_ids)) {
				$from_ids = array($from_ids);
			}
			
			foreach($from_ids as $from_id) {
				$action_group->add_outgoing_relation_by_name($from_id, 'from');
			}
		}
		
		$action_group->make_private();
		
		$action_group->add_incoming_relation_by_name($action_type_id, 'for');
		
		$action_group->update_meta('needsToProcess', true);
		$type_id = dbmtc_get_or_create_type('type/action-status', 'readyToProcess');
		$status_relation = dbmtc_get_group($action_group->add_incoming_relation_by_name($type_id, 'for'));
		
		if(!$time) {
			$time = time();
		}
		
		$status_relation->update_meta('startAt', $time);
		
		return $action_id;
	}
	
	function dbmtc_process_action($action_id) {
		
		$action = dbmtc_get_group($action_id);
			
		$action->end_incoming_relations_from_type('for', 'type/action-status');
		
		$processing_id = dbmtc_get_or_create_type('type/action-status', 'processing');
		$action->add_incoming_relation_by_name($processing_id, 'for', time());
		
		$action_type = $action->get_single_object_relation_field_value('in:for:type/action-type', 'identifier');
		$hook_name = 'dbmtc/process_action/'.$action_type;
		
		if(has_action($hook_name)) {
			do_action($hook_name, $action->get_id());
			
			$done_id = dbmtc_get_or_create_type('type/action-status', 'done');
			$action->end_incoming_relations_from_type('for', 'type/action-status');
			$action->add_incoming_relation_by_name($done_id, 'for', time());
		}
		else {
			$noAction_id = dbmtc_get_or_create_type('type/action-status', 'noAction');
			$action->end_incoming_relations_from_type('for', 'type/action-status');
			$action->add_incoming_relation_by_name($noAction_id, 'for', time());
		}
		
		$action->update_meta('needsToProcess', false);
	}
	
	function dbmtc_create_request($url, $body = null, $method = 'GET', $headers = array(), $curl_options = array()) {
		$send_status_id = dbmtc_get_or_create_type('type/send-status', 'waiting');
		$method_id = dbmtc_get_or_create_type('type/request-method', $method);
		
		$request_id = dbm_create_data('Request', 'request');
		$request = dbmtc_get_group($request_id);
		
		$request->set_field('url', $url);
		$request->set_field('body', $body);
		$request->set_field('headers', $headers);
		$request->set_field('curlOptions', $curl_options);
		
		$request->add_incoming_relation_by_name($send_status_id, 'for');
		$request->add_incoming_relation_by_name($method_id, 'for');
		
		$request->make_private();
		
		return $request_id;
	}
	
	function dbmtc_send_request($id) {
		$request = dbmtc_get_group($id);
		
		
		$current_status = $request->get_single_object_relation_field_value('in:for:type/send-status', 'identifier');
		
		if($current_status === "waiting") {
			$sending_status_id = dbmtc_get_or_create_type('type/send-status', 'sending');
			$request->end_incoming_relations_from_type('for', 'type/send-status');
			$request->add_incoming_relation_by_name($sending_status_id, 'for', time());
			
			$url = $request->get_field_value('url');
			$body = $request->get_field_value('body');
			$headers = $request->get_field_value('headers');
			$curl_options = $request->get_field_value('curlOptions');
			$method = $request->get_single_object_relation_field_value('in:for:type/request-method', 'identifier');
			
			$ch = curl_init($url);
			
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			foreach($curl_options as $curl_option) {
				curl_setopt($ch, $curl_option['key'], $curl_option['value']);
			}
			
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			
			if($body) {
				if(is_string($body)) {
					curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
				}
				else {
					curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
				}
			}
			
			try {
				$result = curl_exec($ch);
				$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);
				
				$request->set_field('response', $result);
				$request->set_field('responseCode', $status_code);
				
				$sent_status_id = dbmtc_get_or_create_type('type/send-status', 'sent');
				$request->end_incoming_relations_from_type('for', 'type/send-status');
				$request->add_incoming_relation_by_name($sent_status_id, 'for', time());
			}
			catch(\Exception $e) {
				$error_status_id = dbmtc_get_or_create_type('type/send-status', 'error');
				$request->end_incoming_relations_from_type('for', 'type/send-status');
				$request->add_incoming_relation_by_name($error_status_id, 'for', time());
			}
		}
	}
	
	function dbmtc_get_credentials_for_email($email) {
		return apply_filters('dbmtc/credentials_for_email', array(), $email);
	}
?>