<?php
	namespace DbmContentTransactionalCommunication;

	class ProcessActions {
		
		function __construct() {
			//echo("\DbmContentTransactionalCommunication\ProcessActions::__construct<br />");
		}
		
		protected function register_hook_for_type($type, $hook_name = null) {
			
			if(!$hook_name) {
				$hook_type = $type;
				$hook_type = implode('_', explode('/', $hook_type));
				$hook_name = 'hook_'.$hook_type;
			}
			
			add_action('dbmtc/process_action/'.$type, array($this, $hook_name), 10, 1);
		}

		public function register() {
			//echo("\DP\ProcessActions::register<br />");
			
			$this->register_hook_for_type('setStatus');
			$this->register_hook_for_type('importItem');
		}
		
		public function hook_setStatus($action_id) {
			$action = dbmtc_get_group($action_id);
			$action_data = $action->get_meta('value');
			
			$ids = $action->object_relation_query('out:from:*');
			foreach($ids as $id) {
				$dbm_post = dbmtc_get_group($id);
				
				if($action_data['status']) {
					$dbm_post->change_status($action_data['status']);
				}
				else {
					$action->update_meta('processLog', 'No status set');
				}
			}
		}
		
		public function hook_importItem($action_id) {
			$action = dbmtc_get_group($action_id);
			$import_item = dbmtc_get_group($action->object_relation_query('out:from:import-item')[0]);
			
			$type = $import_item->get_single_object_relation_field_value('in:for:type/import-type', 'identifier');
			
			do_action('dbmtc/import/handle_action/'.$type, $import_item, $action);
			
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\ProcessActions<br />");
		}
	}
?>
