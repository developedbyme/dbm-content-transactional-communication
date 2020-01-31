<?php
	namespace DbmContentTransactionalCommunication\Template;
	
	// \DbmContentTransactionalCommunication\Template\FilterKeywordsProvider
	class FilterKeywordsProvider {
		
		protected $filter_name = 'dbmtc/keywords/get_keyword_replacement';
		protected $data = null;
		protected $triggering_keywords = null;
		
		function __construct() {
			//echo("\DbmContentTransactionalCommunication\Template\FilterKeywordsProvider::__construct<br />");
			
		}
		
		public function set_filter_name($filter_name) {
			$this->filter_name = $filter_name;
			
			return $this;
		}
		
		public function set_triggering_keywords($triggering_keywords) {
			
			$this->triggering_keywords = $triggering_keywords;
			
			return $this;
		}
		
		public function set_data($data) {
			$this->data = $data;
			
			return $this;
		}
		
		public function get_data() {
			return $this->data;
		}
		
		public function get_keyword_replacement($keyword, $template = null) {
			//echo("\DbmContentTransactionalCommunication\Template\FilterKeywordsProvider::get_keyword_replacement<br />");
			
			if($this->triggering_keywords && !in_array($keyword, $this->triggering_keywords)) {
				return null;
			}
			
			return apply_filters($this->filter_name, null, $keyword, $template, $this);
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\Template\FilterKeywordsProvider<br />");
		}
	}
?>
