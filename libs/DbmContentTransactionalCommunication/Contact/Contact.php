<?php
	namespace DbmContentTransactionalCommunication\Contact;
	
	// \DbmContentTransactionalCommunication\Contact\Contact
	class Contact {
		
		protected $post_id = 0;
		protected $user_details = array();
		protected $contact_detials = array();
		
		function __construct() {
			//echo("\DbmContentTransactionalCommunication\Contact\Contact::__construct<br />");
			
		}
		
		public function set_post_id($post_id) {
			$this->post_id = $post_id;
			
			return $this;
		}
		
		public function add_user_details($type, $value) {
			$this->user_details[$type] = $value;
			
			return $this;
		}
		
		public function get_user_details($type) {
			
			if(isset($this->user_details[$type])) {
				return $this->user_details[$type];
			}
			
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
		
		public function set_name($first_name, $last_name) {
			$this->add_user_details('firstName', $first_name);
			$this->add_user_details('lastName', $last_name);
			$this->add_user_details('name', $first_name.' '.$last_name);
			
			return $this;
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
		
		public function link_to_communication($communication_id) {
			if($this->post_id) {
				$post = dbm_get_post($this->post_id);
				$post->add_incoming_relation_by_name($communication_id, 'to');
			}
			
			return $this;
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\Contact\Contact<br />");
		}
	}
?>
