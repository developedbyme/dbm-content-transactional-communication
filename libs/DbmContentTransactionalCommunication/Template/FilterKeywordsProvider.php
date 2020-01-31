<?php
	namespace DbmContentTransactionalCommunication\Template;
	
	// \DbmContentTransactionalCommunication\Template\FilterKeywordsProvider
	class FilterKeywordsProvider {
		
		protected $filter_name = 'dbmtc/keywords/get_keyword_replacement';
		
		function __construct() {
			//echo("\DbmContentTransactionalCommunication\Template\FilterKeywordsProvider::__construct<br />");
			
		}
		
		public function set_filter_name($filter_name) {
			$this->filter_name = $filter_name;
			
			return $this;
		}
		
		public function get_keyword_replacement($keyword, $template = null) {
			//echo("\DbmContentTransactionalCommunication\Template\FilterKeywordsProvider::get_keyword_replacement<br />");
			
			return apply_filters($this->filter_name, null, $keyword, $template, $this);
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\Template\FilterKeywordsProvider<br />");
		}
	}
?>
