<?php
	namespace DbmContentTransactionalCommunication;

	class CustomRangeFilters {

		function __construct() {
			//echo("\DbmContentTransactionalCommunication\CustomRangeFilters::__construct<br />");
		}
		
		public function register() {
			//echo("\DbmContentTransactionalCommunication\CustomRangeFilters::register<br />");
			
			add_filter('wprr/range_query/myInternalMessageGroups', array($this, 'filter_query_myInternalMessageGroups'), 10, 2);
			add_filter('wprr/range_query/groupsWithUser', array($this, 'filter_query_groupsWithUser'), 10, 2);
			add_filter('wprr/range_encoding/internalMessageGroup', array($this, 'filter_encode_internalMessageGroup'), 10, 3);
			add_filter('wprr/range_encoding/messagesInGroup', array($this, 'filter_encode_messagesInGroup'), 10, 3);
		}
		
		public function filter_query_groupsWithUser($query_args, $data) {
			//echo("\DbmContentTransactionalCommunication\CustomRangeFilters::filter_query_groupsWithUser<br />");
			
			$user_id = (int)$data['withUser'];
			
			$dbm_query = dbm_new_query($query_args);
			$dbm_query->add_meta_query('user_access', $user_id);
			
			return $dbm_query->get_query_args();
		}
		
		public function filter_query_myInternalMessageGroups($query_args, $data) {
			//echo("\DbmContentTransactionalCommunication\CustomRangeFilters::filter_query_myInternalMessageGroups<br />");
			
			$current_user_id = get_current_user_id();
			$user_id = (int)$data['userId'];
			
			if(!current_user_can('edit_others_posts') && $current_user_id !== $user_id) {
				$query_args['post__in'] = array(0);
				return $query_args;
			}
			
			$dbm_query = dbm_new_query($query_args);
			$dbm_query->add_type_by_path('internal-message-group');
			$dbm_query->set_argument('post_status', 'private');
			$dbm_query->add_meta_query('user_access', $user_id);
			
			return $dbm_query->get_query_args();
		}
		
		public function filter_encode_internalMessageGroup($encoded_data, $post_id, $data) {
			
			$post = get_post($post_id);
			$encoded_data['title'] = $post->post_title;
			
			$user_ids = get_post_meta($post_id, 'user_access', false);
			
			$encoded_users = array();
			foreach($user_ids as $user_id) {
				$encoded_users[] = wprr_encode_user(get_user_by('id', $user_id));
			}
			
			$encoded_data['users'] = $encoded_users;
			
			$status_ids = dbm_get_post_relation($post_id, 'internal-message-group-status');
			if(count($status_ids) > 0) {
				$encoded_data['status'] = wprr_encode_term(get_term_by('id', $status_ids[0], 'dbm_relation'));
			}
			else {
				$encoded_data['status'] = null;
			}
			
			$type_slug = null;
			$type_ids = dbm_get_post_relation($post_id, 'internal-message-group-types');
			if(count($type_ids) > 0) {
				$type_term = get_term_by('id', $type_ids[0], 'dbm_relation');
				$type_slug = $type_term->slug;
				$encoded_data['type'] = wprr_encode_term($type_term);
				$encoded_data = apply_filters(DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_DOMAIN.'/encode-internal-message-group/'.$type_slug, $encoded_data, $post_id, $type_slug, $data);
			}
			else {
				$encoded_data['type'] = null;
			}
			
			$encoded_data = apply_filters(DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_DOMAIN.'/encode-internal-message-group', $encoded_data, $post_id, $type_slug, $data);
			
			return $encoded_data;
		}
		
		public function filter_encode_messagesInGroup($encoded_data, $post_id, $data) {
			
			$message_ids = dbm_new_query('dbm_data')->add_type_by_path('internal-message')->set_argument('post_status', 'private')->set_argument('order', 'ASC')->add_relations_from_post($post_id, 'internal-message-groups')->get_post_ids();
			
			$encoded_messages = array();
			foreach($message_ids as $message_id) {
				$encoded_message = array();
				
				$encoded_message['id'] = $message_id;
				$encoded_message['body'] = apply_filters('the_content', get_post_field('post_content', $message_id));
				$encoded_message['date'] = get_the_date('Y-m-d H:i:s', $message_id);
				$encoded_message['user'] = wprr_encode_user(get_user_by('id', get_post_field('post_author', $message_id)));
				
				$type_ids = dbm_get_post_relation($message_id, 'internal-message-types');
				$type_id = 0;
				$type_slug = null;
				if(count($type_ids) > 0) {
					$type_id = $type_ids[0];
					$encoded_message['type'] = wprr_encode_term(get_term_by('id', $type_id, 'dbm_relation'));
					$type_slug = $encoded_message['type']['slug'];
					$encoded_message = apply_filters(DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_DOMAIN.'/encode-internal-message/'.$type_slug, $encoded_message, $message_id, $type_slug, $data);
				}
				else {
					$encoded_message['type'] = null;
				}
				
				$encoded_message = apply_filters(DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_DOMAIN.'/encode-internal-message', $encoded_message, $message_id, $type_slug, $data);
				
				$encoded_messages[] = $encoded_message;
			}
			
			$encoded_data['messages'] = $encoded_messages;
			
			return $encoded_data;
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\CustomRangeFilters<br />");
		}
	}
?>
