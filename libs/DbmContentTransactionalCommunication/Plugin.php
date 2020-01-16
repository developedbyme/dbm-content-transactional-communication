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
		
		public function register_hooks() {
			//echo("\DbmContentTransactionalCommunication\Plugin::register_hooks<br />");
			
			parent::register_hooks();
			
			add_action('plugins_loaded', array($this, 'hook_plugins_loaded'), $this->_default_hook_priority);
			
			
		}
		
		public function hook_plugins_loaded() {
			//echo("\DbmContentTransactionalCommunication\Plugin::hook_plugins_loaded<br />");
			
			if(function_exists('dbm_content_add_owned_relationship')) {
				dbm_content_add_owned_relationship_with_auto_add('internal-message-group', 'internal-message-groups');
			}
		}
		
		protected function create_additional_hooks() {
			//echo("\DbmContentTransactionalCommunication\Plugin::create_additional_hooks<br />");
			
			$this->add_additional_hook(new \DbmContentTransactionalCommunication\ChangePostHooks());
			$this->add_additional_hook(new \DbmContentTransactionalCommunication\ApiActionHooks());
			$this->add_additional_hook(new \DbmContentTransactionalCommunication\CustomRangeFilters());
		}
		
		protected function create_rest_api_end_points() {
			//echo("\DbmContentTransactionalCommunication\Plugin::create_rest_api_end_points<br />");
			
			$api_namespace = 'dbm-content-transactional-communication';
			
			$current_end_point = new \DbmContentTransactionalCommunication\RestApi\UploadFileForFieldEndpoint();
			$current_end_point->add_headers(array('Access-Control-Allow-Origin' => '*'));
			$current_end_point->setup('internal-message-group/(?P<group>\d+)/field/(?P<field>[a-zA-Z0-9_\\-]+)/upload', $api_namespace, 1, 'POST');
			$this->_rest_api_end_points[] = $current_end_point;
			
			$current_end_point = new \DbmContentTransactionalCommunication\OddCore\RestApi\ReactivatePluginEndpoint();
			$current_end_point->set_plugin($this);
			$current_end_point->add_headers(array('Access-Control-Allow-Origin' => '*'));
			$current_end_point->setup('reactivate-plugin', $api_namespace, 1, 'GET');
			$this->_rest_api_end_points[] = $current_end_point;
			
			
		}
		
		protected function create_filters() {
			//echo("\DbmContentTransactionalCommunication\Plugin::create_filters<br />");

			$custom_range_filters = new \DbmContentTransactionalCommunication\CustomRangeFilters();
			
			add_filter('dbm_custom_login/registration_is_verified', array($this, 'filter_dbm_custom_login_registration_is_verified'), 10, 2);
			
			add_action('dbmtc/set_field_value/meta', array($this, 'hook_set_field_value_meta'), 10, 2);
			add_filter('dbmtc/get_field_value/meta', array($this, 'filter_get_field_value_meta'), 10, 2);
			add_filter('dbmtc/copy_field_template_meta/meta', array($this, 'hook_copy_field_template_meta_meta'), 10, 2);
			
			add_action('dbmtc/set_field_value/single-relation', array($this, 'hook_set_field_value_single_relation'), 10, 2);
			add_filter('dbmtc/get_field_value/single-relation', array($this, 'filter_get_field_value_single_relation'), 10, 2);
			add_filter('dbmtc/copy_field_template_meta/single-relation', array($this, 'hook_copy_field_template_meta_single_relation'), 10, 2);
			
			add_filter('dbmtc/copy_field_template_meta/type/relation', array($this, 'hook_copy_field_template_meta_type_relation'), 10, 2);
			add_filter('dbmtc/copy_field_template_meta/type/post-relation', array($this, 'hook_copy_field_template_meta_type_post_relation'), 10, 2);
			
			add_filter('dbmtc/default_field_value/name', array($this, 'hook_default_field_value_name'), 10, 2);
			add_filter('dbmtc/default_field_value/address', array($this, 'hook_default_field_value_address'), 10, 2);
			add_filter('dbmtc/default_field_value/data-array', array($this, 'hook_default_field_value_data_array'), 10, 2);
			
			add_filter('dbmtc/encode_field/relation', array($this, 'hook_encode_field_relation'), 10, 2);
			add_filter('dbmtc/encode_field/post-relation', array($this, 'hook_encode_field_post_relation'), 10, 2);
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
		
		public function filter_dbm_custom_login_registration_is_verified($is_verified, $data) {
			$email = $data['email'];
			$data_id = $data['verificationId'];
			
			$hash_salt = 'Tw?otIAwI%ourB-:@VeZ4tGLY0=Twh)1J Wwhxc!5AOg:*L$Ff@CAY+d-iW47Ztm';
			//METODO: add filter around salt
			$hash = md5($email.$hash_salt);
			
			$stored_hash = get_post_meta($data_id, 'verification_hash', true);
			$verified = (bool)get_post_meta($data_id, 'verified', true);
			
			if($verified && ($stored_hash === $hash)) {
				return true;
			}
			return false;
		}
		
		public function hook_set_field_value_meta($field, $value) {
			$meta_key = $field->get_meta('dbmtc_meta_name');
			var_dump($field->get_group_id(), $meta_key, $value);
			update_post_meta($field->get_group_id(), $meta_key, $value);
		}
		
		public function filter_get_field_value_meta($return_value, $field) {
			$meta_key = $field->get_meta('dbmtc_meta_name');
			return get_post_meta($field->get_group_id(), $meta_key, true);
		}
		
		public function hook_copy_field_template_meta_meta($field, $template) {
			$field->update_meta('dbmtc_meta_name', $template->get_meta('dbmtc_meta_name'));
		}
		
		public function hook_copy_field_template_meta_type_relation($field, $template) {
			$field->update_meta('subtree', $template->get_meta('subtree'));
		}
		
		public function hook_copy_field_template_meta_type_post_relation($field, $template) {
			$field->update_meta('postType', $template->get_meta('postType'));
			$field->update_meta('selection', $template->get_meta('selection'));
		}
		
		public function hook_set_field_value_single_relation($field, $value) {
			$path = $field->get_meta('dbmtc_relation_path');
			$parent_term = dbm_get_relation_by_path($path);
			
			dbm_replace_relations($field->get_group_id(), $parent_term, array((int)$value));
		}
		
		public function filter_get_field_value_single_relation($return_value, $field) {
			$path = $field->get_meta('dbmtc_relation_path');
			
			return dbm_get_single_post_relation($field->get_group_id(), $path);
		}
		
		public function hook_copy_field_template_meta_single_relation($field, $template) {
			$field->update_meta('dbmtc_relation_path', $template->get_meta('dbmtc_relation_path'));
		}
		
		public function hook_default_field_value_name($return_value, $field) {
			return array("firstName" => "", "lastName" => "");
		}
		
		public function hook_default_field_value_address($return_value, $field) {
			return array("address1" => "", "address2" => "", "postCode" => "", "city" => "", "country" => "");
		}
		
		public function hook_default_field_value_data_array($return_value, $field) {
			
			$return_array = array();
			
			return $return_array;
		}
		
		public function hook_encode_field_relation($return_value, $field) {
			//echo("hook_encode_field_relation");
			
			$current_meta = $field->get_meta('subtree');
			$return_value['subtree'] = $current_meta ? $current_meta : null;
			
			return $return_value;
		}
		
		public function hook_encode_field_post_relation($return_value, $field) {
			
			$current_meta = $field->get_meta('postType');
			$return_value['postType'] = $current_meta ? $current_meta : 'page';
			
			$current_meta = $field->get_meta('selection');
			$return_value['selection'] = $current_meta ? $current_meta : 'default/default';
			
			return $return_value;
		}
		
		
		public function activation_setup() {
			\DbmContentTransactionalCommunication\Admin\PluginActivation::run_setup();
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\Plugin<br />");
		}
	}
?>