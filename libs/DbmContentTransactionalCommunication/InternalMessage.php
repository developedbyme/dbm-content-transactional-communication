<?php
	namespace DbmContentTransactionalCommunication;

	class InternalMessage {

		protected $id = array();

		function __construct($id) {
			//echo("\DbmContentTransactionalCommunication\InternalMessage::__construct<br />");
			
			$this->id = $id;
		}
		
		public function get_id() {
			return $this->id;
		}
		
		public function update_meta($field, $value) {
			
			update_post_meta($this->id, $field, $value);
			
			return $this;
		}
		
		public function notify() {
			dbm_content_tc_notify_for_new_message($this->id);
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\InternalMessage<br />");
		}
	}
?>
