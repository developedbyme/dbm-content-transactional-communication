<?php
	namespace DbmContentTransactionalCommunication\Template;
	
	// \DbmContentTransactionalCommunication\Template\MessageKeywordsProvider
	class MessageKeywordsProvider {
		
		protected $message = null;
		
		function __construct() {
			//echo("\DbmContentTransactionalCommunication\Template\MessageKeywordsProvider::__construct<br />");
			
		}
		
		public function set_message($message) {
			$this->message = $message;
			
			return $this;
		}
		
		public function get_keyword_replacement($keyword, $template = null) {
			//echo("\DbmContentTransactionalCommunication\Template\MessageKeywordsProvider::get_keyword_replacement<br />");
			
			switch($keyword) {
				case 'id':
					return $this->message->get_id();
				case 'title':
					return $this->message->get_title();
				case 'message':
					return $this->message->get_content();
				case 'link':
				case 'message-link':
					return $this->message->get_view_url();
			}
			
			return null;
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\Template\MessageKeywordsProvider<br />");
		}
	}
?>
