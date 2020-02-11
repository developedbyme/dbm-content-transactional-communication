<?php
	namespace DbmContentTransactionalCommunication\Template;
	
	// \DbmContentTransactionalCommunication\Template\MessageGroupKeywordsProvider
	class MessageGroupKeywordsProvider {
		
		protected $message_group = null;
		
		function __construct() {
			//echo("\DbmContentTransactionalCommunication\Template\MessageGroupKeywordsProvider::__construct<br />");
			
		}
		
		public function set_message_group($message_group) {
			$this->message_group = $message_group;
			
			return $this;
		}
		
		public function get_keyword_replacement($keyword, $template = null) {
			//echo("\DbmContentTransactionalCommunication\Template\MessageGroupKeywordsProvider::get_keyword_replacement<br />");
			
			switch($keyword) {
				case 'id':
					return $this->message_group->get_id();
				case 'title':
					return $this->message_group->get_title();
				case 'link':
				case 'messageGroup-link':
					return $this->message_group->get_view_url();
			}
			
			return null;
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\Template\MessageGroupKeywordsProvider<br />");
		}
	}
?>
