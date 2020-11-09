<?php
	namespace DbmContentTransactionalCommunication\IntervalAction;
	
	// \DbmContentTransactionalCommunication\IntervalAction\IntervalAction
	class IntervalAction extends \DbmContent\DbmPost {
		
		function __construct($id) {
			//echo("\DbmContentTransactionalCommunication\IntervalAction\IntervalAction::__construct<br />");
			parent::__construct($id);
		}
		
		public function get_time() {
			return $this->get_meta('time');
		}
		
		public function get_action() {
			return $this->get_meta('action');
		}
		
		public function set_time($time) {
			$this->update_meta('time', $time);
			
			return $this;
		}
		
		public function set_action($action) {
			$this->update_meta('action', $action);
			
			return $this;
		}
		
		public function set_action_data($data) {
			$this->update_meta('actionData', $data);
			
			return $this;
		}
		
		protected function perform() {
			$action = $this->get_action();
			
			//MTODO: update next time
			
			$this->add_meta('dbmtc_performed_at', time());
			do_action('dbmtc/timed_action/'.$action, $this);
			
			return $this;
		}
		
		public function try_to_perform() {
			
			$current_time = time();
			
			
			$this->perform();
			
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
			echo("Imported \DbmContentTransactionalCommunication\IntervalAction\IntervalAction<br />");
		}
	}
?>
