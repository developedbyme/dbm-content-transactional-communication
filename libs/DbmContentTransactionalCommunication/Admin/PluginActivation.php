<?php
	namespace DbmContentTransactionalCommunication\Admin;

	// \DbmContentTransactionalCommunication\Admin\PluginActivation
	class PluginActivation {
		
		static function create_page($slug, $title, $post_type = 'page', $parent_id = 0) {
			
			$args = array(
				'post_type' => $post_type,
				'name' => $slug,
				'post_parent' => $parent_id,
				'posts_per_page' => 1,
				'fields' => 'ids'
			);
			
			$post_ids = get_posts($args);
			
			if(count($post_ids) === 0) {
				$args = array(
					'post_type' => $post_type,
					'post_parent' => $parent_id,
					'post_name' => $slug,
					'post_title' => $title,
					'post_status' => 'publish'
				);
				
				$post_id = wp_insert_post($args);
			}
			else {
				$post_id = $post_ids[0];
			}
			
			return $post_id;
		}
		
		static function add_term($path, $name) {
			$temp_array = explode(':', $path);
			
			$taxonomy = $temp_array[0];
			$path = explode('/', $temp_array[1]);
			
			\DbmContentTransactionalCommunication\OddCore\Utils\TaxonomyFunctions::add_term($name, $path, $taxonomy);
		}
		
		static function get_term_by_path($path) {
			$temp_array = explode(':', $path);
			
			$taxonomy = $temp_array[0];
			$path = explode('/', $temp_array[1]);
			
			return \DbmContentTransactionalCommunication\OddCore\Utils\TaxonomyFunctions::get_term_by_slugs($path, $taxonomy);
		}
		
		static function add_terms_to_post($term_paths, $post_id) {
			foreach($term_paths as $term_path) {
				$current_term = self::get_term_by_path($term_path);
				if($current_term) {
					wp_set_post_terms($post_id, $current_term->term_id, $current_term->taxonomy, true);
				}
				else {
					//METODO: error message
				}
			}
			
			return $post_id;
		}
		
		public static function create_global_term_and_page($slug, $title, $post_type = 'page', $parent_id = 0) {
			$relation_path = 'dbm_relation:global-pages/'.$slug;
			self::add_term($relation_path, $title);
			$current_page_id = self::create_page($slug, $title, $post_type, $parent_id);
			update_post_meta($current_page_id, '_wp_page_template', 'template-global-'.$slug.'.php');
			self::add_terms_to_post(array($relation_path), $current_page_id);
			
			return $current_page_id;
		}
		
		public static function create_user($login, $first_name = '', $last_name = '') {
			$existing_user = get_user_by('login', $login);
			
			if($existing_user) {
				return $existing_user->ID;
			}
			
			$args = array(
				'user_login' => $login,
				'user_pass' => wp_generate_password(),
				'first_name' => $first_name,
				'last_name' => $last_name,
				'display_name' => $first_name
			);
			
			$new_user_id = wp_insert_user($args);
			
			return $new_user_id;
		}
		
		public static function run_setup() {
			
			self::add_term('dbm_type:internal-message', 'Internal message');
			self::add_term('dbm_type:internal-message-group', 'Internal message group');
			
			self::add_term('dbm_type:admin-grouping', 'Admin grouping');
			self::add_term('dbm_type:admin-grouping/sent-communications', 'Sent communications');
			self::add_term('dbm_type:admin-grouping/address-verifications', 'Address verifications');
			self::add_term('dbm_type:admin-grouping/internal-messages', 'Internal messages');
			self::add_term('dbm_type:admin-grouping/internal-message-groups', 'Internal message groups');
			
			$sent_communications_group = self::create_page('sent-communications', 'Sent communications', 'dbm_data', 0);
			self::add_terms_to_post(array('dbm_type:admin-grouping', 'dbm_type:admin-grouping/sent-communications'), $sent_communications_group);
			
			$address_verifications_group = self::create_page('address-verifications', 'Address verifications', 'dbm_data', 0);
			self::add_terms_to_post(array('dbm_type:admin-grouping', 'dbm_type:admin-grouping/address-verifications'), $address_verifications_group);
			
			$current_group = self::create_page('internal-messages', 'Internal messages', 'dbm_data', 0);
			self::add_terms_to_post(array('dbm_type:admin-grouping', 'dbm_type:admin-grouping/internal-messages'), $current_group);
			
			$current_group = self::create_page('internal-message-groups', 'Internal message groups', 'dbm_data', 0);
			self::add_terms_to_post(array('dbm_type:admin-grouping', 'dbm_type:admin-grouping/internal-message-groups'), $current_group);
			
			self::add_term('dbm_type:address-verification', 'Address verification');
			self::add_term('dbm_type:transactional-template', 'Transactional template');
			self::add_term('dbm_type:transactional-communication', 'Transactional communication');
			
			self::add_term('dbm_relation:transactional-template-types', 'Transactional template types');
			self::add_term('dbm_relation:transactional-template-types/email', 'Email');
			self::add_term('dbm_relation:transactional-template-types/text-message', 'Text message');
			
			self::add_term('dbm_relation:global-transactional-templates', 'Global transactional templates');
			self::add_term('dbm_relation:global-transactional-templates/reset-password', 'Reset password');
			self::add_term('dbm_relation:global-transactional-templates/verify-email', 'Verify email');
			self::add_term('dbm_relation:global-transactional-templates/new-internal-message', 'New internal message');
			
			self::add_term('dbm_relation:internal-message-groups', 'Internal message groups');
			
			$current_term_id = self::add_term('dbm_relation:global-pages', 'Global pages');
			$current_term_id = self::add_term('dbm_relation:global-pages/view-internal-message', 'View internal message');
		}
		
		public static function test_import() {
			echo("Imported \Admin\CustomPostTypes\PluginActivation<br />");
		}
	}
?>
