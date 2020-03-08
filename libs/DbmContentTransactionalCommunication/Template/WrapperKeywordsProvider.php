<?php
	namespace DbmContentTransactionalCommunication\Template;
	
	// \DbmContentTransactionalCommunication\Template\WrapperKeywordsProvider
	class WrapperKeywordsProvider {
		
		protected $title = '';
		protected $content = '';
		protected $template = null;
		
		function __construct() {
			//echo("\DbmContentTransactionalCommunication\Template\WrapperKeywordsProvider::__construct<br />");
			
		}
		
		public function set_content($title, $content, $template) {
			$this->title = $title;
			$this->content = $content;
			$this->template = $template;
			
			return $this;
		}
		
		public function get_keyword_replacement($keyword, $wrapper_template = null) {
			//echo("\DbmContentTransactionalCommunication\Template\WrapperKeywordsProvider::get_keyword_replacement<br />");
			
			//var_dump($keyword);
			
			switch($keyword) {
				case 'title':
					return $this->title;
				case 'content':
					return $this->content;
			}
			
			if(!$this->wrapper_template) {
				return $this->template->get_keyword_replacement($keyword);
			}
			
			return null;
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\Template\WrapperKeywordsProvider<br />");
		}
	}
?>
