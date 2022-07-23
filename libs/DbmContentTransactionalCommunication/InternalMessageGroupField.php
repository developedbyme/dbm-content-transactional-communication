<?php
	namespace DbmContentTransactionalCommunication;

	class InternalMessageGroupField extends \DbmContent\DbmPost {

		protected $group_id = 0;

		function __construct($id) {
			//echo("\DbmContentTransactionalCommunication\InternalMessageGroupField::__construct<br />");
			
			parent::__construct($id);
			
			$this->id = $id;
		}
		
		public function get_group_id() {
			if(!$this->group_id) {
				
				$relation_ids = $this->resolve_outgoing_relations($this->get_encoded_outgoing_relations_by_type('field-for', null));
				if(!empty($relation_ids)) {
					$this->group_id = $relation_ids[0];
				}
				
				if(!$this->group_id) {
					//MENOTE: this way will be removed
					$post_types = array_keys(get_post_types(array(), 'names'));
					$group_id = dbm_new_query('all')->set_filter_suppression(1)->set_field('post_type', $post_types)->set_field('post_status', array('publish', 'pending', 'draft', 'future', 'private', 'inherit'))->add_type_by_path('internal-message-group')->add_relations_with_children_from_post($this->id, 'internal-message-groups')->get_post_id();
					$this->group_id = $group_id;
				}
			}
			
			return $this->group_id;
		}
		
		public function set_group_id_for_template($group_id) {
			$this->group_id = $group_id;
		}
		
		public function get_key() {
			return get_post_meta($this->id, 'dbmtc_key', true);
		}
		
		public function get_translations() {
			
			$translations = $this->get_cached_value('translations');
			if($translations === false) {
				$translations = get_post_meta($this->id, 'dbmtc_value_translations', true);
				if(!$translations) {
					$translations = array();
				}
				$this->set_cached_value('translations', $translations);
			}
			
			return $translations;
		}
		
		public function get_translated_value($language) {
			$translations = $this->get_translations();
			if(isset($translations[$language])) {
				return $translations[$language];
			}
			
			if(!$translations) {
				return $this->get_value();
			}
		}
		
		public function get_value() {
			
			$cached_value = $this->get_cached_value('value');
			if($cached_value !== false && false) {
				return $cached_value;
			}
			
			$return_value = $this->get_meta('dbmtc_value');
			
			$storage_type_term = $this->get_storage_type_term();
			if($storage_type_term) {
				$return_value = apply_filters('dbmtc/get_field_value/'.$storage_type_term->slug, $return_value, $this);
			}
			
			$type = $this->get_type();
			
			if($return_value === "") {
				$return_value = apply_filters('dbmtc/default_field_value/'.$type, $return_value, $this);
			}
			
			if($return_value) {
				$this->set_cached_value('value', $return_value);
			}
			else {
				$this->delete_cached_value('value');
			}
			
			return $return_value;
		}
		
		public function get_past_changes() {
			$return_value = $this->get_meta('dbmtc_past_changes');
			if(!$return_value) {
				return array(array(0, $this->get_value()));
			}
			
			return $return_value;
		}
		
		public function get_future_changes() {
			$return_value = $this->get_meta('dbmtc_future_changes');
			if(!$return_value) {
				return array();
			}
			
			return $return_value;
		}
		
		public function perform_set_value($value) {
			
			$storage_type_term = $this->get_storage_type_term();
			if($storage_type_term) {
				do_action('dbmtc/set_field_value/'.$storage_type_term->slug, $this, $value);
			}
			
			$this->update_meta('dbmtc_value', $value);
			$this->delete_cached_value('value');
			$this->delete_cached_value('encodedItem');
			dbmtc_get_internal_message_group($this->get_group_id())->delete_cached_value('encodedFields');
			
			return $this;
		}
		
		public function set_value($value) {
			//METODO: sort if this is on a timeline
			
			wprr_performance_tracker()->start_meassure('InternalMessageGroupField set_value');
			
			$this->perform_set_value($value);
			
			wprr_performance_tracker()->stop_meassure('InternalMessageGroupField set_value');
			
			return $this;
		}
		
		public function set_translations($value) {
			
			$this->update_meta('dbmtc_value_translations', $value);
			$this->delete_cached_value('translations');
			
			return $this;
		}
		
		public function ensure_initial_value() {
			$return_value = $this->get_meta('dbmtc_past_changes');
			if(!$return_value) {
				$this->update_meta('dbmtc_past_changes', array(array(0, $this->get_value())));
			}
			
			return $this;
		}
		
		protected function _add_time_change_to_array($value, $time, &$changes) {
			$is_inserted = false;
			foreach($changes as $index => &$change) {
				if($change[0] === $time) {
					$change[1] = $value;
					$is_inserted = true;
					break;
				}
				if($change[0] > $time) {
					array_splice($changes, $index, 0, array($time, $value));
					$is_inserted = true;
					break;
				}
			}
			
			if(!$is_inserted) {
				array_push($changes, array($time, $value));
			}
			
			return $changes;
		}
		
		public function set_history_change($value, $time) {
			$past_changes = $this->get_past_changes();
			$this->_add_time_change_to_array($value, $time, $past_changes);
			
			$this->update_meta('dbmtc_past_changes', $past_changes);
			//METODO: if it's the last change update the current value
			
			$last_value = $past_changes[count($past_changes)-1][1];
			$current_value = $this->get_value();
			
			if($last_value !== $current_value) {
				$this->perform_set_value($last_value);
			}
			
			return $this;
		}
		
		public function set_future_change($value, $time) {
			
			$future_changes = $this->get_future_changes();
			
			if(empty($future_changes) || $future_changes[0][0] > $time) {
				$this->ensure_initial_value();
				
				$future_changes = array_merge(array(array($time, $value)), $future_changes);
				dbmtc_create_timed_action($time, 'update_field_timeline', array('field' => $this->get_id()));
			}
			else {
				$this->_add_time_change_to_array($value, $time, $future_changes);
			}
			
			$this->update_meta('dbmtc_future_changes', $future_changes);
			
			return $this;
		}
		
		public function update_to_next_value() {
			$past_changes = $this->get_past_changes();
			$future_changes = $this->get_future_changes();
			
			$current_time = time();
			
			$changes = array();
			foreach($future_changes as $index => $change) {
				if($change[0] <= $current_time) {
					$changes[] = $change;
				}
				else {
					break;
				}
			}
			
			if(!empty($changes)) {
				array_splice($future_changes, 0, count($changes));
				
				$past_changes = array_merge($past_changes, $changes);
				$next_value = $changes[count($changes)-1][1];
				
				$this->perform_set_value($next_value);
				$this->update_meta('dbmtc_past_changes', $past_changes);
				$this->update_meta('dbmtc_future_changes', $future_changes);
				
				if(!empty($future_changes)) {
					dbmtc_create_timed_action($future_changes[0][0], 'update_field_timeline', array('field' => $this->get_id()));
				}
			}
		}
		
		public function update_meta($field, $value) {
			
			update_post_meta($this->id, $field, $value);
			
			return $this;
		}
		
		public function get_meta($field) {
			return get_post_meta($this->id, $field, true);
		}
		
		public function set_status($status) {
			dbm_set_single_relation_by_name($this->id, 'field-status', $status);
			
			return $this;
		}
		
		public function get_status_term() {
			$term_id = dbm_get_single_post_relation($this->id, 'field-status');
			
			if($term_id) {
				return get_term_by('id', $term_id, 'dbm_relation');
			}
			
			return null;
		}
		
		public function set_type($type) {
			dbm_set_single_relation_by_name($this->id, 'field-type', $type);
			
			return $this;
		}
		
		public function get_type() {
			$term_id = dbm_get_single_post_relation($this->id, 'field-type');
			
			if($term_id) {
				$term = get_term_by('id', $term_id, 'dbm_relation');
				return $term->slug;
			}
			
			return 'none';
		}
		
		public function get_type_term() {
			$term_id = dbm_get_single_post_relation($this->id, 'field-type');
			
			if($term_id) {
				return get_term_by('id', $term_id, 'dbm_relation');
			}
			
			return null;
		}
		
		public function set_storage_type($type) {
			if($type) {
				dbm_set_single_relation_by_name($this->id, 'field-storage', $type);
			}
			//METODO: remove storage type otherwise
			
			return $this;
		}
		
		public function get_storage_type() {
			$term_id = dbm_get_single_post_relation($this->id, 'field-storage');
			
			if($term_id) {
				$term = get_term_by('id', $term_id, 'dbm_relation');
				return $term->slug;
			}
			
			return null;
		}
		
		public function get_storage_type_term() {
			$term_id = dbm_get_single_post_relation($this->id, 'field-storage');
			
			if($term_id) {
				return get_term_by('id', $term_id, 'dbm_relation');
			}
			
			return null;
		}
		
		public function get_cache_key_prefix() {
			return 'dbmtc/img/'.$this->get_group_id().'/field/'.$this->get_id().'/';
		}
		
		public function get_cache_key($key) {
			return $this->get_cache_key_prefix().$key;
		}
		
		public function get_cached_value($key) {
			
			//return false; //METODO: set this as dev settings
			wprr_performance_tracker()->start_meassure('InternalMessageGroupField get_cached_value');
			
			$cache_key = $this->get_cache_key($key);
			$transient = get_transient($cache_key);
			wprr_performance_tracker()->stop_meassure('InternalMessageGroupField get_cached_value');
			
			return $transient;
		}
		
		public function set_cached_value($key, $value) {
			wprr_performance_tracker()->start_meassure('InternalMessageGroupField set_cached_value');
			$cache_key = $this->get_cache_key($key);
			set_transient($cache_key, $value, 48 * HOUR_IN_SECONDS);
			
			wprr_performance_tracker()->stop_meassure('InternalMessageGroupField set_cached_value');
			
			return $this;
		}
		
		public function delete_cached_value($key) {
			wprr_performance_tracker()->start_meassure('InternalMessageGroupField delete_cached_value');
			$cache_key = $this->get_cache_key($key);
			delete_transient($cache_key);
			wprr_performance_tracker()->stop_meassure('InternalMessageGroupField delete_cached_value');
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\InternalMessageGroupField<br />");
		}
	}
?>
