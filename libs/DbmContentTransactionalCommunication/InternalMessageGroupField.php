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
				$group_id = dbm_new_query('all')->set_field('post_type', get_post_types(array(), 'names'))->set_field('post_status', array('publish', 'pending', 'draft', 'future', 'private', 'inherit'))->add_type_by_path('internal-message-group')->add_relations_with_children_from_post($this->id, 'internal-message-groups')->get_post_id();
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
		
		public function set_value($value) {
			
			$storage_type_term = $this->get_storage_type_term();
			if($storage_type_term) {
				do_action('dbmtc/set_field_value/'.$storage_type_term->slug, $this, $value);
			}
			
			update_post_meta($this->id, 'dbmtc_value', $value);
			
			return $this;
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
