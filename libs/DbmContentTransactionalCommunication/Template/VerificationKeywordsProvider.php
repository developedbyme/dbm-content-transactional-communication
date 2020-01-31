<?php
	namespace DbmContentTransactionalCommunication\Template;
	
	// \DbmContentTransactionalCommunication\Template\VerificationKeywordsProvider
	class VerificationKeywordsProvider {
		
		protected $verification = null;
		
		function __construct() {
			//echo("\DbmContentTransactionalCommunication\Template\VerificationKeywordsProvider::__construct<br />");
			
		}
		
		public function set_verification($verification) {
			$this->verification = $verification;
			
			return $this;
		}
		
		public function get_keyword_replacement($keyword, $template = null) {
			//echo("\DbmContentTransactionalCommunication\Template\VerificationKeywordsProvider::get_keyword_replacement<br />");
			
			switch($keyword) {
				case 'code':
					return $this->verification->get_code();
			}
			
			return null;
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\Template\VerificationKeywordsProvider<br />");
		}
	}
?>
