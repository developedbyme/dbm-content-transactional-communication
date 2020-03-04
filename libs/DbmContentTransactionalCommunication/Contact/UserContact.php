<?php
	namespace DbmContentTransactionalCommunication\Contact;
	
	// \DbmContentTransactionalCommunication\Contact\UserContact
	class UserContact extends \DbmContentTransactionalCommunication\Contact\Contact {
		
		protected $user_id = 0;
		
		function __construct($user_id) {
			//echo("\DbmContentTransactionalCommunication\Contact\UserContact::__construct<br />");
			
			$this->user_id = $user_id;
			
			parent::__construct();
		}
		
		public function get_user_id() {
			return $this->user_id;
		}
		
		public function get_user() {
			return get_user_by('id', $this->user_id);
		}
		
		protected function lookup_contact_details($type) {
			$initial_value = null;
			
			if($type === 'email') {
				$initial_value = get_userdata($this->get_user_id())->user_email;
			}
			if($type === 'phoneNumber') {
				$user = $this->get_user();
				$initial_value = apply_filters('dbmtc/get_mobile_number_for_user', null, $user);
			}
			
			return apply_filters('dbmtc/contact/default_contact_details/'.$type, $initial_value, $this);
		}
		
		public function get_user_details($type) {
			if($type === 'firstName') {
				return get_userdata($this->get_user_id())->first_name;
			}
			if($type === 'lastName') {
				return get_userdata($this->get_user_id())->last_name;
			}
			if($type === 'name') {
				return get_userdata($this->get_user_id())->display_name;
			}
			
			return null;
		}
		
		//METODO: implement save_contact_details
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\Contact\Contact<br />");
		}
	}
?>
