<?php
	namespace DbmContentTransactionalCommunication\Template;
	
	// \DbmContentTransactionalCommunication\Template\Template
	class Template {
		
		protected $title = '';
		protected $content = '';
		
		protected $keywords_providers = array();
		protected $named_keywords_providers = array();
		protected $keywords_to_replace = null;
		
		protected $missing_keywords = array();
		
		function __construct() {
			//echo("\DbmContentTransactionalCommunication\Template\Template::__construct<br />");
			
		}
		
		public function set_content($title, $content) {
			$this->title = $title;
			$this->content = $content;
			
			return $this;
		}
		
		public function add_keywords_provider($provider, $prefix = null, $only_at_prefix = false) {
			
			if(!$only_at_prefix) {
				$this->keywords_providers[] = $provider;
			}
			if($prefix) {
				$this->named_keywords_providers[$prefix] = $provider;
			}
			
			return $this;
		}
		
		public function setup_from_post($post) {
			$post = get_post($post);
			
			$title = $post->post_title;
			$content = apply_filters('the_content', $post->post_content);
			
			if(dbm_has_post_relation($post->ID, 'transactional-template-types/email')) {
				$meta_title = get_post_meta($post->ID, 'dbmtc_email_subject', true);
				
				if($meta_title) {
					$title = $meta_title;
				}
			}
			
			$this->set_content($title, $content);
			
			$stored_keywords = get_post_meta($post->ID, 'dbmtc_dynamic_keywords', true);
			if($stored_keywords) {
				$this->keywords_to_replace = $stored_keywords;
			}
			
			return $this;
		}
		
		public function get_content_without_replacements() {
			return array('title' => $this->title, 'content' => $this->content);
		}
		
		public function get_keywords_to_replace() {
			if(!$this->keywords_to_replace) {
				$title_keywords = dbm_content_tc_get_keywords_in_text($this->title);
				$content_keywords = dbm_content_tc_get_keywords_in_text($this->content);
				
				$keywords = array_unique(array_merge($title_keywords, $content_keywords));
				
				$this->keywords_to_replace = $keywords;
			}
			
			return $this->keywords_to_replace;
		}
		
		public function get_keyword_replacement($keyword) {
			$temp = explode(':', $keyword);
			if(count($temp) > 1) {
				$prefix = array_shift($temp);
				if(isset($this->named_keywords_providers[$prefix])) {
					$keyword_without_prefix = implode(':', $temp);
					$current_replacement = $this->named_keywords_providers[$prefix]->get_keyword_replacement($keyword_without_prefix, $this);
					if($current_replacement) {
						return $current_replacement;
					}
				}
			}
			
			foreach($this->keywords_providers as $keyword_provider) {
				$current_replacement = $keyword_provider->get_keyword_replacement($keyword, $this);
				if($current_replacement) {
					return $current_replacement;
				}
			}
			
			return null;
		}
		
		public function get_keyword_map() {
			$return_array = array();
			
			$keywords = $this->get_keywords_to_replace();
			foreach($keywords as $keyword) {
				$replacement = $this->get_keyword_replacement($keyword);
				
				if($replacement) {
					$return_array['%'.$keyword.'%'] = $replacement;
				}
				else {
					$this->missing_keywords[] = $keyword;
				}
			}
			
			return $return_array;
		}
		
		public function perform_replacements($text, $replacements) {
			$replaced_text = str_replace(array_keys($replacements), array_values($replacements), $text);
	
			return $replaced_text;
		}
		
		public function get_content() {
			
			$title = $this->title;
			$content = $this->content;
			
			$keyword_map = $this->get_keyword_map();
			
			$title = $this->perform_replacements($title, $keyword_map);
			$content = $this->perform_replacements($content, $keyword_map);
			
			return array('title' => $title, 'content' => $content);
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\Template\Template<br />");
		}
	}
?>
