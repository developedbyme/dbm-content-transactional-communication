<?php
	namespace DbmContentTransactionalCommunication\TimedAction;
	
	// \DbmContentTransactionalCommunication\TimedAction\TimedAction
	class TimedAction extends \DbmContent\DbmPost {
		
		function __construct($id) {
			//echo("\DbmContentTransactionalCommunication\TimedAction\TimedAction::__construct<br />");
			parent::__construct($id);
		}
		
		public function get_time() {
			return $this->get_meta('dbmtc_time');
		}
		
		public function get_action() {
			return $this->get_meta('dbmtc_action');
		}
		
		public function get_action_data() {
			return $this->get_meta('dbmtc_action_data');
		}
		
		public function set_action_status($status) {
			$this->set_single_relation_by_name('timed-action-status/'.$status);
			
			return $this;
		}
		
		public function set_time($time) {
			$this->update_meta('dbmtc_time', $time);
			
			return $this;
		}
		
		public function set_action($action) {
			$this->update_meta('dbmtc_action', $action);
			
			return $this;
		}
		
		public function set_action_data($data) {
			$this->update_meta('dbmtc_action_data', $data);
			
			return $this;
		}
		
		protected function perform() {
			$action = $this->get_action();
			
			$this->set_action_status('completed');
			
			$this->add_meta('dbmtc_performed_at', time());
			do_action('dbmtc/timed_action/'.$action, $this);
			
			return $this;
		}
		
		public function try_to_perform() {
			
			$current_time = time();
			
			$status = get_term_by('id', $this->get_single_relation('timed-action-status'), 'dbm_relation');
			if($status->slug === 'waiting' && $current_time >= $this->get_time()) {
				$this->perform();
			}
			
			return $this;
		}
		
		public function force_perform() {
			
			$this->perform();
			
			return $this;
		}
		
		public function make_private() {
			$args = array(
				'ID' => $this->id,
				'post_status' => 'private'
			);
			
			wp_update_post($args);
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\TimedAction\TimedAction<br />");
		}
	}
?>
