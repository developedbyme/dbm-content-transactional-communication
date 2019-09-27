<?php
	namespace DbmContentTransactionalCommunication;

	class InternalMessageGroup {

		protected $id = array();

		function __construct($id) {
			//echo("\DbmContentTransactionalCommunication\InternalMessageGroup::__construct<br />");
			
			$this->id = $id;
		}
		
		public function get_group_term_id() {
			$group_term = dbm_get_owned_relation($this->id, 'internal-message-group');
			if(!$group_term) {
				return 0;
			}
			
			return $group_term->term_id;
		}
		
		public function add_type_to_post() {
			echo("\DbmContentTransactionalCommunication\InternalMessageGroup::add_type_to_post<br />");
			$dbm_post = dbm_get_post($this->id);
			$dbm_post->add_type_by_name('internal-message-group');
			
			//MENOTE: resaving it triggers setup of owned relations
			$args = array(
				'ID' => $this->id
			);
			
			wp_update_post($args);
			
			$group_term = $dbm_post->dbm_get_owned_relation('internal-message-group');
			if(!$group_term) {
				return 0;
			}
			return $group_term->term_id;
		}
		
		public function ensure_group_term_id_exists() {
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
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\InternalMessageGroup<br />");
		}
	}
?>
