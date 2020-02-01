<?php
	namespace DbmContentTransactionalCommunication\Verification;
	
	// \DbmContentTransactionalCommunication\Verification\Verification
	class Verification extends \DbmContent\DbmPost {
		
		function __construct($id) {
			//echo("\DbmContentTransactionalCommunication\Verification\Verification::__construct<br />");
			parent::__construct($id);
		}
		
		/*
			if($set_default_templates) {
				//METODO
				switch($this->verifiction_type) {
					case 'password-reset-verification':
						$template_path = 'global-transactional-templates/reset-password-by-verification';
						$template_id = dbm_new_query('dbm_additional')->add_relation_by_path($template_path)->add_relation_by_path('transactional-template-types/email')->get_post_id();
						if($template_id) {
							$this->send_methods['email'] = array('template' => $template_id);
						}
						$template_id = dbm_new_query('dbm_additional')->add_relation_by_path($template_path)->add_relation_by_path('transactional-template-types/text-message')->get_post_id();
						if($template_id) {
							$this->send_methods['textMessage'] = array('template' => $template_id);
						}
						break;
					case 'two-factor-verification':
						
						break;
					default:
						break;
				}
			}
		*/
		public function get_verification_types() {
			return $this->get_subtypes('address-verification');
		}
		
		public function get_code() {
			return $this->get_meta('verification_code');
		}
		
		public function verify($value, $code) {
			
			$salt = 'Tw?otIAwI%ourB-:@VeZ4tGLY0=Twh)1J Wwhxc!5AOg:*L$Ff@CAY+d-iW47Ztm';
			$hash = apply_filters('dbmtc/verification/generate_hash', md5($value.$salt), $value, $salt, $this);
			
			if($this->get_meta('verification_hash') === $hash && $this->get_meta('verification_code') === $code) {
				$this->update_meta('verified', true);
				return true;
			}
			
			return false;
		}
		
		public function get_available_send_methods($requested_methods) {
			$available_methods = array();
			
			foreach($requested_methods as $requested_method) {
				$method_data = apply_filters('dbmtc/send_method_for_verification/'.$requested_method, null, $this);
				if($method_data) {
					$available_methods[$requested_method] = $method_data;
				}
			}
			
			return $available_methods;
		}
		
		public function get_available_send_methods_for_contact($requested_methods, $contact) {
			
			$return_methods = array();
			
			$available_methods = $this->get_available_send_methods($requested_methods);
			foreach($available_methods as $method => $data) {
				if($contact->can_handle_send_method($method)) {
					$return_methods[$method] = $data;
				}
			}
			
			return $return_methods;
		}
		
		public function get_preferred_send_methods_for_contact($contact, $preferred_order) {
			$available_methods = $this->get_available_send_methods_for_contact($preferred_order, $contact);
			$selected_methods = $available_methods;
			
			if($preferred_order) {
				foreach($preferred_order as $preferred_option) {
					if(isset($available_methods[$preferred_option])) {
						$selected_methods = array($preferred_option => $available_methods[$preferred_option]);
						break;
					}
				}
			}
			
			$selected_methods = apply_filters('dbmtc/verification/preferred_send_methods', $selected_methods, $contact, $available_methods, $this);
			
			return $selected_methods;
		}
		
		public function send_to_user($user, $preferred_order) {
			
			$contact = dbmtc_get_user_contact($user);
			$send_methods = $this->get_preferred_send_methods_for_contact($contact, $preferred_order);
			
			foreach($send_methods as $method => $data) {
				$this->perform_send($method, $data, $contact);
			}
		}
		
		public function perform_send($method, $data, $to_contact) {
			
			$data['template']->add_keywords_provider($this->create_keywords_provider(), 'verification');
			$data['template']->add_keywords_provider($to_contact->create_keywords_provider(), 'to');
			
			do_action('dbmtc/send_verification/'.$method, $data, $to_contact, $this);
		}
		
		public function create_keywords_provider() {
			$provider = new \DbmContentTransactionalCommunication\Template\VerificationKeywordsProvider();
			
			$provider->set_verification($this);
			
			return $provider;
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\Verification\Verification<br />");
		}
	}
?>
