<?php
	namespace DbmContentTransactionalCommunication\Template;
	
	// \DbmContentTransactionalCommunication\Template\ContactKeywordsProvider
	class ContactKeywordsProvider {
		
		protected $contact = null;
		
		function __construct() {
			//echo("\DbmContentTransactionalCommunication\Template\ContactKeywordsProvider::__construct<br />");
			
		}
		
		public function set_contact($contact) {
			$this->contact = $contact;
		}
		
		public function get_keyword_replacement($keyword, $template = null) {
			//echo("\DbmContentTransactionalCommunication\Template\ContactKeywordsProvider::get_keyword_replacement<br />");
			//METODO: name, firstName, contactDetails:any
			
			switch($keyword) {
				case 'email':
				case 'phoneNumber':
					return $this->contact->get_contact_details($keyword);
				default:
					return $this->contact->get_user_details($keyword);
			}
			
			return null;
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\Template\ContactKeywordsProvider<br />");
		}
	}
?>
