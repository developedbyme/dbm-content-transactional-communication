<?php
	namespace DbmContentTransactionalCommunication;

	class InternalMessageGroup {

		protected $id = array();

		function __construct($id) {
			//echo("\DbmContentTransactionalCommunication\InternalMessageGroup::__construct<br />");
			
			$this->id = $id;
		}
		
		public function get_id() {
			return $this->id;
		}
		
		public function update_meta($field, $value) {
			
			update_post_meta($this->id, $field, $value);
			
			return $this;
		}
		
		public function get_group_term_id() {
			$group_term = dbm_get_owned_relation($this->id, 'internal-message-group');
			if(!$group_term) {
				return 0;
			}
			
			return $group_term->term_id;
		}
		
		public function get_users_to_notify() {
			$users = get_post_meta($group_id, 'users_to_notify', true);
			
			if(!$users) {
				$users = array();
			}
			
			return $users;
		}
		
		public function get_assigned_users() {
			$users = get_post_meta($this->id, 'assignedUsers', true);
			
			if(!$users) {
				$users = array();
			}
			
			return $users;
		}
		
		public function assign_user($user_id, $body = '', $by_user = 0) {
			$message = $this->create_message('internal-message-types/user-assigned', $body, $by_user);
			
			$message->update_meta('assignedUser', $user_id);
			
			$users = $this->get_assigned_users();
			$users[] = $user_id;
			update_post_meta($this->id, 'assignedUser', $users);
			
			return $message;
		}
		
		public function unassign_user($user_id, $body = '', $by_user = 0) {
			$message = $this->create_message('internal-message-types/user-unassigned', $body, $by_user);
			
			$message->update_meta('unassignedUser', $user_id);
			
			$users = $this->get_assigned_users();
			
			if(($key = array_search($user_id, $users)) !== false) {
				unset($users[$key]);
				update_post_meta($this->id, 'assignedUser', $users);
			}
			
			return $message;
		}
		
		public function add_notification_for_user($user_id) {
			$users = $this->get_users_to_notify();
			$users[] = $user_id;
			update_post_meta($this->id, 'users_to_notify', $users);
		}
		
		public function remove_notification_for_user($user_id) {
			$users = $this->get_users_to_notify();
			
			if(($key = array_search($user_id, $users)) !== false) {
				unset($users[$key]);
				update_post_meta($this->id, 'users_to_notify', $users);
			}
		}
		
		public function request_data($data, $body = '', $by_user = 0) {
			$message = $this->create_message('internal-message-types/request-for-data', $body, $by_user);
			
			$message->update_meta('requestedData', $data);
			
			return $message;
		}
		
		public function add_type_to_post() {
			//echo("\DbmContentTransactionalCommunication\InternalMessageGroup::add_type_to_post<br />");
			
			$post_id = $this->id;
			
			$dbm_post = dbm_get_post($post_id);
			$dbm_post->add_type_by_name('internal-message-group');
			
			$post = get_post($post_id);
			$dbm_content_object = dbm_get_content_object_for_type_and_relation($post_id);
			do_action('dbm_content/parse_dbm_content', $dbm_content_object, $post_id, $post);
			
			$group_term = $dbm_post->dbm_get_owned_relation('internal-message-group');
			
			if(!$group_term) {
				return 0;
			}
			return $group_term->term_id;
		}
		
		public function ensure_group_term_id_exists() {
			//echo("\DbmContentTransactionalCommunication\InternalMessageGroup::ensure_group_term_id_exists<br />");
			
			$group_term_id = $this->get_group_term_id();
			
			if(!$group_term_id) {
				return $this->add_type_to_post();
			}
			
			return $group_term_id;
		}
		
		public function create_standard_message($body, $from_user) {
			$this->create_message('internal-message-types/message', $body, $from_user);
		}
		
		public function create_message($type, $body, $from_user) {
			//echo("\DbmContentTransactionalCommunication\InternalMessageGroup::create_message<br />");
			$group_post = get_post($this->id);
			
			$new_id = dbm_create_data($group_post->post_title, 'internal-message', 'admin-grouping/internal-messages');
			
			$group_id = $this->ensure_group_term_id_exists();
			
			$parent_term = dbm_get_relation_by_path('internal-message-groups');
		
			dbm_replace_relations($new_id, $parent_term, array($group_id));
			
			$args = array(
				'ID' => $new_id,
				'post_content' => $body,
				'post_author' => $from_user,
				'post_status' => 'private'
			);
			
			dbm_add_post_relation($new_id, $type);
			
			wp_update_post($args);
			
			$message = dbmtc_get_internal_message($new_id);
			
			return $message;
		}
		
		public function get_view_url() {
			$id = dbm_get_global_page_id('view-internal-message');
			
			if(!$id) {
				return null;
			}
			$permalink = get_permalink($id);
			$permalink = add_query_arg('group', $this->id, $permalink);
			
			return $permalink;
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\InternalMessageGroup<br />");
		}
	}
?>
