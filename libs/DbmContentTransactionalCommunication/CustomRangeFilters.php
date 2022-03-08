<?php
	namespace DbmContentTransactionalCommunication;

	class CustomRangeFilters {

		function __construct() {
			//echo("\DbmContentTransactionalCommunication\CustomRangeFilters::__construct<br />");
		}
		
		public function register() {
			//echo("\DbmContentTransactionalCommunication\CustomRangeFilters::register<br />");
			
			add_filter('wprr/range_query/internalMessageGroup', array($this, 'filter_query_internalMessageGroup'), 10, 2);
			add_filter('wprr/range_query/allInternalMessageGroups', array($this, 'filter_query_allInternalMessageGroups'), 10, 2);
			add_filter('wprr/range_query/myInternalMessageGroups', array($this, 'filter_query_myInternalMessageGroups'), 10, 2);
			add_filter('wprr/range_query/groupsWithUser', array($this, 'filter_query_groupsWithUser'), 10, 2);
			add_filter('wprr/range_query/messagesInGroup', array($this, 'filter_query_messagesInGroup'), 10, 2);
			
			add_filter('wprr/range_encoding/internalMessageGroup', array($this, 'filter_encode_internalMessageGroup'), 10, 3);
			add_filter('wprr/range_encoding/messagesInGroup', array($this, 'filter_encode_messagesInGroup'), 10, 3);
			add_filter('wprr/range_encoding/messagesCount', array($this, 'filter_encode_messagesCount'), 10, 3);
			add_filter('wprr/range_encoding/message', array($this, 'filter_encode_message'), 10, 3);
			add_filter('wprr/range_encoding/fields', array($this, 'filter_encode_fields'), 10, 3);
			add_filter('wprr/range_encoding/fieldsWithChanges', array($this, 'filter_encode_fieldsWithChanges'), 10, 3);
			add_filter('wprr/range_encoding/fieldValues', array($this, 'filter_encode_fieldValues'), 10, 3);
			add_filter('wprr/range_encoding/linkGroup', array($this, 'filter_encode_linkGroup'), 10, 3);
			
			add_filter(DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_DOMAIN.'/encode-internal-message/change-comment', array($this, 'filter_encode_internal_message_group_change_comment'), 10, 2);
			add_filter(DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_DOMAIN.'/encode-internal-message/user-assigned', array($this, 'filter_encode_internal_message_user_assigned'), 10, 2);
			add_filter(DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_DOMAIN.'/encode-internal-message/user-unassigned', array($this, 'filter_encode_internal_message_user_unassigned'), 10, 2);
			add_filter(DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_DOMAIN.'/encode-internal-message/request-for-data', array($this, 'filter_encode_internal_message_request_for_data'), 10, 2);
			add_filter(DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_DOMAIN.'/encode-internal-message/field-changed', array($this, 'filter_encode_internal_message_field_changed'), 10, 2);
			add_filter(DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_DOMAIN.'/encode-internal-message/verify-mobile-phone-field', array($this, 'filter_encode_internal_message_verify_mobile_phone_field'), 10, 2);
			
			add_filter('wprr/global-item/processActions', array($this, 'filter_global_processActions'), 10, 3);
		}
		
		public function filter_query_groupsWithUser($query_args, $data) {
			//echo("\DbmContentTransactionalCommunication\CustomRangeFilters::filter_query_groupsWithUser<br />");
			
			$user_id = (int)$data['withUser'];
			
			$dbm_query = dbm_new_query($query_args);
			$dbm_query->add_meta_query('user_access', $user_id);
			
			return $dbm_query->get_query_args();
		}
		
		public function filter_query_messagesInGroup($query_args, $data) {
			//echo("\DbmContentTransactionalCommunication\CustomRangeFilters::filter_query_messagesInGroup<br />");
			
			$group_id = (int)$data['group'];
			
			$dbm_query = dbm_new_query($query_args);
			
			$group = dbmtc_get_group($group_id);
			$dbm_query->set_argument('post__in', $group->get_message_ids());
			
			return $dbm_query->get_query_args();
		}
		
		public function filter_query_internalMessageGroup($query_args, $data) {
			//echo("\DbmContentTransactionalCommunication\CustomRangeFilters::filter_query_internalMessageGroup<br />");
			
			$current_user_id = get_current_user_id();
			$group_id = (int)$data['groupId'];
			$user_access = get_post_meta($group_id, 'user_access');
			
			$has_access_to_post = in_array($current_user_id, $user_access);
			
			$has_permissions = apply_filters('dbmtc/has_permission_to_view_internal_messages', (current_user_can('read_private_posts') || $has_access_to_post));
			
			
			if(!$has_permissions) {
				throw(new \Exception("User doesn't have permission permission"));
			}
			
			$dbm_query = dbm_new_query($query_args);
			$dbm_query->add_type_by_path('internal-message-group');
			$dbm_query->set_argument('post_status', array('publish', 'private'));
			
			
			$dbm_query->set_argument('post__in', array($group_id));
			
			return $dbm_query->get_query_args();
		}
		
		public function filter_query_allInternalMessageGroups($query_args, $data) {
			//echo("\DbmContentTransactionalCommunication\CustomRangeFilters::filter_query_allInternalMessageGroups<br />");
			
			$current_user_id = get_current_user_id();
			
			$has_permissions = apply_filters('dbmtc/has_permission_to_view_internal_messages', current_user_can('read_private_posts'));
			
			if(!$has_permissions) {
				throw(new \Exception("User doesn't have permission permission"));
			}
			
			$dbm_query = dbm_new_query($query_args);
			$dbm_query->add_type_by_path('internal-message-group');
			$dbm_query->set_argument('post_status', array('publish', 'private'));
			
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
			$dbm_query->set_argument('post_status', array('publish', 'private'));
			$dbm_query->add_meta_query('user_access', $user_id);
			
			return $dbm_query->get_query_args();
		}
		
		protected function encode_users($user_ids) {
			$encoded_users = array();
			foreach($user_ids as $user_id) {
				$encoded_users[] = wprr_encode_user(get_user_by('id', $user_id));
			}
			return $encoded_users;
		}
		
		protected function _encode_field($post_id, $include_changes = 0) {
			$return_object = array();
			
			$field = new \DbmContentTransactionalCommunication\InternalMessageGroupField($post_id);
			
			$cached_value = $field->get_cached_value('encodedItem');
			if($cached_value) {
				$return_object = $cached_value;
			}
			else {
				wprr_performance_tracker()->start_meassure('_encode_field (uncached)');
				$return_object['key'] = $field->get_key();
				$return_object['value'] = $field->get_value();
				$return_object['translations'] = $field->get_translations();
				$return_object['type'] = wprr_encode_term($field->get_type_term());
				$return_object['status'] = wprr_encode_term($field->get_status_term());
			
				$return_object = apply_filters('dbmtc/encode_field/'.$field->get_type(), $return_object, $field);
				
				$field->set_cached_value('encodedItem', $return_object);
				wprr_performance_tracker()->stop_meassure('_encode_field (uncached)');
			}
			
			if($include_changes) {
				$return_object['pastChanges'] = $field->get_past_changes();
				$return_object['futureChanges'] = $field->get_future_changes();
			}
			
			return $return_object;
		}
		
		protected function _encode_field_from_template($field) {
			$return_object = array();
			
			$return_object['key'] = $field->get_key();
			$return_object['value'] = $field->get_value();
			
			$return_object['type'] = wprr_encode_term($field->get_type_term());
			
			$return_object = apply_filters('dbmtc/encode_field/'.$field->get_type(), $return_object, $field);
			
			return $return_object;
		}
		
		public function get_fields($post_id, $include_changes = 0) {
			$encoded_fields = array();
			$keys = array();
			
			$message_group = dbmtc_get_internal_message_group($post_id);
			
			$cached_value = $message_group->get_cached_value('encodedFields');
			if($cached_value) {
				$encoded_fields = $cached_value;
			}
			else {
				$fields_ids = $message_group->get_fields_ids();
				
				wprr_performance_tracker()->start_meassure('Range get_fields encode');
				
				foreach($fields_ids['single'] as $field_name => $field_id) {
					$current_encoded_field = $this->_encode_field($field_id, $include_changes);
					$encoded_fields[] = $current_encoded_field;
				}
			
				foreach($fields_ids['shared'] as $field_name => $field_id) {
					$field = new \DbmContentTransactionalCommunication\InternalMessageGroupField($field_id);
					$field->set_group_id_for_template($post_id);
				
					$current_encoded_field = $this->_encode_field_from_template($field);
					$encoded_fields[] = $current_encoded_field;
				}
				
				wprr_performance_tracker()->stop_meassure('Range get_fields encode');
				
				wprr_performance_tracker()->start_meassure('Range get_fields set cache');
				$message_group->set_cached_value('encodedFields', $encoded_fields);
				wprr_performance_tracker()->stop_meassure('Range get_fields set cache');
			}
			
			return $encoded_fields;
		}
		
		public function filter_encode_fields($encoded_data, $post_id, $data) {
			
			wprr_performance_tracker()->start_meassure('filter_encode_fields');
			$encoded_data['fields'] = $this->get_fields($post_id);
			wprr_performance_tracker()->stop_meassure('filter_encode_fields');
			
			return $encoded_data;
		}
		
		public function filter_encode_fieldsWithChanges($encoded_data, $post_id, $data) {
			
			$encoded_data['fields'] = $this->get_fields($post_id, 2);
			
			return $encoded_data;
		}
		
		public function filter_encode_fieldValues($encoded_data, $post_id, $data) {
			
			$fields = $this->get_fields($post_id);
			foreach($fields as $field) {
				$encoded_data[$field['key']] = $field['value'];
			}
			
			return $encoded_data;
		}
		
		protected function get_posts_from_link_group($links, &$return_array) {
			foreach($links as $link) {
				$url_parts = $link['urlParts'];
				foreach($url_parts as $url_part) {
					if($url_part['type'] === 'postUrl') {
						$id = $url_part['value'];
						if(!isset($return_array['post'.$id])) {
							$return_array['post'.$id] = wprr_encode_post_link($id);
						}
					}
				}
				
				$this->get_posts_from_link_group($link['childPaths'], $return_array);
			}
		}
		
		public function filter_encode_linkGroup($encoded_data, $post_id, $data) {
			$message_group = dbmtc_get_internal_message_group($post_id);
			
			$links = $message_group->get_field_value('links');
			
			$encoded_data['links'] = $links;
			
			$posts = array();
			$this->get_posts_from_link_group($links, $posts);
			
			$encoded_data['postsMap'] = $posts;
			
			return $encoded_data;
		}
		
		public function filter_encode_internalMessageGroup($encoded_data, $post_id, $data) {
			
			$post = get_post($post_id);
			$message_group = dbmtc_get_internal_message_group($post_id);
			$encoded_data['title'] = $post->post_title;
			
			$encoded_data['users'] = $this->encode_users($message_group->get_users_with_access());
			$encoded_data['notifiedUsers'] = $this->encode_users($message_group->get_users_to_notify());
			$encoded_data['assignedUsers'] = $this->encode_users($message_group->get_assigned_users());
			$encoded_data['dates'] = array(
				'started' => $message_group->get_started_date(),
				'updated' => $message_group->get_updated_date()
			);
			
			$status_ids = dbm_get_post_relation($post_id, 'internal-message-group-status');
			if(count($status_ids) > 0) {
				$encoded_data['status'] = wprr_encode_term(get_term_by('id', $status_ids[0], 'dbm_relation'));
			}
			else {
				$encoded_data['status'] = null;
			}
			
			$encoded_data['fields'] = $this->get_fields($post_id);
			
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
			
			$group = dbmtc_get_group($post_id);
			$message_ids = $group->get_message_ids();
			
			$encoded_messages = array();
			foreach($message_ids as $message_id) {
				$encoded_message = array();
				
				$encoded_message['id'] = $message_id;
				$encoded_message['body'] = apply_filters('the_content', get_post_field('post_content', $message_id));
				$encoded_message['date'] = get_the_date('Y-m-d H:i:s', $message_id);
				
				$author_id = (int)get_post_field('post_author', $message_id);
				if($author_id) {
					$encoded_message['user'] = wprr_encode_user(get_user_by('id', $author_id));
				}
				else {
					$encoded_message['user'] = null;
				}
				
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
		
		public function filter_encode_messagesCount($encoded_data, $post_id, $data) {
			
			$group = dbmtc_get_group($post_id);
			$message_ids = $group->get_message_ids();
			$encoded_data['numberOfMessages'] = count($message_ids);
			
			return $encoded_data;
		}
		
		public function filter_encode_message($encoded_data, $post_id, $data) {
			
			$message_id = $post_id;
			
			$encoded_data['id'] = $message_id;
			$encoded_data['body'] = apply_filters('the_content', get_post_field('post_content', $message_id));
			$encoded_data['date'] = get_the_date('Y-m-d H:i:s', $message_id);
			$encoded_data['user'] = wprr_encode_user(get_user_by('id', get_post_field('post_author', $message_id)));
			
			$type_ids = dbm_get_post_relation($message_id, 'internal-message-types');
			$type_id = 0;
			$type_slug = null;
			if(count($type_ids) > 0) {
				$type_id = $type_ids[0];
				$encoded_data['type'] = wprr_encode_term(get_term_by('id', $type_id, 'dbm_relation'));
				$type_slug = $encoded_data['type']['slug'];
				$encoded_data = apply_filters(DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_DOMAIN.'/encode-internal-message/'.$type_slug, $encoded_data, $message_id, $type_slug, $data);
			}
			else {
				$encoded_data['type'] = null;
			}
			
			$encoded_data = apply_filters(DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_DOMAIN.'/encode-internal-message', $encoded_data, $message_id, $type_slug, $data);
			
			
			return $encoded_data;
		}
		
		public function filter_encode_internal_message_group_change_comment($encoded_data, $message_id) {
			
			$encoded_data['oldValue'] = get_post_meta($message_id, 'oldValue', true);
			$encoded_data['newValue'] = get_post_meta($message_id, 'newValue', true);
			$encoded_data['changeType'] = get_post_meta($message_id, 'changeType', true);
			$encoded_data['field'] = get_post_meta($message_id, 'field', true);
			
			return $encoded_data;
		}
		
		public function filter_encode_internal_message_user_assigned($encoded_data, $message_id) {
			
			$user_id = (int)get_post_meta($message_id, 'assignedUser', true);
			
			$encoded_data['assignedUser'] = wprr_encode_user(get_user_by('id', $user_id));
			
			return $encoded_data;
		}
		
		public function filter_encode_internal_message_user_unassigned($encoded_data, $message_id) {
			
			$user_id = (int)get_post_meta($message_id, 'unassignedUser', true);
			
			$encoded_data['unassignedUser'] = wprr_encode_user(get_user_by('id', $user_id));
			
			return $encoded_data;
		}
		
		public function filter_encode_internal_message_request_for_data($encoded_data, $message_id) {
			
			$encoded_data['requestedData'] = get_post_meta($message_id, 'requestedData', true);
			$encoded_data['fields'] = get_post_meta($message_id, 'fields', true);
			
			return $encoded_data;
		}
		
		public function filter_encode_internal_message_field_changed($encoded_data, $message_id) {
			
			$encoded_data['field'] = get_post_meta($message_id, 'field', true);
			$encoded_data['oldValue'] = get_post_meta($message_id, 'oldValue', true);
			$encoded_data['newValue'] = get_post_meta($message_id, 'newValue', true);
			
			return $encoded_data;
		}
		
		public function filter_encode_internal_message_verify_mobile_phone_field($encoded_data, $message_id) {
			
			$encoded_data['field'] = get_post_meta($message_id, 'field', true);
			
			return $encoded_data;
		}
		
		public function filter_global_processActions($return_object, $item_name, $data) {
			
			wprr_performance_tracker()->start_meassure('CustomRangeHooks filter_global_processActions');
			
			$readyToProcess_id = dbmtc_get_or_create_type('type/action-status', 'readyToProcess');
			$processing_id = dbmtc_get_or_create_type('type/action-status', 'processing');
			$done_id = dbmtc_get_or_create_type('type/action-status', 'done');
			$noAction_id = dbmtc_get_or_create_type('type/action-status', 'noAction');
			
			$type_group = dbmtc_get_group($readyToProcess_id);
			
			$max_length = 10;
			
			wprr_performance_tracker()->start_meassure('CustomRangeHooks filter_global_processActions data get ids');
			$data_api = wprr_get_data_api();
			$data_post = $data_api->wordpress()->get_post($readyToProcess_id);
			$data_posts = $data_post->object_relation_query('out:for:action');
			$ids = array_map(function($post) {return $post->get_id();}, $data_posts);
			wprr_performance_tracker()->stop_meassure('CustomRangeHooks filter_global_processActions data get ids');
			
			//wprr_performance_tracker()->start_meassure('CustomRangeHooks filter_global_processActions get ids');
			//$ids = $type_group->object_relation_query('out:for:action');
			//wprr_performance_tracker()->stop_meassure('CustomRangeHooks filter_global_processActions get ids');
			
			sort($ids);
			$remaining_items_to_process = max(0, count($ids)-$max_length);
			$return_object['remaining'] = $remaining_items_to_process;
			$ids = array_slice($ids, 0, $max_length);
			$return_object['handled'] = $ids;
			
			$actions = array_map(function($id) {return dbmtc_get_group($id);}, $ids);
			
			foreach($actions as $action) {
				$action->end_incoming_relations_from_type('for', 'type/action-status');
				$action->add_incoming_relation_by_name($processing_id, 'for', time());
			}
			
			foreach($actions as $action) {
				$action_type = $action->get_single_object_relation_field_value('in:for:type/action-type', 'identifier');
				$hook_name = 'dbmtc/process_action/'.$action_type;
				
				if(has_action($hook_name)) {
					do_action($hook_name, $action->get_id());
					
					$action->end_incoming_relations_from_type('for', 'type/action-status');
					$action->add_incoming_relation_by_name($done_id, 'for', time());
				}
				else {
					$action->end_incoming_relations_from_type('for', 'type/action-status');
					$action->add_incoming_relation_by_name($noAction_id, 'for', time());
				}
				
			}
			
			wprr_performance_tracker()->stop_meassure('CustomRangeHooks filter_global_processActions');
			
			return $return_object;
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\CustomRangeFilters<br />");
		}
	}
?>
