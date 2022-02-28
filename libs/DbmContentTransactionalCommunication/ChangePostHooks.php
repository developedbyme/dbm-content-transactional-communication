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
			$this->register_hook_for_type('dbmtc/commentAction', 'hook_dbmtc_commentAction');
			$this->register_hook_for_type('dbmtc/comment', 'hook_dbmtc_comment');
			
			$this->register_hook_for_type('dbmtc/setFields', 'hook_dbmtc_setFields');
			$this->register_hook_for_type('dbmtc/setField', 'hook_dbmtc_setField');
			$this->register_hook_for_type('dbmtc/setFieldTranslations', 'hook_dbmtc_setFieldTranslations');
			
			$this->register_hook_for_type('dbmtc/removeFileFromField', 'hook_dbmtc_removeFileFromField');
			
			$this->register_hook_for_type('dbmtc/tag', 'hook_dbmtc_tag');
			$this->register_hook_for_type('dbmtc/untag', 'hook_dbmtc_untag');
			
			$this->register_hook_for_type('dbmtc/addSingleTrigger', 'hook_dbmtc_addSingleTrigger');
			$this->register_hook_for_type('dbmtc/addTrigger', 'hook_dbmtc_addTrigger');
			
			$this->register_hook_for_type('dbm/clearCache', 'change_clearCache');
		}
		
		protected function update_message_meta($message, $meta) {
			foreach($meta as $key => $value) {
				$message->update_meta($key, $value);
			}
		}
		
		public function hook_dbmtc_commentChange($data, $post_id, $logger) {
			//var_dump('\DbmContentTransactionalCommunication\ChangePostHooks::hook_dbmtc_commentChange');
			
			$internal_message_group = dbmtc_get_internal_message_group($post_id);
			
			$message = $internal_message_group->create_message('internal-message-types/change-comment', $data['comment'], get_current_user_id());
			$message->update_meta('changeType', $data['type']);
			$message->update_meta('field', $data['field']);
			$message->update_meta('newValue', $data['value']);
			$message->update_meta('oldValue', $data['oldValue']);
			
			if(isset($data['meta'])) {
				$this->update_message_meta($message, $data['meta']);
			}
			
			$logger->add_return_data('messageId', $message->get_id());
		}
		
		public function hook_dbmtc_commentAction($data, $post_id, $logger) {
			//var_dump('\DbmContentTransactionalCommunication\ChangePostHooks::hook_dbmtc_commentAction');
			
			$internal_message_group = dbmtc_get_internal_message_group($post_id);
			
			$message = $internal_message_group->create_message('internal-message-types/change-comment', $data['comment'], get_current_user_id());
			$message->update_meta('changeType', 'action');
			$message->update_meta('action', $data['action']);
			
			if(isset($data['meta'])) {
				$this->update_message_meta($message, $data['meta']);
			}
			
			$logger->add_return_data('messageId', $message->get_id());
		}
		
		public function hook_dbmtc_comment($data, $post_id, $logger) {
			//var_dump('\DbmContentTransactionalCommunication\ChangePostHooks::hook_dbmtc_comment');
			
			$internal_message_group = dbmtc_get_internal_message_group($post_id);
			
			$message = $internal_message_group->create_message('internal-message-types/comment', $data['comment'], get_current_user_id());
			
			if(isset($data['meta'])) {
				$this->update_message_meta($message, $data['meta']);
			}
			
			$logger->add_return_data('messageId', $message->get_id());
		}
		
		public function hook_dbmtc_setFields($data, $post_id, $logger) {
			//var_dump('\DbmContentTransactionalCommunication\ChangePostHooks::hook_dbmtc_setFields');
			
			$internal_message_group = dbmtc_get_internal_message_group($post_id);
			
			$fields = $data['value'];
			$comment = '';
			if(isset($data['comment']) && $data['comment']) {
				$comment = $data['comment'];
			}
			
			foreach($fields as $name => $value) {
				try {
					$internal_message_group->set_field_if_different($name, $value, $comment);
				}
				catch(\Exception $exception) {
					$logger->add_log($exception->getMessage());
				}
			}
		}
		
		public function hook_dbmtc_removeFileFromField($data, $post_id, $logger) {
			//var_dump('\DbmContentTransactionalCommunication\ChangePostHooks::hook_dbmtc_removeFileFromField');
			
			$internal_message_group = dbmtc_get_internal_message_group($post_id);
			$name = $data['field'];
			
			try {
				$field = $internal_message_group->get_field($name);
				$type = $field->get_type();
				
				switch($type) {
					case 'image':
					case 'file':
					case 'multiple-files':
						break;
					default:
						throw new \Exception('Field type '.$type.' doesn\'t support file handling');
				}
				
				$comment = '';
				if(isset($data['comment']) && $data['comment']) {
					$comment = $data['comment'];
				}
				
				$id_to_remove = $data['value'];
				$field_value = $field->get_value();
				
				if($type === 'multiple-files') {
					$new_field_value = array();
					foreach($field_value as $file) {
						if($file['id'] !== $id_to_remove) {
							$new_field_value[] = $file;
						}
					}
					$internal_message_group->set_field_if_different($name, $new_field_value, $comment);
				}
				else {
					if($field_value['id'] === $id_to_remove) {
						$internal_message_group->set_field_if_different($name, null, $comment);
					}
				}
			}
			catch(\Exception $exception) {
				$logger->add_log($exception->getMessage());
			}
		}
		
		public function hook_dbmtc_setField($data, $post_id, $logger) {
			//var_dump('\DbmContentTransactionalCommunication\ChangePostHooks::hook_dbmtc_setField');
			
			wprr_performance_tracker()->start_meassure('ChangePostHooks hook_dbmtc_setField');
			
			$internal_message_group = dbmtc_get_internal_message_group($post_id);
			$name = $data['field'];
			
			try {
				$comment = '';
				if(isset($data['comment']) && $data['comment']) {
					$comment = $data['comment'];
				}
				$internal_message_group->set_field_if_different($name, $data['value'], $comment);
			}
			catch(\Exception $exception) {
				$logger->add_log($exception->getMessage());
			}
			
			wprr_performance_tracker()->stop_meassure('ChangePostHooks hook_dbmtc_setField');
		}
		
		public function hook_dbmtc_setFieldTranslations($data, $post_id, $logger) {
			//var_dump('\DbmContentTransactionalCommunication\ChangePostHooks::hook_dbmtc_setFieldTranslations');
			
			$internal_message_group = dbmtc_get_internal_message_group($post_id);
			$name = $data['field'];
			
			try {
				$internal_message_group->set_field_translations($name, $data['value']);
			}
			catch(\Exception $exception) {
				$logger->add_log($exception->getMessage());
			}
		}
		
		public function hook_dbmtc_tag($data, $post_id, $logger) {
			//var_dump('\DbmContentTransactionalCommunication\ChangePostHooks::hook_dbmtc_tag');
			
			wprr_performance_tracker()->start_meassure('ChangePostHooks hook_dbmtc_tag');
			
			dbmtc_tag_item($post_id, $data['value']);
			
			wprr_performance_tracker()->stop_meassure('ChangePostHooks hook_dbmtc_tag');
		}
		
		public function hook_dbmtc_untag($data, $post_id, $logger) {
			//var_dump('\DbmContentTransactionalCommunication\ChangePostHooks::hook_dbmtc_untag');
			
			wprr_performance_tracker()->start_meassure('ChangePostHooks hook_dbmtc_untag');
			
			dbmtc_untag_item($post_id, $data['value']);
			
			wprr_performance_tracker()->stop_meassure('ChangePostHooks hook_dbmtc_untag');
		}
		
		public function hook_dbmtc_addTrigger($data, $post_id, $logger) {
			//var_dump('\DbmContentTransactionalCommunication\ChangePostHooks::hook_dbmtc_addTrigger');
			
			wprr_performance_tracker()->start_meassure('ChangePostHooks hook_dbmtc_addTrigger');
			
			$valid_for = -1;
			if(isset($data['validFor'])) {
				$valid_for = $data['validFor'];
			}
			
			dbmtc_add_trigger($post_id, $data['value'], $valid_for);
			
			wprr_performance_tracker()->stop_meassure('ChangePostHooks hook_dbmtc_addTrigger');
		}
		
		public function hook_dbmtc_addSingleTrigger($data, $post_id, $logger) {
			//var_dump('\DbmContentTransactionalCommunication\ChangePostHooks::hook_dbmtc_addSingleTrigger');
			
			wprr_performance_tracker()->start_meassure('ChangePostHooks hook_dbmtc_addSingleTrigger');
			
			$valid_for = -1;
			if(isset($data['validFor'])) {
				$valid_for = $data['validFor'];
			}
			
			dbmtc_add_single_trigger($post_id, $data['value'], $valid_for);
			
			wprr_performance_tracker()->stop_meassure('ChangePostHooks hook_dbmtc_addSingleTrigger');
		}
		
		public function change_clearCache($data, $post_id, $logger) {
			//echo("change_clearCache");
			
			$post = dbmtc_get_group($post_id);
			$post->clear_cache();
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\ChangePostHooks<br />");
		}
	}
?>