<?php
	namespace DbmContentTransactionalCommunication;
	
	use \WP_Query;
	
	// \DbmContentTransactionalCommunication\ChangePostHooks
	class ChangePostHooks {
		
		function __construct() {
			//echo("\DbmContentTransactionalCommunication\ChangePostHooks::__construct<br />");
			
			
		}
		
		protected function register_hook_for_type($type, $hook_name) {
			add_action('wprr/admin/change_post/'.$type, array($this, $hook_name), 10, 3);
		}
		
		public function register() {
			//echo("\DbmContentTransactionalCommunication\ChangePostHooks::register<br />");
			
			$this->register_hook_for_type('dbmtc/commentChange', 'hook_dbmtc_commentChange');
			
		}
		
		public function hook_dbmtc_commentChange($data, $post_id, &$logger) {
			var_dump('\DbmContentTransactionalCommunication\ChangePostHooks::hook_dbmtc_commentChange');
			
			$internal_message_group = dbmtc_get_internal_message_group($post_id);
			
			$message = $internal_message_group->create_message('internal-message-types/change-comment', $data['comment'], get_current_user_id());
			$message->update_meta('changeType', $data['type']);
			$message->update_meta('field', $data['field']);
			$message->update_meta('newValue', $data['value']);
			$message->update_meta('oldValue', $data['oldValue']);
			
			$logger->add_return_data('messageId', $message->get_id());
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\ChangePostHooks<br />");
		}
	}
?>