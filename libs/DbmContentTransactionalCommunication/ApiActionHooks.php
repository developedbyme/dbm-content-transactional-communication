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
			add_action('wprr/api_action/resend-email-verification', array($this, 'hook_resend_email_verification'), 10, 2);
			add_action('wprr/api_action/verify-email', array($this, 'hook_verify_email'), 10, 2);
			add_action('wprr/api_action/internal-message/reply', array($this, 'hook_internal_message_reply'), 10, 2);
			add_action('wprr/api_action/internal-message/close', array($this, 'hook_internal_message_close'), 10, 2);
			add_action('wprr/api_action/internal-message/re-open', array($this, 'hook_internal_message_re_open'), 10, 2);
			
			add_action('wprr/api_action/internal-message/change-assignment', array($this, 'hook_internal_message_change_assignment'), 10, 2);
			add_action('wprr/api_action/internal-message/request-data', array($this, 'hook_internal_message_request_data'), 10, 2);
			add_action('wprr/api_action/internal-message/set-field', array($this, 'hook_internal_message_set_field'), 10, 2);
			add_action('wprr/api_action/internal-message/verify-phone-number-field', array($this, 'hook_internal_message_verify_phone_number_field'), 10, 2);
			
			add_action('wprr/api_action/dbmtc/sendPasswordResetVerification', array($this, 'hook_sendPasswordResetVerification'), 10, 2);
			add_action('wprr/api_action/dbmtc/resendPasswordResetVerification', array($this, 'hook_resendPasswordResetVerification'), 10, 2);
			add_action('wprr/api_action/dbmtc/verifyResetPassword', array($this, 'hook_verifyResetPassword'), 10, 2);
			add_action('wprr/api_action/dbmtc/setPasswordWithVerification', array($this, 'hook_setPasswordWithVerification'), 10, 2);
			
			add_action('wprr/api_action/dbmtc/sendPhoneNumberVerification', array($this, 'hook_sendPhoneNumberVerification'), 10, 2);
			add_action('wprr/api_action/dbmtc/resendPhoneNumberVerification', array($this, 'hook_resendPhoneNumberVerification'), 10, 2);
			
			add_action('wprr/api_action/dbmtc/sendTwoFactorVerification', array($this, 'hook_sendTwoFactorVerification'), 10, 2);
			add_action('wprr/api_action/dbmtc/verifyVerification', array($this, 'hook_verifyVerification'), 10, 2);
			
			add_action('wprr/api_action/dbmtc/testMessageNotification', array($this, 'hook_testMessageNotification'), 10, 2);
			
			add_action('wprr/api_action/dbmtc/timedAction/tryToPerform', array($this, 'hook_timedAction_tryToPerform'), 10, 2);
			add_action('wprr/api_action/dbmtc/timedAction/checkTimedActions', array($this, 'hook_timedAction_checkTimedActions'), 10, 2);
			
			add_action('wprr/api_action/dbmtc/sendEmail', array($this, 'hook_sendEmail'), 10, 2);
			add_action('wprr/api_action/dbmtc/sendTestEmail', array($this, 'hook_sendTestEmail'), 10, 2);
			
			add_action('wprr/api_action/incomingWebhookEvent', array($this, 'hook_incomingWebhookEvent'), 10, 2);
			
			add_action('wprr/api_action/submitForm', array($this, 'hook_submitForm'), 10, 2);
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
		
		public function hook_resend_email_verification($data, &$response_data) {
			//echo("\DbmContentTransactionalCommunication\ApiActionHooks::hook_resend_email_verification<br />");
			
			$data_id = $data['verificationId'];
			$email = $data['email'];
			
			$hash_salt = 'Tw?otIAwI%ourB-:@VeZ4tGLY0=Twh)1J Wwhxc!5AOg:*L$Ff@CAY+d-iW47Ztm';
			//METODO: add filter around salt
			$hash = md5($email.$hash_salt);
			
			if($hash !== get_post_meta($data_id, 'verification_hash', true)) {
				throw new \Exception('Incorrect email');
			}
			
			$replacements = array(
				'code' => get_post_meta($data_id, 'verification_code', true),
				'email' => $email
			);
			
			$template_id = dbm_new_query('dbm_additional')->add_relation_by_path('global-transactional-templates/verify-email')->get_post_id();
			
			$template = dbm_content_tc_get_template_with_replacements($template_id, $replacements);
			$communication_id = dbm_content_tc_send_email($template['title'], $template['body'], $email, apply_filters('dbm_content_tc/default_from_email', get_option('admin_email')));
			
			add_post_meta($data_id, 'send_time', time());
			add_post_meta($data_id, 'communication_id', $communication_id);
			
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
		
		public function hook_verifyResetPassword($data, &$response_data) {
			//echo("\DbmContentTransactionalCommunication\ApiActionHooks::hook_verifyResetPassword<br />");
			
			$result = false;
			
			$user = $this->get_user($data['user']);
			$user_id = $user->ID;
			
			$data_id = $data['verificationId'];
			$code = $data['verificationCode'];
			
			$hash_salt = 'Tw?otIAwI%ourB-:@VeZ4tGLY0=Twh)1J Wwhxc!5AOg:*L$Ff@CAY+d-iW47Ztm';
			//METODO: add filter around salt
			$hash = md5($user_id.$hash_salt);
			
			$stored_hash = get_post_meta($data_id, 'verification_hash', true);
			$stored_code = get_post_meta($data_id, 'verification_code', true);
			
			if($stored_hash === $hash && $stored_code === $code) {
				
				$post = dbmtc_get_group($data_id);
				$current_status = $post->get_single_object_relation_field_value("in:for:type/verication-status", "identifier");
				
				if($current_status === 'unverified') {
					update_post_meta($data_id, 'verified', true);
					$result = true;
				
					$verified_id = dbmtc_get_or_create_type('type/verication-status', 'verified');
					$expired_id = dbmtc_get_or_create_type('type/verication-status', 'expired');
				
					$start_time = time();
					$minutes_until_expired = 5;
					$end_time = $start_time+60*$minutes_until_expired;
				
					$post->end_incoming_relations_from_type('for', 'type/verication-status');
					$type_relation = dbmtc_get_group($post->add_incoming_relation_by_name($verified_id, 'for'));
					$type_relation->data_api_post()->editor()->set_object_relation_field('startAt', $start_time);
					$type_relation->data_api_post()->editor()->set_object_relation_field('endAt', $end_time);
			
					$type_relation = dbmtc_get_group($post->add_incoming_relation_by_name($expired_id, 'for'));
					$type_relation->data_api_post()->editor()->set_object_relation_field('startAt', $end_time);
				}
				else if($current_status === 'verified') {
					$result = true;
				}
				else {
					throw new \Exception('Can\'t verify with status '.$current_status);
				}
			}
			
			$response_data['verified'] = $result;
		}
		
		public function hook_setPasswordWithVerification($data, &$response_data) {
			//echo("\DbmContentTransactionalCommunication\ApiActionHooks::hook_setPasswordWithVerification<br />");
			
			$result = false;
			
			$user = $this->get_user($data['user']);
			$user_id = $user->ID;
			$data_id = $data['verificationId'];
			
			$hash_salt = 'Tw?otIAwI%ourB-:@VeZ4tGLY0=Twh)1J Wwhxc!5AOg:*L$Ff@CAY+d-iW47Ztm';
			//METODO: add filter around salt
			$hash = md5($user_id.$hash_salt);
			
			$stored_hash = get_post_meta($data_id, 'verification_hash', true);
			
			if($stored_hash === $hash) {
				
				$post = dbmtc_get_group($data_id);
				$current_status = $post->get_single_object_relation_field_value("in:for:type/verication-status", "identifier");
				
				if($current_status === "verified") {
					$password = $data['password'];
				
					$user_id = (int)get_post_meta($data_id, 'user_id', true);
				
					wp_set_password($password, $user_id);
				
					$response_data['authenticated'] = true;
					$response_data['userId'] = $user_id;
				
					$encoder = new \Wprr\WprrEncoder();
				
					$user = get_user_by('id', $user_id);
					$response_data['user'] = $encoder->encode_user_with_private_data($user);
					$response_data['roles'] = $user->roles;
				
					$nonce_data = dbm_custom_login_perform_login($user);
				
					$response_data['restNonce'] = $nonce_data['restNonce'];
					$response_data['restNonceGeneratedAt'] = $nonce_data['restNonceGeneratedAt'];
				
					$used_id = dbmtc_get_or_create_type('type/verication-status', 'used');
				
					$post->end_incoming_relations_from_type('for', 'type/verication-status');
					$type_relation = $post->add_incoming_relation_by_name($used_id, 'for', time());
				}
				else {
					throw new \Exception('Can\'t reset with status '.$current_status);
				}
			}
			else {
				throw new \Exception('Not correct user');
			}
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
		
		public function hook_internal_message_close($data, &$response_data) {
			//echo("\DbmContentTransactionalCommunication\ApiActionHooks::hook_internal_message_close<br />");
			
			$group_id = $data['groupId'];
			$title = get_post($group_id)->post_title;
			$body = $data['body'];
			
			//METODO: check that current user is allowed
			
			$current_user = (int)$data['userId'];
			
			$close_message_id = dbm_content_tc_create_internal_message($title, $body, $current_user_id, $group_id, false);
			dbm_add_post_relation($close_message_id, 'internal-message-types/close-ticket');
			dbm_set_single_relation_by_name($group_id, 'internal-message-group-status', 'closed');
			
			$response_data['messageId'] = $close_message_id;
		}
		
		public function hook_internal_message_re_open($data, &$response_data) {
			//echo("\DbmContentTransactionalCommunication\ApiActionHooks::hook_internal_message_re_open<br />");
			
			$group_id = $data['groupId'];
			$body = $data['body'];
			
			//METODO: check that current user is allowed
			
			$current_user = (int)$data['userId'];
			
			$close_message_id = dbm_content_tc_create_internal_message($title, $body, $current_user_id, $group_id, false);
			dbm_add_post_relation($close_message_id, 'internal-message-types/reopen-ticket');
			dbm_set_single_relation_by_name($group_id, 'internal-message-group-status', 'open');
			
			$response_data['messageId'] = $close_message_id;
		}
		
		public function hook_internal_message_change_assignment($data, &$response_data) {
			$group_id = $data['groupId'];
			$body = $data['body'];
			
			$current_user = (int)$data['userId'];
			
			$assign_users = $data['assignUsers'];
			$unassign_users = $data['unassignUsers'];
			
			$group = dbmtc_get_internal_message_group($group_id);
			
			$new_messages = array();
			
			if($assign_users) {
				foreach($assign_users as $user_id) {
					$new_messages[] = $group->assign_user($user_id, $body, $current_user);
				}
			}
			
			if($unassign_users) {
				foreach($unassign_users as $user_id) {
					$new_messages[] = $group->unassign_user($user_id, $body, $current_user);
				}
			}
		}
		
		public function hook_internal_message_request_data($data, &$response_data) {
			$group_id = $data['groupId'];
			$body = $data['body'];
			
			$current_user = (int)$data['userId'];
			
			$requested_data = $data['requestedData'];
			
			$group = dbmtc_get_internal_message_group($group_id);
			
			$message = $group->request_data($requested_data);
			
			if(!(isset($data['skipNotify']) && $data['skipNotify'])) {
				$message->notify();
			}
		}
		
		public function hook_internal_message_set_field($data, &$response_data) {
			$group_id = $data['groupId'];
			$body = $data['body'];
			
			$current_user = get_current_user_id();
			
			$field = $data['field'];
			$value = $data['value'];
			
			$group = dbmtc_get_internal_message_group($group_id);
			
			$message = $group->set_field($field, $value);
			
			if(!(isset($data['skipNotify']) && $data['skipNotify'])) {
				$message->notify();
			}
		}
		
		public function hook_internal_message_verify_phone_number_field($data, &$response_data) {
			$group_id = $data['groupId'];
			$field = $data['field'];
			$value = $data['value'];
			
			$group = dbmtc_get_internal_message_group($group_id);
			
			$field_id = $group->get_field_id($field);
			
			$verification_id = get_post_meta($field_id, 'textMessageVerification', true);
			
			$result = false;
			
			$phone_number = get_post_meta($field_id, 'dbmtc_value', true);
			$code = $data['verificationCode'];
			
			$hash_salt = 'Tw?otIAwI%ourB-:@VeZ4tGLY0=Twh)1J Wwhxc!5AOg:*L$Ff@CAY+d-iW47Ztm';
			//METODO: add filter around salt
			$hash = md5($phone_number.$hash_salt);
			
			$stored_hash = get_post_meta($verification_id, 'verification_hash', true);
			$stored_code = get_post_meta($verification_id, 'verification_code', true);
			
			if($stored_hash === $hash && $stored_code === $code) {
				update_post_meta($data_id, 'verified', true);
				dbm_set_single_relation_by_name($field_id, 'field-status', 'verified');
				$result = true;
			}
			
			$response_data['verified'] = $result;
		}
		
		protected function get_user($username_or_email) {
			if(is_int($username_or_email)) {
				$user = get_user_by('id', $username_or_email);
				
				return $user;
			}
			
			$user = get_user_by('login', $username_or_email);
			
			if(!$user) {
				$user = get_user_by('email', $username_or_email);
			}
			
			return $user;
		}
		
		public function hook_sendTwoFactorVerification($data, &$response_data) {
			$user = $this->get_user($data['user']);
			
			if(!$user) {
				$response_data['message'] = "User not found";
				return;
			}
			
			$preferred_order = array('text-message', 'email');
			
			$verification_generator = dbmtc_create_verification_generator();
			$verification_generator->set_type('two-factor-verification');
			$verification = $verification_generator->generate_and_send_to_user($data['user'], $user, $preferred_order);
			
			$response_data['verificationId'] = $verification->get_id();
		}
		
		public function hook_verifyVerification($data, &$response_data) {
			
			$verification = dbmtc_get_verification((int)$data['verificationId']);
			$response_data['verified'] = $verification->verify($data['value'], $data['verificationCode']);
		}
		
		public function hook_sendPhoneNumberVerification($data, &$response_data) {
			$phoneNumber = $data['phoneNumber'];
			$code = mt_rand(100000, 999999);
			
			$replacements = array(
				'code' => $code,
				'phoneNumber' => $phoneNumber
			);
			
			$hash_salt = 'Tw?otIAwI%ourB-:@VeZ4tGLY0=Twh)1J Wwhxc!5AOg:*L$Ff@CAY+d-iW47Ztm';
			//METODO: add filter around salt
			$hash = md5($phoneNumber.$hash_salt);
			
			$data_id = dbm_create_data('Text message verification - '.$hash, 'address-verification', 'admin-grouping/address-verifications');
			update_post_meta($data_id, 'verification_hash', $hash);
			update_post_meta($data_id, 'verification_code', $code);
			update_post_meta($data_id, 'verified', false);
			
			wp_update_post(array(
				'ID' => $data_id,
				'post_status' => 'private'
			));
			
			$template_id = dbm_new_query('dbm_additional')->add_relation_by_path('global-transactional-templates/verify-phone-number')->get_post_id();
			
			$template = dbm_content_tc_get_template_with_replacements($template_id, $replacements);
			$communication_id = dbm_content_tc_send_text_message(wp_strip_all_tags($template['body']), $phoneNumber);
			
			update_post_meta($data_id, 'send_time', time());
			update_post_meta($data_id, 'communication_id', $communication_id);
			
			$response_data['sent'] = get_post_meta($communication_id, 'send_result', true);
			$response_data['verificationId'] = $data_id;
			//$response_data['hash'] = $hash;
		}
		
		public function hook_resendPhoneNumberVerification($data, &$response_data) {
			
			$data_id = $data['verificationId'];
			$phoneNumber = $data['phoneNumber'];
			
			$hash_salt = 'Tw?otIAwI%ourB-:@VeZ4tGLY0=Twh)1J Wwhxc!5AOg:*L$Ff@CAY+d-iW47Ztm';
			//METODO: add filter around salt
			$hash = md5($phoneNumber.$hash_salt);
			
			if($hash !== get_post_meta($data_id, 'verification_hash', true)) {
				throw new \Exception('Incorrect phone number');
			}
			
			$replacements = array(
				'code' => get_post_meta($data_id, 'verification_code', true),
				'phoneNumber' => $phoneNumber
			);
			
			$template_id = dbm_new_query('dbm_additional')->add_relation_by_path('global-transactional-templates/verify-phone-number')->get_post_id();
			
			$template = dbm_content_tc_get_template_with_replacements($template_id, $replacements);
			$communication_id = dbm_content_tc_send_text_message(wp_strip_all_tags($template['body']), $phoneNumber);
			
			add_post_meta($data_id, 'send_time', time());
			add_post_meta($data_id, 'communication_id', $communication_id);
			
			$response_data['sent'] = get_post_meta($communication_id, 'send_result', true);
			$response_data['verificationId'] = $data_id;
			//$response_data['hash'] = $hash;
		}
		
		public function hook_sendPasswordResetVerification($data, &$response_data) {
			$user = $this->get_user($data['user']);
			
			if(!$user) {
				$response_data['message'] = "User not found";
				return;
			}
			
			$send_type = $data['sendType'];
			
			$user_id = $user->ID;
			$email = get_userdata($user_id)->user_email;
			
			$code = mt_rand(100000, 999999);
			
			$hash_salt = 'Tw?otIAwI%ourB-:@VeZ4tGLY0=Twh)1J Wwhxc!5AOg:*L$Ff@CAY+d-iW47Ztm';
			//METODO: add filter around salt
			$hash = md5($user_id.$hash_salt);
			
			$data_id = dbm_create_data('Reset password verification - '.$hash, 'address-verification', 'admin-grouping/address-verifications');
			$data_dbm_post = dbm_get_post($data_id);
			$data_dbm_post->add_type_by_name('address-verification/'.'password-reset-verification');
			
			$unverified_id = dbmtc_get_or_create_type('type/verication-status', 'unverified');
			$expired_id = dbmtc_get_or_create_type('type/verication-status', 'expired');
		
			$post = dbmtc_get_group($data_id);
			
			$start_time = time();
			$minutes_until_expired = 10;
			$end_time = $start_time+60*$minutes_until_expired;
			
			$type_relation = dbmtc_get_group($post->add_incoming_relation_by_name($unverified_id, 'for'));
			$type_relation->data_api_post()->editor()->set_object_relation_field('startAt', $start_time);
			$type_relation->data_api_post()->editor()->set_object_relation_field('endAt', $end_time);
			
			$type_relation = dbmtc_get_group($post->add_incoming_relation_by_name($expired_id, 'for', $end_time));
			
			$response_data['verificationId'] = $data_id;
			$response_data['sent'] = array();
			$response_data['availableOptions'] = array();
			
			update_post_meta($data_id, 'user_id', $user_id);
			update_post_meta($data_id, 'verification_hash', $hash);
			update_post_meta($data_id, 'verification_code', $code);
			update_post_meta($data_id, 'verified', false);
			
			wp_update_post(array(
				'ID' => $data_id,
				'post_status' => 'private'
			));
			
			$site_name = substr(preg_replace('/[^a-zA-Z0-9 \\-]+/', '', get_bloginfo('name'), -1), 0, 8);
			
			$sent_text_message = false;
			$phone_number = apply_filters('dbmtc/get_mobile_number_for_user', null, $user);
			if($phone_number) {
				$response_data['availableOptions'][] = 'textMessage';
				
				$template_id = dbm_new_query('dbm_additional')->add_relation_by_path('global-transactional-templates/reset-password-by-verification')->add_relation_by_path('transactional-template-types/text-message')->get_post_id();
				
				$replacements = array(
					'code' => $code,
					'phone-number' => $phone_number
				);
				$template = dbm_content_tc_get_template_with_replacements($template_id, $replacements);
				
				$clean_text = wp_strip_all_tags($template['body']);
				$communication_id = dbm_content_tc_send_text_message($clean_text, $phone_number, apply_filters('dbm_content_tc/default_from_phone_number', $site_name));
				
				update_post_meta($data_id, 'text_message_send_time', time());
				update_post_meta($data_id, 'text_message_communication_id', $communication_id);
				
				$response_data['sent'][] = 'textMessage';
				
				$sent_text_message = true;
			}
			
			if($email) {
				$template_id = dbm_new_query('dbm_additional')->add_relation_by_path('global-transactional-templates/reset-password-by-verification')->add_relation_by_path('transactional-template-types/email')->get_post_id();
				if($template_id) {
					$response_data['availableOptions'][] = 'email';
					
					if($send_type !== 'preferTextMessage' || !$sent_text_message) {
						$replacements = array(
							'code' => $code,
							'email' => $email
						);
						$template = dbm_content_tc_get_template_with_replacements($template_id, $replacements);
					
						$communication_id = dbm_content_tc_send_email($template['title'], $template['body'], $email, apply_filters('dbm_content_tc/default_from_email', get_option('admin_email')));
					
						update_post_meta($data_id, 'email_send_time', time());
						update_post_meta($data_id, 'email_communication_id', $communication_id);
						
						$response_data['sent'][] = 'email';
					}
				}
			}
		}
		
		public function hook_resendPasswordResetVerification($data, &$response_data) {
			$verification_id = $data['verificationId'];
			$send_type = $data['sendType'];
			
			$user_id = get_post_meta($verification_id, 'user_id', true);
			$user = get_user_by('id', $user_id);
			
			$response_data['sent'] = array();
			
			$code = get_post_meta($verification_id, 'verification_code', true);
			
			$site_name = substr(preg_replace('/[^a-zA-Z0-9 \\-]+/', '', get_bloginfo('name'), -1), 0, 8);
			
			if($send_type === 'textMessage') {
				$phone_number = apply_filters('dbmtc/get_mobile_number_for_user', null, $user);
				if($phone_number) {
					$template_id = dbm_new_query('dbm_additional')->add_relation_by_path('global-transactional-templates/reset-password-by-verification')->add_relation_by_path('transactional-template-types/text-message')->get_post_id();
				
					$replacements = array(
						'code' => $code,
						'phone-number' => $phone_number
					);
					$template = dbm_content_tc_get_template_with_replacements($template_id, $replacements);
				
					$clean_text = wp_strip_all_tags($template['body']);
					$communication_id = dbm_content_tc_send_text_message($clean_text, $phone_number, apply_filters('dbm_content_tc/default_from_phone_number', $site_name));
				
					add_post_meta($verification_id, 'resend_text_message_send_time', time());
					add_post_meta($verification_id, 'resend_text_message_communication_id', $communication_id);
				
					$response_data['sent'][] = 'textMessage';
				}
			}
			if($send_type === 'email') {
				$template_id = dbm_new_query('dbm_additional')->add_relation_by_path('global-transactional-templates/reset-password-by-verification')->add_relation_by_path('transactional-template-types/email')->get_post_id();
				if($template_id) {
					$email = get_userdata($user_id)->user_email;
					
					$replacements = array(
						'code' => $code,
						'email' => $email
					);
					$template = dbm_content_tc_get_template_with_replacements($template_id, $replacements);
				
					$communication_id = dbm_content_tc_send_email($template['title'], $template['body'], $email, apply_filters('dbm_content_tc/default_from_email', get_option('admin_email')));
				
					add_post_meta($verification_id, 'resend_email_send_time', time());
					add_post_meta($verification_id, 'resend_email_communication_id', $communication_id);
					
					$response_data['sent'][] = 'email';
				}
			}
		}
		
		public function hook_testMessageNotification($data, &$response_data) {
			$message_id = (int)$data['message'];
			$for_identifier = $data['for'];
			$email = $data['email'];
			
			if(!$email) {
				return;
			}
			
			$message = dbmtc_get_internal_message($message_id);
			$for_user = dbmtc_get_user($for_identifier);
			
			$result = $message->testNotification($for_user, $email);
			
			$response_data['result'] = $result;
			$response_data['sentTo'] = $email;
		}
		
		public function hook_timedAction_checkTimedActions($data, &$response_data) {
			do_action('dbmtc_check_timed_actions');
		}
		
		public function hook_timedAction_tryToPerform($data, &$response_data) {
			$id = $data['id'];
			
			$timed_action = dbmtc_get_timed_action($id);
			$timed_action->try_to_perform();
		}
		
		public function hook_sendEmail($data, &$response_data) {
			
			//METODO: add filter to allow other roles
			if(!current_user_can( 'administrator' )) {
				throw new \Exception('Not permitted');
			}
			
			$template_id = $data['emailTemplateId'];
			$for_id = $data['forId'];
			$for_type = isset($data['forType']) ? $data['forType'] : 'manual';
			$from = isset($data['from']) ? $data['from'] : dbmtc_get_default_from_email();
			$message_type = isset($data['messageType']) ? $data['messageType'] : 'standard';
			$keywords = isset($data['keywords']) ? $data['keywords'] : array();
			
			if($template_id && $for_id) {
				
				$from_contact = dbmtc_get_manual_contact($from);
				
				$template = dbmtc_create_template_from_post($template_id);
				
				$to_contact = apply_filters('dbmtc/get_contact_for/'.$for_type, null, $for_id);
				if(!$to_contact) {
					throw new \Exception('No contact');
				}
				
				$template->add_keywords_provider(dbmtc_create_static_keywords_replacements($keywords), 'input');
				$template->add_keywords_provider($from_contact->create_keywords_provider(), 'from');
				$template->add_keywords_provider($to_contact->create_keywords_provider(), 'to');
			
				do_action('dbmtc/setup_template/'.$message_type, $template, $for_id);
			
				$content = $template->get_content();
			
				if(!$content['title'] && !$content['content']) {
					throw new \Exception('No content');
				}
				$response_data['sent'] = dbm_content_tc_send_email($content['title'], $content['content'], $to_contact->get_contact_details('email'), $from);
				
			}
			else {
				throw new \Exception('Missing parameters');
			}
		}
		
		public function hook_sendTestEmail($data, &$response_data) {
			
			//METODO: add filter to allow other roles
			if(!current_user_can( 'administrator' )) {
				throw new \Exception('Not permitted');
			}
			
			$email = $data['email'];
			$template_id = $data['emailTemplateId'];
			$for_id = $data['forId'];
			$for_type = isset($data['forType']) ? $data['forType'] : 'manual';
			$from = isset($data['from']) ? $data['from'] : dbmtc_get_default_from_email();
			$message_type = isset($data['messageType']) ? $data['messageType'] : 'standard';
			$keywords = isset($data['keywords']) ? $data['keywords'] : array();
			
			if($template_id && $email) {
				$template = dbmtc_create_template_from_post($template_id);
				
				$from_contact = dbmtc_get_manual_contact($from);
			
				$to_contact = apply_filters('dbmtc/get_contact_for/'.$for_type, null, $for_id);
				if(!$to_contact) {
					throw new \Exception('No to contact');
				}
				
				$template->add_keywords_provider(dbmtc_create_static_keywords_replacements($keywords), 'input');
				$template->add_keywords_provider($from_contact->create_keywords_provider(), 'from');
				$template->add_keywords_provider($to_contact->create_keywords_provider(), 'to');
				
				do_action('dbmtc/setup_template/'.$message_type, $template, $for_id);
				
				$content = $template->get_content();
				
				if(!$content['title'] && !$content['content']) {
					throw new \Exception('No content');
				}
				
				$response_data['sent'] = dbm_content_tc_send_email($content['title'], $content['content'], $email, $from);
			}
			else {
				throw new \Exception('Missing parameters');
			}
		}
		
		public function hook_incomingWebhookEvent($data, &$response_data) {
			$payload = array(
				"url" => $_SERVER['REQUEST_URI'],
				"body" => file_get_contents("php://input")
			);
			
			if(isset($_SERVER['HTTP_REFERER'])) {
				$payload["from"] = $_SERVER['HTTP_REFERER'];
			}
			if(isset($_GET['type'])) {
				$payload["type"] = $_GET['type'];
			}
			
			$post_id = dbm_create_data('Webhook event '.date('Y-m-d H:i:s'), 'incoming-webhook-event');
			$group = dbmtc_get_group($post_id);
			$group->update_meta('payload', $payload);
			$group->make_private();
			
			$action_id = dbmtc_add_action_to_process('incomingWebhook', array($post_id));
			
			$response_data['id'] = $post_id;
			$response_data['actionId'] = $action_id;
		}
		
		public function hook_submitForm($data, &$response_data) {
			
			$form_name = $_GET['form'];
			if(!$form_name) {
				throw new \Exception('No form name');
			}
			$name_id = dbmtc_get_or_create_type('type/form-name', $form_name);
			
			$current_user = get_current_user_id();
			
			$post_id = dbm_create_data('Form submission '.$form_name.' by '.$current_user.' at '.date('Y-m-d H:i:s'), 'form-submission');
			$group = dbmtc_get_group($post_id);
			$group->add_type_by_name('value-item');
			
			$group->add_incoming_relation_by_name($name_id, 'for');
			
			if($current_user) {
				$group->add_user_relation($current_user, 'by');
			}
			
			$form_data = $data->get_params();
			unset($form_data['action_name']);
			unset($form_data['form']);
			
			$wp_upload_dir = wp_upload_dir(null, false);
			
			$files = $data->get_file_params();
			foreach($files as $field_name => $file) {
				$original_name = $file['name'];
				
				$wp_filetype = wp_check_filetype($original_name, null);
				
				$file_name = time().'-'.uniqid().'.'.$wp_filetype['ext'];
				$path_to_file = '/dbmtc/groups/'.$group_id.'/'.$field_name.'/'.$file_name;
				
				$moved = $this->create_folders_and_move_file($wp_upload_dir['basedir'].$path_to_file, $file['tmp_name']);
				
				if(!$moved) {
					return $this->output_error('Could not move uploaded file');
				}
				
				$url = $wp_upload_dir['baseurl'].$path_to_file;
				
				$uploaded_file_group = dbmtc_get_group(dbm_create_data($original_name, 'uploaded-file', 'uploaded-files'));
				$uploaded_file_group->add_type_by_name('identifiable-item');
				$uploaded_file_group->set_field('identifier', $field_name);
				$uploaded_file_group->set_field('fileName', $original_name);
				$uploaded_file_group->set_field('url', $url);
				$uploaded_file_group->add_outgoing_relation_by_name($group->get_id(), 'uploaded-to');
				$uploaded_file_group->change_status('private');
				
			}
			
			$group->set_field('value', $form_data);
			$group->make_private();
			
			$action_id = dbmtc_add_action_to_process('handleFormSubmission/'.$form_name, array($post_id));
			
			if(isset($_GET['handleDirect']) && $_GET['handleDirect'] == '1') {
				dbmtc_process_action();
			}
			
			$response_data['id'] = $post_id;
			$response_data['actionId'] = $action_id;
		}
		
		protected function create_folders_and_move_file($full_path, $temp_path) {
			
			$parts = explode('/', $full_path);
			$file = array_pop($parts);
			$dir = '';
			
			foreach($parts as $part) {
				if(!is_dir($dir .= "/$part")) {
					mkdir($dir);
				}
			}
			
			return move_uploaded_file($temp_path, $full_path);
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\ApiActionHooks<br />");
		}
	}
?>
