<?php
	namespace DbmContentTransactionalCommunication;

	class CronJobs {
		
		function __construct() {
			//echo("\DbmContentTransactionalCommunication\CronJobs::__construct<br />");
		}
		
		protected function register_call($name) {
			
			add_filter('wprr/global-item/cron/'.$name, array($this, 'prepare_cron_call'), 19, 1);
			add_filter('wprr/global-item/cron/'.$name, array($this, 'cron_'.$name), 20, 3);
			
			
		}
		
		public function register() {
			//echo("\DbmContentTransactionalCommunication\CronJobs::register<br />");
			
			//MENOTE: legacy without cron prefix
			add_filter('wprr/global-item/processActions', array($this, 'prepare_cron_call'), 19, 1);
			add_filter('wprr/global-item/processActions', array($this, 'cron_processActions'), 20, 3);
			
			$this->register_call('processActions');
			$this->register_call('checkActionDependencies');
			
			$this->register_call('removeOldTrashLogs');
			$this->register_call('removeOldDraftRelations');
			$this->register_call('removeOldActions');
			
			$this->register_call('emptyRelationsBin');
			$this->register_call('emptyDatasBin');
		}
		
		public function prepare_cron_call($return_object) {
			ignore_user_abort(true);
			set_time_limit(0);
			
			return $return_object;
		}
		
		public function cron_processActions($return_object, $item_name, $data) {
			
			wprr_performance_tracker()->start_meassure('CustomRangeHooks filter_global_processActions');
			
			$readyToProcess_id = dbmtc_get_or_create_type('type/action-status', 'readyToProcess');
			$queued_id = dbmtc_get_or_create_type('type/action-status', 'queued');
			$processing_id = dbmtc_get_or_create_type('type/action-status', 'processing');
			$done_id = dbmtc_get_or_create_type('type/action-status', 'done');
			$noAction_id = dbmtc_get_or_create_type('type/action-status', 'noAction');
			
			$type_group = dbmtc_get_group($readyToProcess_id);
			
			$max_length = 10;
			
			if(isset($data['numberOfActions'])) {
				$max_length = (int)$data['numberOfActions'];
			}
			
			wprr_performance_tracker()->start_meassure('CustomRangeHooks filter_global_processActions data get ids');
			$data_api = wprr_get_data_api();
			$query = $data_api->database()->new_select_query()->set_post_type('dbm_data')->include_private()->term_query_by_path('dbm_type', 'action')->meta_query('needsToProcess', '1');
			
			$all_ids = $query->get_ids();
			$ids = array();
			
			$status_post = $data_api->wordpress()->get_post($readyToProcess_id);
			
			foreach($all_ids as $id) {
				$post = $data_api->wordpress()->get_post($id);
				
				$statuses = $post->object_relation_query('in:for:type/action-status');
				
				if(in_array($status_post, $statuses)) {
					$ids[] = $id;
				}
			}
			wprr_performance_tracker()->stop_meassure('CustomRangeHooks filter_global_processActions data get ids');
			
			sort($ids);
			$remaining_items_to_process = max(0, count($ids)-$max_length);
			$return_object['remaining'] = $remaining_items_to_process;
			$ids = array_slice($ids, 0, $max_length);
			$return_object['handled'] = $ids;
			
			$actions = array_map(function($id) {return dbmtc_get_group($id);}, $ids);
			
			foreach($actions as $action) {
				$action->end_incoming_relations_from_type('for', 'type/action-status');
				$action->add_incoming_relation_by_name($queued_id, 'for', time());
			}
			
			foreach($actions as $action) {
				$action_type = $action->get_single_object_relation_field_value('in:for:type/action-type', 'identifier');
				$hook_name = 'dbmtc/process_action/'.$action_type;
				
				$action->end_incoming_relations_from_type('for', 'type/action-status');
				$action->add_incoming_relation_by_name($processing_id, 'for', time());
				
				if(has_action($hook_name)) {
					do_action($hook_name, $action->get_id());
					
					$action->end_incoming_relations_from_type('for', 'type/action-status');
					$action->add_incoming_relation_by_name($done_id, 'for', time());
				}
				else {
					$action->end_incoming_relations_from_type('for', 'type/action-status');
					$action->add_incoming_relation_by_name($noAction_id, 'for', time());
				}
				
				$action->update_meta('needsToProcess', false);
			}
			
			wprr_performance_tracker()->stop_meassure('CustomRangeHooks filter_global_processActions');
			
			return $return_object;
		}
		
		public function cron_checkActionDependencies($return_object, $item_name, $data) {
			
			wprr_performance_tracker()->start_meassure('CustomRangeHooks cron_checkActionDependencies');
			
			$readyToProcess_id = dbmtc_get_or_create_type('type/action-status', 'readyToProcess');
			$waitingForDependencies_id = dbmtc_get_or_create_type('type/action-status', 'waitingForDependencies');
			$dependenciesTimedOut_id = dbmtc_get_or_create_type('type/action-status', 'dependenciesTimedOut');
			$done_id = dbmtc_get_or_create_type('type/action-status', 'done');
			$noAction_id = dbmtc_get_or_create_type('type/action-status', 'noAction');
			
			wprr_performance_tracker()->start_meassure('CustomRangeHooks filter_global_processActions data get ids');
			$data_api = wprr_get_data_api();
			$query = $data_api->database()->new_select_query()->set_post_type('dbm_data')->include_private()->term_query_by_path('dbm_type', 'action')->meta_query('needsToProcess', '1');
			
			$all_ids = $query->get_ids();
			$check_ids = array();
			$timed_out_ids = array();
			
			$waitingForDependencies_post = $data_api->wordpress()->get_post($waitingForDependencies_id);
			$dependenciesTimedOut_post = $data_api->wordpress()->get_post($dependenciesTimedOut_id);
			$done_post = $data_api->wordpress()->get_post($done_id);
			$noAction_post = $data_api->wordpress()->get_post($noAction_id);
			
			foreach($all_ids as $id) {
				$post = $data_api->wordpress()->get_post($id);
				
				$statuses = $post->object_relation_query('in:for:type/action-status');
				
				if(in_array($waitingForDependencies_post, $statuses)) {
					$check_ids[] = $id;
				}
				else if(in_array($dependenciesTimedOut_post, $statuses)) {
					$timed_out_ids[] = $id;
				}
			}
			wprr_performance_tracker()->stop_meassure('CustomRangeHooks filter_global_processActions data get ids');
			
			$actions = array_map(function($id) {return dbmtc_get_group($id);}, $check_ids);
			
			$ids = array();
			$waiting = array();
			
			foreach($check_ids as $id) {
				
				$post = $data_api->wordpress()->get_post($id);
				$dependencies = $post->object_relation_query('in:for:group/dependencies,in:in:action');
				$is_completed = !empty($dependencies);
				
				foreach($dependencies as $dependency) {
					$statuses = $dependency->object_relation_query('in:for:type/action-status');
					if(!(in_array($done_post, $statuses) || in_array($noAction_post, $statuses))) {
						$is_completed = false;
						break;
					}
				}
				
				if($is_completed) {
					$edit_post = dbmtc_get_group($id);
					$edit_post->end_incoming_relations_from_type('for', 'type/action-status');
					$edit_post->add_incoming_relation_by_name($readyToProcess_id, 'for', time());
					
					$ids[] = $id;
				}
				else {
					$waiting[] = $id;
				}
			}
			
			$return_object['readyToProcess'] = $ids;
			$return_object['waiting'] = $waiting;
			
			foreach($timed_out_ids as $timed_out_id) {
				dbmtc_add_action_to_process('actionWithDependenciesTimedOut', array($timed_out_id));
				
				dbmtc_get_group($timed_out_id)->update_meta('needsToProcess', false);
			}
			
			$return_object['timedOut'] = $timed_out_ids;
			
			wprr_performance_tracker()->stop_meassure('CustomRangeHooks cron_checkActionDependencies');
			
			return $return_object;
		}
		
		public function cron_removeOldTrashLogs($return_object, $item_name, $data) {
			$data_api = wprr_get_data_api();
			$query = $data_api->database()->new_select_query()->set_post_type('dbm_data')->include_private()->term_query_by_path('dbm_type', 'trash-log');
			
			$before_date = date('Y-m-d', strtotime('-30 days'));
			$query->in_date_range("1970-01-01", $before_date);
			
			$ids = $query->get_ids();
			
			$chunks = array_chunk($ids, 10);
			
			foreach($chunks as $chunk) {
				dbmtc_add_action_to_process('removeItems', $chunk, array('source' => 'cron/removeOldTrashLogs', 'ids' => $chunk));
			}
		}
		
		public function cron_removeOldDraftRelations($return_object, $item_name, $data) {
			$data_api = wprr_get_data_api();
			$query = $data_api->database()->new_select_query()->set_post_type('dbm_object_relation')->set_status('draft');
			
			$before_date = date('Y-m-d', strtotime('-30 days'));
			$query->in_date_range("1970-01-01", $before_date);
			
			$ids = $query->get_ids();
			
			$chunks = array_chunk($ids, 10);
			
			foreach($chunks as $chunk) {
				dbmtc_add_action_to_process('removeItems', $chunk, array('source' => 'cron/removeOldDraftRelations', 'ids' => $chunk, 'skipLogs' => true));
			}
		}
		
		public function cron_removeOldActions($return_object, $item_name, $data) {
			$data_api = wprr_get_data_api();
			$query = $data_api->database()->new_select_query()->set_post_type('dbm_data')->include_private()->term_query_by_path('dbm_type', 'action');
			
			$query->meta_query('needsToProcess', false);
			
			$before_date = date('Y-m-d', strtotime('-90 days'));
			$query->in_date_range("1970-01-01", $before_date);
			
			$ids = $query->get_ids();
			
			$chunks = array_slice(array_chunk($ids, 10), 0, 20);
			
			foreach($chunks as $chunk) {
				dbmtc_add_action_to_process('removeItems', $chunk, array('source' => 'cron/removeOldActions', 'ids' => $chunk, 'skipLogs' => true));
			}
		}
		
		public function cron_emptyRelationsBin($return_object, $item_name, $data) {
			$data_api = wprr_get_data_api();
			$query = $data_api->database()->new_select_query()->set_post_type('dbm_object_relation')->set_status('trash');
			
			wprr_performance_tracker()->start_meassure('cron_emptyRelationsBin get ids');
			
			$remove_ids = $query->get_ids_with_limit(100);
			
			wprr_performance_tracker()->stop_meassure('cron_emptyRelationsBin get ids');
			
			wprr_performance_tracker()->start_meassure('cron_emptyRelationsBin trash');
			foreach($remove_ids as $remove_id) {
				wp_delete_post($remove_id, true);
			}
			wprr_performance_tracker()->stop_meassure('cron_emptyRelationsBin trash');
		}
		
		public function cron_emptyDatasBin($return_object, $item_name, $data) {
			$data_api = wprr_get_data_api();
			$query = $data_api->database()->new_select_query()->set_post_type('dbm_data')->set_status('trash');
			
			$remove_ids = $query->get_ids_with_limit(100);
			
			foreach($remove_ids as $remove_id) {
				wp_delete_post($remove_id, true);
			}
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\CronJobs<br />");
		}
	}
?>
