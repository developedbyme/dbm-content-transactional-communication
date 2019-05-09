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
			
			$user_ids = get_post_meta($post_id, user_access, false);
			
			$encoded_users = array();
			foreach($user_ids as $user_id) {
				$encoded_users[] = wprr_encode_user(get_user_by('id', $user_id));
			}
			
			$encoded_data['users'] = $encoded_users; //METODO: encode users
			
			return $encoded_data;
		}
		
		public function filter_encode_messagesInGroup($encoded_data, $post_id, $data) {
			
			$message_ids = dbm_new_query('dbm_data')->add_type_by_path('internal-message')->set_argument('post_status', 'private')->add_relations_from_post($post_id, 'internal-message-groups')->get_post_ids();
			
			$encoded_messages = array();
			foreach($message_ids as $message_id) {
				$encoded_message = array();
				
				$encoded_message['body'] = apply_filters('the_content', get_post_field('post_content', $message_id));
				$encoded_message['date'] = get_the_date('Y-m-d h:i:s', $message_id);
				$encoded_message['user'] = wprr_encode_user(get_user_by('id', get_post_field('post_author', $message_id)));
				
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
