<?php
	namespace DbmContentTransactionalCommunication;
	
	use \DbmContentTransactionalCommunication\OddCore\PluginBase;
	
	class Plugin extends PluginBase {
		
		function __construct() {
			//echo("\DbmContentTransactionalCommunication\Plugin::__construct<br />");
			
			$this->_default_hook_priority = 20;
			
			parent::__construct();
			
			//$this->add_javascript('dbm-content-transactional-communication-main', DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_URL.'/assets/js/main.js');
		}
		
		protected function create_pages() {
			//echo("\DbmContentTransactionalCommunication\Plugin::create_pages<br />");
			
		}
		
		protected function create_custom_post_types() {
			//echo("\DbmContentTransactionalCommunication\Plugin::create_custom_post_types<br />");
			
		}
		
		protected function create_additional_hooks() {
			//echo("\DbmContentTransactionalCommunication\Plugin::create_additional_hooks<br />");
			
			$this->add_additional_hook(new \DbmContentTransactionalCommunication\ChangePostHooks());
			$this->add_additional_hook(new \DbmContentTransactionalCommunication\ApiActionHooks());
		}
		
		protected function create_rest_api_end_points() {
			//echo("\DbmContentTransactionalCommunication\Plugin::create_rest_api_end_points<br />");
			
			$api_namespace = 'dbm-content-transactional-communication';
			
			$current_end_point = new \DbmContentTransactionalCommunication\OddCore\RestApi\ReactivatePluginEndpoint();
			$current_end_point->set_plugin($this);
			$current_end_point->add_headers(array('Access-Control-Allow-Origin' => '*'));
			$current_end_point->setup('reactivate-plugin', $api_namespace, 1, 'GET');
			$this->_rest_api_end_points[] = $current_end_point;
			
			
		}
		
		protected function create_filters() {
			//echo("\DbmContentTransactionalCommunication\Plugin::create_filters<br />");

			$custom_range_filters = new \DbmContentTransactionalCommunication\CustomRangeFilters();
			
			
			
		}
		
		protected function create_shortcodes() {
			//echo("\DbmContentTransactionalCommunication\OddCore\PluginBase::create_shortcodes<br />");
			
			$current_shortcode = new \DbmContentTransactionalCommunication\Shortcode\WprrShortcode();
			$this->add_shortcode($current_shortcode);
		}
		
		
		public function hook_admin_enqueue_scripts() {
			//echo("\DbmContentTransactionalCommunication\Plugin::hook_admin_enqueue_scripts<br />");
			
			parent::hook_admin_enqueue_scripts();
			
		}
		
		public function hook_save_post($post_id, $post, $update) {
			//echo("\DbmContentTransactionalCommunication\Plugin::hook_save_post<br />");
			
			parent::hook_save_post($post_id, $post, $update);
			
			if(function_exists('dbm_has_post_type')) {
				if(dbm_has_post_type($post_id, 'transactional-template')) {
					$keywords = dbm_content_tc_get_keywords_in_text($post->post_content);
					
					if(dbm_has_post_relation($post_id, 'transactional-template-types/email')) {
						$subject = get_post_meta($post_id, 'dbmtc_email_subject', true);
						$title_keywords = dbm_content_tc_get_keywords_in_text($subject);
						
						$keywords = array_unique(array_merge($keywords, $title_keywords));
					}
					
					update_post_meta($post_id, 'dbmtc_dynamic_keywords', $keywords);
				}
			}
		}
		
		public function activation_setup() {
			\DbmContentTransactionalCommunication\Admin\PluginActivation::run_setup();
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\Plugin<br />");
		}
	}
?>