<?php
	namespace DbmContentTransactionalCommunication\Template;
	
	// \DbmContentTransactionalCommunication\Template\StaticKeywordReplacements
	class StaticKeywordReplacements {
		
		protected $keywords = array();
		
		function __construct() {
			//echo("\DbmContentTransactionalCommunication\Template\StaticKeywordReplacements::__construct<br />");
			
		}
		
		public function add_keyword($keyword, $replacement) {
			$this->keywords[$keyword] = $replacement;
			
			return $this;
		}
		
		public function add_keywords($keywords) {
			foreach($keywords as $keyword => $replacement) {
				$this->add_keyword($keyword, $replacement);
			}
			
			return $this;
		}
		
		public function get_keyword_replacement($keyword, $template = null) {
			//METODO: check for deep value
			if(isset($this->keywords[$keyword])) {
				return $this->keywords[$keyword];
			}
			
			return null;
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\Template\StaticKeywordReplacements<br />");
		}
	}
?>
