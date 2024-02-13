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
				
				$group = $this->data_api_post()->single_object_relation_query('out:field-for:*');
				if($group) {
					$this->group_id = $group->get_id();
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
			
			$translations = get_post_meta($this->id, 'dbmtc_value_translations', true);
			if(!$translations) {
				$translations = array();
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
			
			$return_value = $this->get_meta('dbmtc_value');
			
			$storage_type_term = $this->get_storage_type_term();
			if($storage_type_term) {
				$return_value = apply_filters('dbmtc/get_field_value/'.$storage_type_term->slug, $return_value, $this);
			}
			
			if($return_value === "") {
				$type = $this->get_type();
				$return_value = apply_filters('dbmtc/default_field_value/'.$type, $return_value, $this);
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
			
			wprr_performance_tracker()->start_meassure('InternalMessageGroupField set_value');
			
			$this->perform_set_value($value);
			
			wprr_performance_tracker()->stop_meassure('InternalMessageGroupField set_value');
			
			return $this;
		}
		
		public function set_translations($value) {
			
			$this->data_api_post()->editor()->update_meta('dbmtc_value_translations', $value);
			
			return $this;
		}
		
		public function ensure_initial_value() {
			$return_value = $this->get_meta('dbmtc_past_changes');
			if(!$return_value) {
				$this->update_meta('dbmtc_past_changes', array(array(0, $this->get_value())));
			}
			
			return $this;
		}
		
		public function set_status($status) {
			$this->data_api_post()->editor()->add_term_by_path('dbm_relation', 'field-status/'.$status);
			
			return $this;
		}
		
		public function get_status_term() {
			
			$parent_term = wprr_get_data_api()->wordpress()->get_taxonomy('dbm_relation')->get_term('field-status');
			$type_term = $this->data_api_post()->get_single_term_in($parent_term);
			if($type_term) {
				return get_term_by('id', $type_term->get_id(), 'dbm_relation');
			}
			
			return null;
		}
		
		public function set_type($type) {
			$this->data_api_post()->editor()->add_term_by_path('dbm_relation', 'field-type/'.$type);
			
			return $this;
		}
		
		public function get_type() {
			$parent_term = wprr_get_data_api()->wordpress()->get_taxonomy('dbm_relation')->get_term('field-type');
			$type_term = $this->data_api_post()->get_single_term_in($parent_term);
			if($type_term) {
				return $type_term->get_slug();
			}
			
			return 'none';
		}
		
		public function get_type_term() {
			$parent_term = wprr_get_data_api()->wordpress()->get_taxonomy('dbm_relation')->get_term('field-type');
			$type_term = $this->data_api_post()->get_single_term_in($parent_term);
			if($type_term) {
				return get_term_by('id', $type_term->get_id(), 'dbm_relation');
			}
			
			return null;
		}
		
		public function set_storage_type($type) {
			if($type) {
				$this->data_api_post()->editor()->add_term_by_path('dbm_relation', 'field-storage/'.$type);
			}
			//METODO: remove storage type otherwise
			
			return $this;
		}
		
		public function get_storage_type() {
			
			$parent_term = wprr_get_data_api()->wordpress()->get_taxonomy('dbm_relation')->get_term('field-storage');
			$type_term = $this->data_api_post()->get_single_term_in($parent_term);
			if($type_term) {
				return $type_term->get_slug();
			}
			
			return null;
		}
		
		public function get_storage_type_term() {
			$parent_term = wprr_get_data_api()->wordpress()->get_taxonomy('dbm_relation')->get_term('field-storage');
			$type_term = $this->data_api_post()->get_single_term_in($parent_term);
			if($type_term) {
				return get_term_by('id', $type_term->get_id(), 'dbm_relation');
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
			$transient = get_post_meta($this->get_id(), $cache_key, true);
			wprr_performance_tracker()->stop_meassure('InternalMessageGroupField get_cached_value');
			
			return $transient;
		}
		
		public function set_cached_value($key, $value) {
			wprr_performance_tracker()->start_meassure('InternalMessageGroupField set_cached_value');
			$cache_key = $this->get_cache_key($key);
			update_post_meta($this->get_id(), $cache_key, $value);
			
			wprr_performance_tracker()->stop_meassure('InternalMessageGroupField set_cached_value');
			
			return $this;
		}
		
		public function delete_cached_value($key) {
			wprr_performance_tracker()->start_meassure('InternalMessageGroupField delete_cached_value');
			$cache_key = $this->get_cache_key($key);
			delete_post_meta($this->get_id(), $cache_key);
			wprr_performance_tracker()->stop_meassure('InternalMessageGroupField delete_cached_value');
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\InternalMessageGroupField<br />");
		}
	}
?>
