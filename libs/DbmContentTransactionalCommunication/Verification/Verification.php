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
		
		public function get_available_send_methods() {
			//METODO
			$type = $this->get_subtypes('address-verification');
		}
		
		public function get_available_send_methods_for_contact($contact) {
			
			$return_methods = array();
			
			$available_methods = $this->get_available_send_methods();
			foreach($available_methods as $method => $data) {
				if($contact->can_handle_send_method($method)) {
					$return_methods[$method] = $data;
				}
			}
			
			return $return_methods;
		}
		
		public function get_preferred_send_methods_for_contact($contact, $preferred_order = null) {
			$available_methods = $this->get_available_send_methods_for_contact($contact);
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
		
		public function send_to_user($user, $preferred_order = null) {
			
			$contact = dbmtc_get_user_contact($user);
			
			$user_id = $user->ID;
			$data_dbm_post->update_meta('user_id', $user_id);
			$send_methods = $this->get_preferred_send_methods_for_contact($contact, $preferred_order);
			
			foreach($send_methods as $method => $data) {
				$this->perform_send($method, $data, $contact);
			}
		}
		
		public function perform_send($method, $data, $to_contact) {
			//METODO: keywords
			do_action('dbmtc/send_verification/'.$method, $this->get_id(), $data, $to_contact);
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\Verification\Verification<br />");
		}
	}
?>
