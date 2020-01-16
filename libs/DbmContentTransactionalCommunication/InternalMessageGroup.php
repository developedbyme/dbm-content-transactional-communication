<?php
	namespace DbmContentTransactionalCommunication;

	class InternalMessageGroup {

		protected $id = 0;

		function __construct($id) {
			//echo("\DbmContentTransactionalCommunication\InternalMessageGroup::__construct<br />");
			
			$this->id = $id;
		}
		
		public function get_id() {
			return $this->id;
		}
		
		public function get_type() {
			$term_id = dbm_get_single_post_relation($this->id, 'internal-message-group-types');
			
			if($term_id) {
				$term = get_term_by('id', $term_id, 'dbm_relation');
				return $term->slug;
			}
			
			return 'none';
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
		
		public function get_users_with_access() {
			$users = get_post_meta($this->id, 'user_access', false);
			
			if(!$users) {
				$users = array();
			}
			
			return $users;
		}
		
		public function get_users_to_notify() {
			$users = get_post_meta($this->id, 'users_to_notify', true);
			
			if(!$users) {
				$users = array();
			}
			
			return $users;
		}
		
		public function get_assigned_users() {
			$users = get_post_meta($this->id, 'assigned_users', true);
			
			if(!$users) {
				$users = array();
			}
			
			return $users;
		}
		
		public function get_started_date() {
			return get_the_date('Y-m-d\TH:i:s', $this->id);
		}
		
		public function get_updated_date() {
			$meta_value = get_post_meta($this->id, 'updated_date', true);
			
			if($meta_value) {
				return $meta_value;
			}
			
			return $this->get_started_date();
		}
		
		public function get_field_id($key) {
			return dbm_new_query('dbm_data')->set_argument('post_status', 'private')->add_type_by_path('internal-message-group-field')->add_relations_with_children_from_post($this->id, 'internal-message-groups')->add_meta_query('dbmtc_key', $key)->get_post_id();
		}
		
		public function assign_user($user_id, $body = '', $by_user = 0) {
			$message = $this->create_message('internal-message-types/user-assigned', $body, $by_user);
			
			$message->update_meta('assignedUser', $user_id);
			
			$users = $this->get_assigned_users();
			$users[] = $user_id;
			update_post_meta($this->id, 'assigned_users', $users);
			$this->add_notification_for_user($user_id);
			$this->add_user_access($user_id);
			
			return $message;
		}
		
		public function unassign_user($user_id, $body = '', $by_user = 0) {
			$message = $this->create_message('internal-message-types/user-unassigned', $body, $by_user);
			
			$message->update_meta('unassignedUser', $user_id);
			
			$users = $this->get_assigned_users();
			
			if(($key = array_search($user_id, $users)) !== false) {
				unset($users[$key]);
				update_post_meta($this->id, 'assigned_users', $users);
			}
			$this->remove_notification_for_user($user_id);
			
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
		
		public function add_user_access($user_id) {
			add_post_meta($this->id, 'user_access', $user_id);
		}
		
		public function remove_user_access($user_id) {
			delete_post_meta($this->id, 'user_access', $user_id);
		}
		
		public function request_data($fields, $body = '', $by_user = 0) {
			$message = $this->create_message('internal-message-types/request-for-data', $body, $by_user);
			
			$message->update_meta('requestedData', $fields);
			
			$fields_keys = array();
			
			foreach($fields as $field) {
				$field_key = $field['field'];
				
				$fields_keys[] = $field_key;
				
				//METODO: check for field template
				
				$this->create_field($field_key, $field['type']);
			}
			
			$message->update_meta('fields', $fields_keys);
			
			return $message;
		}
		
		public function try_to_auto_assign() {
			$assigned_users = $this->get_assigned_users();
			if(empty($assigned_users)) {
				$new_assigned_user = apply_filters('dbmtc/auto_assigned_user_for_group', 0, $this->id, $this);
				if($new_assigned_user) {
					return $this->assign_user($new_assigned_user);
				}
			}
			
			return null;
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
			
			$this->relate_post_to_group($new_id);
			
			dbm_add_post_relation($new_id, $type);
			
			$args = array(
				'ID' => $new_id,
				'post_content' => $body,
				'post_author' => $from_user,
				'post_status' => 'private'
			);
			
			wp_update_post($args);
			
			$message = dbmtc_get_internal_message($new_id);
			
			$this->update_updated_date();
			
			return $message;
		}
		
		public function relate_post_to_group($post_id) {
			$group_id = $this->ensure_group_term_id_exists();
			$parent_term = dbm_get_relation_by_path('internal-message-groups');
			dbm_replace_relations($post_id, $parent_term, array($group_id));
			
			return $this;
		}
		
		public function create_field($key, $type, $value = null) {
			$field_id = $this->get_field_id_if_exists($key);
			if(!$field_id) {
				
				$group_post = get_post($this->id);
				
				$field_id = dbm_create_data($group_post->post_title.' - '.$key, 'internal-message-group-field', 'admin-grouping/internal-message-group-fields');
				$this->relate_post_to_group($field_id);
				update_post_meta($field_id, 'dbmtc_key', $key);
				
				$field = new \DbmContentTransactionalCommunication\InternalMessageGroupField($field_id);
				
				$field->set_type($type);
				
				do_action('dbmtc/setup_default_field_storage', $field);
				
				$field->set_value($value);
				
				$status = $value ? 'complete' : 'none';
				$field->set_status($status);
				
				$field->make_private();
				
				$this->update_updated_date();
				$this->update_name_after_field_change($field);
			}
			else {
				$field = new \DbmContentTransactionalCommunication\InternalMessageGroupField($field_id);
			}
			
			return $field;
		}
		
		public function create_field_from_template($key, $template) {
			$field_id = $this->get_field_id_if_exists($key);
			if(!$field_id) {
				
				$group_post = get_post($this->id);
				
				$field_id = dbm_create_data($group_post->post_title.' - '.$key, 'internal-message-group-field', 'admin-grouping/internal-message-group-fields');
				$this->relate_post_to_group($field_id);
				update_post_meta($field_id, 'dbmtc_key', $key);
				
				$field = new \DbmContentTransactionalCommunication\InternalMessageGroupField($field_id);
				
				$type = $template->get_type();
				$field->set_type($type);
				
				$storage_type = $template->get_storage_type();
				$field->set_storage_type($storage_type);
				
				do_action('dbmtc/copy_field_template_meta/'.$storage_type, $field, $template);
				do_action('dbmtc/copy_field_template_meta/type/'.$type, $field, $template);
				do_action('dbmtc/setup_default_field_storage', $field);
				
				$value = $template->get_value();
				$field->set_value($value);
				
				$status = $value ? 'complete' : 'none';
				$field->set_status($status);
				
				$field->make_private();
				
				$this->update_updated_date();
			}
			else {
				$field = new \DbmContentTransactionalCommunication\InternalMessageGroupField($field_id);
			}
			
			return $field;
		}
		
		public function set_field($key, $value) {
			$field = $this->get_field($key);
			if(!$field) {
				return null;
			}
			
			$original_value = $field->get_value();
			$field->set_value($value);
			
			$field->set_status('complete');
			
			$user_id = get_current_user_id();
			
			$message = $this->create_message('internal-message-types/field-changed', '', $user_id);
			$message->update_meta('field', $key);
			$message->update_meta('oldValue', $original_value);
			$message->update_meta('newValue', $value);
			
			do_action('dbmtc/internal_message/group_field_set', $this, $key, $value, $user_id, $message);
			
			$this->update_updated_date();
			$this->update_name_after_field_change($field);
			
			return $message;
		}
		
		public function get_field_id_if_exists($key) {
			
			$local_id = dbm_new_query('dbm_data')->set_argument('post_status', array('publish', 'private'))->add_type_by_path('internal-message-group-field')->add_relations_with_children_from_post($this->id, 'internal-message-groups')->add_meta_query('dbmtc_key', $key)->debug_print_query_args()->get_post_id();
			
			return $local_id;
		}
		
		public function get_field_template_if_exists($key) {
			$type_terms = get_the_terms($this->id, 'dbm_type');
			if($type_terms) {
				$type_term_ids = wp_list_pluck($type_terms, 'term_id');
				
				$shared_id = dbm_new_query('dbm_data')->set_argument('post_status', array('publish', 'private'))->add_type_by_path('field-template')->add_meta_query('dbmtc_for_type', $type_term_ids, 'IN', 'NUMERIC')->add_meta_query('dbmtc_key', $key)->get_post_id();
			
				if($shared_id) {
					$field = new \DbmContentTransactionalCommunication\InternalMessageGroupField($shared_id);
					$field->set_group_id_for_template($this->id);
					
					return $field;
				}
			}
			
			return null;
		}
		
		public function get_field($key) {
			
			$field_id = $this->get_field_id_if_exists($key);
			
			if(!$field_id) {
				
				$template = $this->get_field_template_if_exists($key);
				if($template) {
					return $this->create_field_from_template($key, $template);
				}
				
				trigger_error('No field for key '.$key, E_USER_ERROR);
				return null;
			}
			
			$field = new \DbmContentTransactionalCommunication\InternalMessageGroupField($field_id);
			
			return $field;
		}
		
		public function update_updated_date() {
			update_post_meta($this->id, 'updated_date', date('Y-m-d\TH:i:s'));
		}
		
		public function update_name_after_field_change($field) {
			do_action('dbmtc/internal_message/update_name_after_field_change', $this, $field);
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
