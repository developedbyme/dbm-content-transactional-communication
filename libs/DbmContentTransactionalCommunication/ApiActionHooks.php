<?php
	namespace DbmContentTransactionalCommunication;
	
	// \DbmContentTransactionalCommunication\ApiActionHooks
	class ApiActionHooks {

		function __construct() {
			//echo("\DbmContentTransactionalCommunication\ApiActionHooks::__construct<br />");
			
		}

		public function register() {
			//echo("\DbmContentTransactionalCommunication\ApiActionHooks::register<br />");

			add_action('wprr/api_action/send-email-verification', array($this, 'hook_send_email_verification'), 10, 2);
			add_action('wprr/api_action/verify-email', array($this, 'hook_verify_email'), 10, 2);
			add_action('wprr/api_action/internal-message/reply', array($this, 'hook_internal_message_reply'), 10, 2);
			
		}

		public function hook_send_email_verification($data, &$response_data) {
			//echo("\DbmContentTransactionalCommunication\ApiActionHooks::hook_send_email_verification<br />");
			
			$email = $data['email'];
			$code = mt_rand(100000, 999999);
			
			$replacements = array(
				'code' => $code,
				'email' => $email
			);
			
			$hash_salt = 'Tw?otIAwI%ourB-:@VeZ4tGLY0=Twh)1J Wwhxc!5AOg:*L$Ff@CAY+d-iW47Ztm';
			//METODO: add filter around salt
			$hash = md5($email.$hash_salt);
			
			$data_id = dbm_create_data('Email verification - '.$hash, 'address-verification', 'admin-grouping/address-verifications');
			update_post_meta($data_id, 'verification_hash', $hash);
			update_post_meta($data_id, 'verification_code', $code);
			update_post_meta($data_id, 'verified', false);
			
			wp_update_post(array(
				'ID' => $data_id,
				'post_status' => 'private'
			));
			
			$template_id = dbm_new_query('dbm_additional')->add_relation_by_path('global-transactional-templates/verify-email')->get_post_id();
			
			$template = dbm_content_tc_get_template_with_replacements($template_id, $replacements);
			$communication_id = dbm_content_tc_send_email($template['title'], $template['body'], $email, apply_filters('dbm_content_tc/default_from_email', get_option('admin_email')));
			
			update_post_meta($data_id, 'send_time', time());
			update_post_meta($data_id, 'communication_id', $communication_id);
			
			$response_data['sent'] = get_post_meta($communication_id, 'send_result', true);
			$response_data['verificationId'] = $data_id;
			//$response_data['hash'] = $hash;
		}
		
		public function hook_verify_email($data, &$response_data) {
			//echo("\DbmContentTransactionalCommunication\ApiActionHooks::hook_verify_email<br />");
			
			$result = false;
			
			$email = $data['email'];
			$data_id = $data['verificationId'];
			$code = $data['verificationCode'];
			
			$hash_salt = 'Tw?otIAwI%ourB-:@VeZ4tGLY0=Twh)1J Wwhxc!5AOg:*L$Ff@CAY+d-iW47Ztm';
			//METODO: add filter around salt
			$hash = md5($email.$hash_salt);
			
			$stored_hash = get_post_meta($data_id, 'verification_hash', true);
			$stored_code = get_post_meta($data_id, 'verification_code', true);
			
			if($stored_hash === $hash && $stored_code === $code) {
				update_post_meta($data_id, 'verified', true);
				$result = true;
			}
			
			$response_data['verified'] = $result;
		}
		
		public function hook_internal_message_reply($data, &$response_data) {
			//echo("\DbmContentTransactionalCommunication\ApiActionHooks::hook_internal_message_reply<br />");
			
			$group_id = $data['groupId'];
			$title = get_post($group_id)->post_title;
			$body = $data['body'];
			
			//METODO: check that current user is allowed
			
			$current_user = (int)$data['userId'];
			
			$message_id = dbm_content_tc_create_internal_message($title, $body, $current_user, $group_id);
			$response_data['messageId'] = $message_id;
			
			$communication_ids = dbm_content_tc_notify_for_new_message($message_id);
			$response_data['communicationIds'] = $communication_ids;
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\ApiActionHooks<br />");
		}
	}
?>
