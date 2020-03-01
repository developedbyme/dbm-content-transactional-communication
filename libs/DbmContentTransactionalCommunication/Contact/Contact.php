<?php
	namespace DbmContentTransactionalCommunication\Contact;
	
	// \DbmContentTransactionalCommunication\Contact\Contact
	class Contact {
		
		protected $user_details = array();
		protected $contact_detials = array();
		
		function __construct() {
			//echo("\DbmContentTransactionalCommunication\Contact\Contact::__construct<br />");
			
		}
		
		public function get_user_details($type) {
			return null;
		}
		
		protected function save_contact_details($type) {
			//MENOTE: should be overridden
			
			return $this;
		}
		
		public function set_contact_detials($type, $details) {
			$this->contact_detials[$type] = $details;
			$this->save_contact_details($type);
			
			return $this;
		}
		
		protected function lookup_contact_details($type) {
			return apply_filters('dbmtc/contact/default_contact_details/'.$type, null, $this);
		}
		
		public function get_contact_details($type) {
			if(!isset($this->contact_detials[$type])) {
				$new_value = $this->lookup_contact_details($type, null, $this);
				$this->contact_detials[$type] = $new_value;
			}
			
			return $this->contact_detials[$type];
		}
		
		public function set_email($email) {
			$this->set_contact_detials('email', $email);
			
			return $this;
		}
		
		public function set_phone_number($phone_number) {
			$this->set_contact_detials('phoneNumber', $phone_number);
			
			return $this;
		}
		
		public function can_handle_send_method($method) {
			$contact_details_to_check = $method;
			if($method === 'text-message') {
				$contact_details_to_check = 'phoneNumber';
			}
			
			$contact_details = $this->get_contact_details($contact_details_to_check);
			
			if($contact_details) {
				return true;
			}
			
			return false;
		}
		
		public function create_keywords_provider() {
			$provider = new \DbmContentTransactionalCommunication\Template\ContactKeywordsProvider();
			$provider->set_contact($this);
			
			return $provider;
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\Contact\Contact<br />");
		}
	}
?>
