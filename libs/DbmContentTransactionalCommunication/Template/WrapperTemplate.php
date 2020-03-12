<?php
	namespace DbmContentTransactionalCommunication\Template;
	
	// \DbmContentTransactionalCommunication\Template\WrapperTemplate
	class WrapperTemplate extends Template {
		
		protected $wrapper_keywords_provider = null;
		
		function __construct() {
			//echo("\DbmContentTransactionalCommunication\Template\WrapperTemplate::__construct<br />");
			
			parent::__construct();
			
			$this->wrapper_keywords_provider = new WrapperKeywordsProvider();
			$this->named_keywords_providers['wrappedTemplate'] = &$this->wrapper_keywords_provider;
		}
		
		public function get_keyword_replacement($keyword) {
			//var_dump('get_keyword_replacement');
			
			$current_replacement = parent::get_keyword_replacement($keyword);
			
			if($current_replacement) {
				return $current_replacement;
			}
			
			return $this->wrapper_keywords_provider->get_keyword_replacement($keyword, $this);
		}
		
		public function set_current_template($title, $unwrapped_content, $template) {
			//var_dump('set_current_template');
			
			$this->wrapper_keywords_provider->set_content($title, $unwrapped_content, $template);
			
			
			return $this;
		}
		
		public function get_wrapped_content($title, $unwrapped_content, $template) {
			//var_dump('get_wrapped_content');
			
			$this->set_current_template($title, $unwrapped_content, $template);
			
			$keyword_map = $this->get_keyword_map();
			$wrapped_content = $this->perform_replacements($this->content, $keyword_map);
			
			$return_object = array('title' => $title, 'content' => $wrapped_content);
			
			$this->wrapper_keywords_provider = null;
			
			return $return_object;
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\Template\WrapperTemplate<br />");
		}
	}
?>
