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
			add_action('dbmtc/send_verification/text-message', array($this, 'hook_dbmtc_send_verification_text_message'), $this->_default_hook_priority, 3);
			add_action('dbmtc/send_verification/email', array($this, 'hook_dbmtc_send_verification_email'), $this->_default_hook_priority, 3);
			add_action('dbmtc_check_timed_actions', array($this, 'hook_dbmtc_check_timed_actions'), $this->_default_hook_priority, 0);
			add_action('dbmtc/timed_action/update_field_timeline', array($this, 'hook_dbmtc_timed_action_update_field_timeline'), $this->_default_hook_priority, 1);
		}
		
		public function hook_plugins_loaded() {
			//echo("\DbmContentTransactionalCommunication\Plugin::hook_plugins_loaded<br />");
			
			if(function_exists('dbm_content_add_owned_relationship')) {
				dbm_content_add_owned_relationship_with_auto_add('internal-message-group', 'internal-message-groups');
				dbm_content_add_owned_relationship_with_auto_add('link-group', 'link-groups');
				dbm_content_add_owned_relationship_with_auto_add('page-data', 'page-datas');
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
			
			add_action('dbmtc/set_field_value/multiple-relation', array($this, 'hook_set_field_value_multiple_relation'), 10, 2);
			add_filter('dbmtc/get_field_value/multiple-relation', array($this, 'filter_get_field_value_multiple_relation'), 10, 2);
			add_filter('dbmtc/copy_field_template_meta/multiple-relation', array($this, 'hook_copy_field_template_meta_multiple_relation'), 10, 2);
			
			add_action('dbmtc/set_field_value/relation-flag', array($this, 'hook_set_field_value_relation_flag'), 10, 2);
			add_filter('dbmtc/get_field_value/relation-flag', array($this, 'filter_get_field_value_relation_flag'), 10, 2);
			add_filter('dbmtc/copy_field_template_meta/relation-flag', array($this, 'hook_copy_field_template_meta_relation_flag'), 10, 2);
			
			add_filter('dbmtc/copy_field_template_meta/type/relation', array($this, 'hook_copy_field_template_meta_type_relation'), 10, 2);
			add_filter('dbmtc/copy_field_template_meta/type/multiple-relation', array($this, 'hook_copy_field_template_meta_type_multiple_relation'), 10, 2);
			add_filter('dbmtc/copy_field_template_meta/type/post-relation', array($this, 'hook_copy_field_template_meta_type_post_relation'), 10, 2);
			
			add_filter('dbmtc/default_field_value/name', array($this, 'filter_default_field_value_name'), 10, 2);
			add_filter('dbmtc/default_field_value/address', array($this, 'filter_default_field_value_address'), 10, 2);
			add_filter('dbmtc/default_field_value/data-array', array($this, 'filter_default_field_value_data_array'), 10, 2);
			add_filter('dbmtc/default_field_value/boolean', array($this, 'filter_default_field_value_data_boolean'), 10, 2);
			add_filter('dbmtc/default_field_value/json', array($this, 'filter_default_field_value_data_json'), 10, 2);
			add_filter('dbmtc/default_field_value/multiple-relation', array($this, 'filter_default_field_value_multiple_relation'), 10, 2);
			
			add_filter('dbmtc/encode_field/relation', array($this, 'hook_encode_field_relation'), 10, 2);
			add_filter('dbmtc/encode_field/multiple-relation', array($this, 'hook_encode_field_multiple_relation'), 10, 2);
			add_filter('dbmtc/encode_field/post-relation', array($this, 'hook_encode_field_post_relation'), 10, 2);
			
			add_filter('dbmtc/send_method_for_verification/email', array($this, 'filter_send_method_for_verification_email'), 10, 2);
			add_filter('dbmtc/default_wrapper/email', array($this, 'filter_default_wrapper_email'), 10, 1);
			
			add_filter('dbmtc/get_contact_for/manual', array($this, 'filter_get_contact_for_manual'), 10, 2);
			add_filter('dbmtc/get_contact_for/user', array($this, 'filter_get_contact_for_user'), 10, 2);
			add_filter('dbmtc/get_contact_for/emailMeta', array($this, 'filter_get_contact_for_emailMeta'), 10, 2);
			
			add_filter('dbm_content_tc/send_email', array($this, 'filter_sendEmail'), 1000, 7);
			
			add_filter('cron_schedules', array($this, 'filter_cron_schedules'), 10, 1);
			
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
		
		public function hook_copy_field_template_meta_type_multiple_relation($field, $template) {
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
		
		public function hook_set_field_value_multiple_relation($field, $value) {
			$path = $field->get_meta('dbmtc_relation_path');
			$parent_term = dbm_get_relation_by_path($path);
			
			dbm_replace_relations($field->get_group_id(), $parent_term, $value);
		}
		
		public function filter_get_field_value_multiple_relation($return_value, $field) {
			$path = $field->get_meta('dbmtc_relation_path');
			
			return dbm_get_post_relation($field->get_group_id(), $path);
		}
		
		public function hook_copy_field_template_meta_multiple_relation($field, $template) {
			$field->update_meta('dbmtc_relation_path', $template->get_meta('dbmtc_relation_path'));
		}
		
		public function hook_set_field_value_relation_flag($field, $value) {
			//echo('hook_set_field_value_relation_flag');
			//var_dump($field, $value);
			
			$path = $field->get_meta('dbmtc_relation_path');
			$post_id = $field->get_group_id();
			
			if($value) {
				dbm_add_post_relation($post_id, $path);
			}
			else {
				dbm_remove_post_relation($post_id, $path);
			}
		}
		
		public function filter_get_field_value_relation_flag($return_value, $field) {
			$path = $field->get_meta('dbmtc_relation_path');
			
			return dbm_has_post_relation($field->get_group_id(), $path);
		}
		
		public function hook_copy_field_template_meta_relation_flag($field, $template) {
			$field->update_meta('dbmtc_relation_path', $template->get_meta('dbmtc_relation_path'));
		}
		
		public function filter_default_field_value_name($return_value, $field) {
			return array("firstName" => "", "lastName" => "");
		}
		
		public function filter_default_field_value_address($return_value, $field) {
			return array("address1" => "", "address2" => "", "postCode" => "", "city" => "", "country" => "");
		}
		
		public function filter_default_field_value_data_array($return_value, $field) {
			
			$return_array = array();
			
			return $return_array;
		}
		
		public function filter_default_field_value_data_boolean($return_value, $field) {
			return false;
		}
		
		public function filter_default_field_value_data_json($return_value, $field) {
			return null;
		}
		
		public function filter_default_field_value_multiple_relation($return_value, $field) {
			//echo("filter_default_field_value_multiple_relation");
			
			$return_array = array();
			
			return $return_array;
		}
		
		public function hook_encode_field_relation($return_value, $field) {
			//echo("hook_encode_field_relation");
			
			$current_meta = $field->get_meta('subtree');
			$return_value['subtree'] = $current_meta ? $current_meta : null;
			
			return $return_value;
		}
		
		public function hook_encode_field_multiple_relation($return_value, $field) {
			//echo("hook_encode_field_multiple_relation");
			
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
		
		public function filter_send_method_for_verification_email($data, $verification) {
			if($data) {
				return $data;
			}
			
			$types = $verification->get_verification_types();
			
			//METODO: check withc types
			
			$template_path = 'global-transactional-templates/reset-password-by-verification';
			$template_id = dbm_new_query('dbm_additional')->add_relation_by_path($template_path)->add_relation_by_path('transactional-template-types/email')->get_post_id();
			if($template_id) {
				$template = dbmtc_create_template_from_post($template_id);
				return array('template' => $template);
			}
			
			return $data;
		}
		
		public function hook_dbmtc_send_verification_text_message($data, $to_contact, $verication) {
			//echo("\DbmContentTransactionalCommunication\Plugin::hook_dbmtc_send_verification_text_message<br />");
			
			$content = $data['template']->get_content();
			
			dbm_content_tc_send_text_message($content['content'], $to_contact->get_contact_details('phoneNumber'));
		}
		
		public function hook_dbmtc_send_verification_email($data, $to_contact, $verication) {
			//echo("\DbmContentTransactionalCommunication\Plugin::hook_dbmtc_send_verification_email<br />");
			
			$content = $data['template']->get_content();
			
			dbm_content_tc_send_email($content['title'], $content['content'], $to_contact->get_contact_details('email'));
		}
		
		public function filter_default_wrapper_email($wrapper_template) {
			if($wrapper_template) {
				return $wrapper_template;
			}
			
			$content = apply_filters('dbmtc/default_wrapper/email/content', '');
			if($content) {
				$wrapper_template = new \DbmContentTransactionalCommunication\Template\WrapperTemplate();
				$wrapper_template->set_content('', $content);
				do_action('dbmtc/default_wrapper/email/add_keywords', $wrapper_template);
			}
			
			return $wrapper_template;
		}
		
		public function filter_get_contact_for_user($contact, $id) {
			return dbmtc_get_user_contact($id);
		}
		
		public function filter_get_contact_for_emailMeta($contact, $id) {
			return dbmtc_get_manual_contact(get_post_meta($id, 'email', true));
		}
		
		public function filter_get_contact_for_manual($contact, $id) {
			return dbmtc_get_manual_contact($id);
		}
		
		public function filter_sendEmail($sent, $title, $content, $to, $from, $tc_id, $additional_data) {
			if($sent) {
				return $sent;
			}
			
			$headers = array(
				'From: '.$from,
				'Content-Type: text/html; charset=UTF-8'
			);
			
			if(isset($additional_data['headers'])) {
				foreach($additional_data['headers'] as $name => $header) {
					$headers[] = $name.': '.$header;
				}
			}
			
			$sent = wp_mail($to, $title, $content, $headers);
			
			update_post_meta($tc_id, 'wp_mail_send_status', $sent);
			
			return $sent;
		}
		
		public function filter_cron_schedules($schedules) {
			if(!isset($schedules["5min"])){
				$schedules["5min"] = array(
					'interval' => 5*60,
					'display' => __('Once every 5 minutes', DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_TEXTDOMAIN)
				);
			}
			return $schedules;
		}
		
		public function hook_dbmtc_check_timed_actions() {
			//echo("\DbmContentTransactionalCommunication\Plugin::hook_dbmtc_check_timed_actions<br />");
			
			$time = time();
			
			$timed_actions = dbm_new_query('dbm_data')->set_field('post_status', array('publish', 'private'))->add_type_by_path('timed-action')->add_relation_by_path('timed-action-status/waiting')->add_meta_query('dbmtc_time', $time, '<=', 'NUMERIC')->get_post_ids();
			foreach($timed_actions as $timed_action_id) {
				$timed_action = dbmtc_get_timed_action($timed_action_id);
				$timed_action->try_to_perform();
			}
		}
		
		public function hook_dbmtc_timed_action_update_field_timeline($timed_action) {
			$internal_message_field = dbmtc_get_internal_message_group_field($timed_action->get_action_data()['field']);
			$internal_message_field->update_to_next_value();
		}
		
		public function activation_setup() {
			\DbmContentTransactionalCommunication\Admin\PluginActivation::run_setup();
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\Plugin<br />");
		}
	}
?>