<?php
	namespace DbmContentTransactionalCommunication\Shortcode;
	
	use \DbmContentTransactionalCommunication\OddCore\Shortcode\ShortcodeFilter;
	
	// \DbmContentTransactionalCommunication\Shortcode\WprrShortcode
	class WprrShortcode extends ShortcodeFilter {
		
		function __construct() {
			//echo("\OddCore\Shortcode\WprrShortcode::__construct<br />");
			
			$this->set_keyword('wprr-component');
			
		}
		
		public function apply_shortcode($attributes, $content, $tag) {
			//echo("\Shortcode\PostTemplateShorctode::apply_shortcode<br />");
			
			$type = $attributes['type'];
			$expanded = (isset($attributes['expanded']) && $attributes['expanded'] == '1') ? ' data-expanded-content="1"' : '';
			$data = '';
			if(isset($attributes['data']) && !empty($attributes['data'])) {
				$data = ' data-wprr-component-data="'.str_replace('&amp;', '&', $attributes['data']).'"';
			}
			
			$return_value = '<div'.$expanded.' data-wprr-component="'.$type.'"'.$data.'></div>';
			
			return $return_value;
		}
		
		public static function test_import() {
			echo("Imported \Shortcode\WprrShortcode<br />");
		}
	}
?>