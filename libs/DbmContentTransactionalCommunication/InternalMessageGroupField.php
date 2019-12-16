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
				$group_id = dbm_new_query('all')->set_field('post_type', get_post_types(array(), 'names'))->set_field('post_status', array('publish', 'private'))->add_type_by_path('internal-message-group')->add_relations_from_post($this->id, 'internal-message-groups')->get_post_id();
				$this->group_id = $group_id;
			}
			
			return $this->group_id;
		}
		
		public function get_key() {
			return get_post_meta($this->id, 'dbmtc_key', true);
		}
		
		public function get_value() {
			
			//METODO: check storage method
			
			return get_post_meta($this->id, 'dbmtc_value', true);
		}
		
		public function set_value($value) {
			
			//METODO: check storage method
			
			update_post_meta($this->id, 'dbmtc_value', $value);
			
			return $this;
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
		
		public function get_type_term() {
			$term_id = dbm_get_single_post_relation($this->id, 'field-type');
			
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
