<?php
	namespace DbmContentTransactionalCommunication;

	class InternalMessageGroupField {

		protected $id = 0;
		protected $group_id = 0;

		function __construct($id) {
			//echo("\DbmContentTransactionalCommunication\InternalMessageGroupField::__construct<br />");
			
			$this->id = $id;
		}
		
		public function get_id() {
			return $this->id;
		}
		
		public function get_group_id() {
			if(!$this->group_id) {
				$post_types = array_keys(get_post_types(array(), 'names'));
				$group_id = dbm_new_query('all')->set_field('post_type', $post_types)->set_field('post_status', array('publish', 'pending', 'draft', 'future', 'private', 'inherit'))->add_type_by_path('internal-message-group')->add_relations_with_children_from_post($this->id, 'internal-message-groups')->get_post_id();
				$this->group_id = $group_id;
			}
			
			return $this->group_id;
		}
		
		public function set_group_id_for_template($group_id) {
			$this->group_id = $group_id;
		}
		
		public function get_key() {
			return get_post_meta($this->id, 'dbmtc_key', true);
		}
		
		public function get_value() {
			
			$return_value = get_post_meta($this->id, 'dbmtc_value', true);
			
			$storage_type_term = $this->get_storage_type_term();
			if($storage_type_term) {
				$return_value = apply_filters('dbmtc/get_field_value/'.$storage_type_term->slug, $return_value, $this);
			}
			
			$type = $this->get_type();
			
			if($return_value === "") {
				$return_value = apply_filters('dbmtc/default_field_value/'.$type, $return_value, $this);
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
			
			return $this;
		}
		
		public function set_value($value) {
			//METODO: sort if this is on a timeline
			
			$this->perform_set_value($value);
			
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
			dbm_set_single_relation_by_name($this->id, 'field-storage', $type);
			
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
		
		public function make_private() {
			$args = array(
				'ID' => $this->id,
				'post_status' => 'private'
			);
			
			wp_update_post($args);
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\InternalMessageGroupField<br />");
		}
	}
?>
