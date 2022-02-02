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
			self::add_term('dbm_type:internal-message-group-field', 'Internal message group field');
			self::add_term('dbm_type:field-template', 'Field template');
			self::add_term('dbm_type:timed-action', 'Timed action');
			self::add_term('dbm_type:link-group', 'Link group');
			self::add_term('dbm_type:page-data', 'Page data');
			self::add_term('dbm_type:uploaded-file', 'Uploaded file');
			
			self::add_term('dbm_type:admin-grouping', 'Admin grouping');
			self::add_term('dbm_type:admin-grouping/sent-communications', 'Sent communications');
			self::add_term('dbm_type:admin-grouping/address-verifications', 'Address verifications');
			self::add_term('dbm_type:admin-grouping/internal-messages', 'Internal messages');
			self::add_term('dbm_type:admin-grouping/internal-message-groups', 'Internal message groups');
			self::add_term('dbm_type:admin-grouping/internal-message-group-fields', 'Internal message group fields');
			self::add_term('dbm_type:admin-grouping/field-templates', 'Field templates');
			self::add_term('dbm_type:admin-grouping/timed-actions', 'Timed actions');
			self::add_term('dbm_type:admin-grouping/link-groups', 'Link groups');
			self::add_term('dbm_type:admin-grouping/page-datas', 'Page datas');
			self::add_term('dbm_type:admin-grouping/uploaded-files', 'Uploaded files');
			
			$sent_communications_group = self::create_page('sent-communications', 'Sent communications', 'dbm_data', 0);
			self::add_terms_to_post(array('dbm_type:admin-grouping', 'dbm_type:admin-grouping/sent-communications'), $sent_communications_group);
			
			$address_verifications_group = self::create_page('address-verifications', 'Address verifications', 'dbm_data', 0);
			self::add_terms_to_post(array('dbm_type:admin-grouping', 'dbm_type:admin-grouping/address-verifications'), $address_verifications_group);
			
			$current_group = self::create_page('internal-messages', 'Internal messages', 'dbm_data', 0);
			self::add_terms_to_post(array('dbm_type:admin-grouping', 'dbm_type:admin-grouping/internal-messages'), $current_group);
			
			$current_group = self::create_page('internal-message-groups', 'Internal message groups', 'dbm_data', 0);
			self::add_terms_to_post(array('dbm_type:admin-grouping', 'dbm_type:admin-grouping/internal-message-groups'), $current_group);
			
			$current_group = self::create_page('internal-message-group-fields', 'Internal message group fields', 'dbm_data', 0);
			self::add_terms_to_post(array('dbm_type:admin-grouping', 'dbm_type:admin-grouping/internal-message-group-fields'), $current_group);
			
			$current_group = self::create_page('field-templates', 'Field templates', 'dbm_data', 0);
			self::add_terms_to_post(array('dbm_type:admin-grouping', 'dbm_type:admin-grouping/field-templates'), $current_group);
			
			$current_group = self::create_page('link-groups', 'Link groups', 'dbm_data', 0);
			self::add_terms_to_post(array('dbm_type:admin-grouping', 'dbm_type:admin-grouping/link-groups'), $current_group);
			
			$current_group = self::create_page('page-datas', 'Page datas', 'dbm_data', 0);
			self::add_terms_to_post(array('dbm_type:admin-grouping', 'dbm_type:admin-grouping/page-datas'), $current_group);
			
			$current_group = self::create_page('uploaded-files', 'Uploaded files', 'dbm_data', 0);
			self::add_terms_to_post(array('dbm_type:admin-grouping', 'dbm_type:admin-grouping/uploaded-files'), $current_group);
			
			self::add_term('dbm_type:address-verification', 'Address verification');
			self::add_term('dbm_type:address-verification/password-reset-verification', 'Password reset verification');
			self::add_term('dbm_type:address-verification/two-factor-verification', 'Two factor verification');
			self::add_term('dbm_type:transactional-template', 'Transactional template');
			self::add_term('dbm_type:transactional-communication', 'Transactional communication');
			
			self::add_term('dbm_relation:transactional-template-types', 'Transactional template types');
			self::add_term('dbm_relation:transactional-template-types/email', 'Email');
			self::add_term('dbm_relation:transactional-template-types/text-message', 'Text message');
			
			self::add_term('dbm_relation:global-transactional-templates', 'Global transactional templates');
			self::add_term('dbm_relation:global-transactional-templates/reset-password', 'Reset password');
			self::add_term('dbm_relation:global-transactional-templates/verify-email', 'Verify email');
			self::add_term('dbm_relation:global-transactional-templates/reset-password-by-verification', 'Reset password by verification');
			self::add_term('dbm_relation:global-transactional-templates/verify-phone-number', 'Verify phone number');
			self::add_term('dbm_relation:global-transactional-templates/new-internal-message', 'New internal message');
			
			self::add_term('dbm_relation:internal-message-group-types', 'Internal message group types');
			self::add_term('dbm_relation:internal-message-group-types/standard', 'Standard');
			
			self::add_term('dbm_relation:internal-message-group-status', 'Internal message status');
			self::add_term('dbm_relation:internal-message-group-status/open', 'Open');
			self::add_term('dbm_relation:internal-message-group-status/closed', 'Closed');
			
			self::add_term('dbm_relation:internal-message-group-flags', 'Internal message flags');
			self::add_term('dbm_relation:internal-message-group-flags/unassigned', 'Unassigned');
			self::add_term('dbm_relation:internal-message-group-flags/assigned', 'Assigned');
			
			self::add_term('dbm_relation:internal-message-types', 'Internal message types');
			self::add_term('dbm_relation:internal-message-types/message', 'Message');
			self::add_term('dbm_relation:internal-message-types/reopen-ticket', 'Reopen ticket');
			self::add_term('dbm_relation:internal-message-types/close-ticket', 'Close ticket');
			self::add_term('dbm_relation:internal-message-types/change-comment', 'Change comment');
			self::add_term('dbm_relation:internal-message-types/comment', 'Comment');
			self::add_term('dbm_relation:internal-message-types/request-for-data', 'Request for data');
			self::add_term('dbm_relation:internal-message-types/user-assigned', 'User assigned');
			self::add_term('dbm_relation:internal-message-types/user-unassigned', 'User unassigned');
			self::add_term('dbm_relation:internal-message-types/needs-to-be-assigned', 'Needs to be assigned');
			self::add_term('dbm_relation:internal-message-types/field-changed', 'Field changed');
			self::add_term('dbm_relation:internal-message-types/verify-mobile-phone-field', 'Verify mobile phone field');
			self::add_term('dbm_relation:internal-message-types/added-to-field-timeline', 'Added to field timeline');
			self::add_term('dbm_relation:internal-message-types/removed-from-field-timeline', 'Removed from field timeline');
			self::add_term('dbm_relation:internal-message-types/added-item', 'Added item');
			self::add_term('dbm_relation:internal-message-types/removed-item', 'Removed item');
			self::add_term('dbm_relation:internal-message-types/translations-updated', 'Translations updated');
			
			self::add_term('dbm_relation:internal-message-status', 'Internal message status');
			self::add_term('dbm_relation:internal-message-status/removed', 'Removed');
			
			self::add_term('dbm_relation:field-type', 'Field type');
			self::add_term('dbm_relation:field-type/string', 'String');
			self::add_term('dbm_relation:field-type/timestamp', 'Timestamp');
			self::add_term('dbm_relation:field-type/image', 'Image');
			self::add_term('dbm_relation:field-type/file', 'File');
			self::add_term('dbm_relation:field-type/multiple-files', 'Multiple files');
			self::add_term('dbm_relation:field-type/mobile-phone-number', 'Mobile phone number');
			self::add_term('dbm_relation:field-type/email', 'Email');
			self::add_term('dbm_relation:field-type/relation', 'Relation');
			self::add_term('dbm_relation:field-type/dbm-type', 'Dbm Type');
			self::add_term('dbm_relation:field-type/multiple-relation', 'Multiple relation');
			self::add_term('dbm_relation:field-type/post-relation', 'Post relation');
			self::add_term('dbm_relation:field-type/address', 'Address');
			self::add_term('dbm_relation:field-type/name', 'Name');
			self::add_term('dbm_relation:field-type/data-array', 'Data array');
			self::add_term('dbm_relation:field-type/date', 'Date');
			self::add_term('dbm_relation:field-type/date-time', 'Date time');
			self::add_term('dbm_relation:field-type/boolean', 'Boolean');
			self::add_term('dbm_relation:field-type/json', 'Json');
			self::add_term('dbm_relation:field-type/number', 'Number');
			
			self::add_term('dbm_relation:field-status', 'Field status');
			self::add_term('dbm_relation:field-status/none', 'None');
			self::add_term('dbm_relation:field-status/complete', 'Complete');
			self::add_term('dbm_relation:field-status/verified', 'Verified');
			self::add_term('dbm_relation:field-status/incorrect', 'Incorrect');
			
			self::add_term('dbm_relation:timed-action-status', 'Timed action status');
			self::add_term('dbm_relation:timed-action-status/waiting', 'Waiting');
			self::add_term('dbm_relation:timed-action-status/completed', 'Completed');
			self::add_term('dbm_relation:timed-action-status/cancelled', 'Cancelled');
			
			self::add_term('dbm_relation:field-storage', 'Field storage');
			self::add_term('dbm_relation:field-storage/meta', 'Meta');
			self::add_term('dbm_relation:field-storage/single-relation', 'Single relation');
			self::add_term('dbm_relation:field-storage/multiple-relation', 'Multiple relation');
			self::add_term('dbm_relation:field-storage/relation-flag', 'Relation flag');
			
			self::add_term('dbm_relation:internal-message-groups', 'Internal message groups');
			self::add_term('dbm_relation:page-datas', 'Page datas');
			self::add_term('dbm_relation:link-groups', 'Link groups');
			
			self::add_term('dbm_type:object-relation', 'Object relation');
			self::add_term('dbm_type:object-relation/field-for', 'Field for');
			self::add_term('dbm_type:object-relation/uploaded-to', 'Uploaded to');
			self::add_term('dbm_type:object-relation/message-in', 'Message in');
			
			$current_term_id = self::add_term('dbm_relation:global-pages', 'Global pages');
			$current_term_id = self::add_term('dbm_relation:global-pages/view-internal-message', 'View internal message');
			
			dbmtc_setup_field_template('link-group', 'name', 'string', 'meta', array('dbmtc_meta_name' => 'dbm_name'));
			dbmtc_setup_field_template('link-group', 'links', 'json', 'meta', array('dbmtc_meta_name' => 'dbm_links'));
			
			dbmtc_setup_field_template('page-data', 'parameters', 'json', 'meta', array('dbmtc_meta_name' => 'dbm_page_data_parameters'));
			
			dbmtc_setup_field_template('field-template', 'key', 'string', 'meta', array('dbmtc_meta_name' => 'dbmtc_key'));
			dbmtc_setup_field_template('field-template', 'forType', 'dbm-type', 'meta', array('dbmtc_meta_name' => 'dbmtc_for_type'));
			dbmtc_setup_field_template('field-template', 'type', 'relation', 'single-relation', array('dbmtc_relation_path' => 'field-type', 'subtree' => 'field-type'));
			dbmtc_setup_field_template('field-template', 'storageType', 'relation', 'single-relation', array('dbmtc_relation_path' => 'field-storage', 'subtree' => 'field-storage'));
			
			dbmtc_setup_field_template('uploaded-file', 'fileName', 'string', 'meta', array('dbmtc_meta_name' => 'fileName'));
			dbmtc_setup_field_template('uploaded-file', 'url', 'string', 'meta', array('dbmtc_meta_name' => 'url'));
			
			dbmtc_setup_field_template('object-relation', 'startAt', 'timestamp', 'meta', array('dbmtc_meta_name' => 'startAt'));
			dbmtc_setup_field_template('object-relation', 'endAt', 'timestamp', 'meta', array('dbmtc_meta_name' => 'endAt'));
			
			dbmtc_setup_field_template('object-user-relation', 'startAt', 'timestamp', 'meta', array('dbmtc_meta_name' => 'startAt'));
			dbmtc_setup_field_template('object-user-relation', 'endAt', 'timestamp', 'meta', array('dbmtc_meta_name' => 'endAt'));
			
			$setup_manager = dbm_setup_get_manager();
			
			$current_type = $setup_manager->create_data_type('type/send-status')->set_name('Send status');
			$current_type = $setup_manager->create_data_type('type/tag')->set_name('Tag');
			
			$current_type = $setup_manager->create_data_type('action')->set_name('Action');
			
			$current_type = $setup_manager->create_data_type('type/action-status')->set_name('Action status');
			$current_type = $setup_manager->create_data_type('type/action-type')->set_name('Action type');
			
			$current_type = $setup_manager->create_data_type('trigger')->set_name('Trigger');
			
			$current_type = $setup_manager->create_data_type('type/trigger-type')->set_name('Trigger type');
			
			$current_type = $setup_manager->create_data_type('task')->set_name('Task');
			
			$current_type = $setup_manager->create_data_type('type/task-status')->set_name('Task status');
			$current_type = $setup_manager->create_data_type('type/task-type')->set_name('Task type');
			
			$current_type = $setup_manager->create_data_type('incoming-webhook-event')->set_name('Incoming webhook event');
			$current_type->add_field("payload")->set_type('json')->setup_meta_storage();
			
			$current_type = $setup_manager->create_data_type('request')->set_name('Request');
			$current_type->add_field("url")->setup_meta_storage();
			$current_type->add_field("headers")->set_type('json')->setup_meta_storage();
			$current_type->add_field("body")->set_type('json')->setup_meta_storage();
			$current_type->add_field("curlOptions")->set_type('json')->setup_meta_storage();
			$current_type->add_field("responseCode")->setup_meta_storage();
			$current_type->add_field("response")->set_type('json')->setup_meta_storage();
			
			$current_type = $setup_manager->create_data_type('type/request-method')->set_name('Request method');
			
			$current_type = $setup_manager->create_data_type('timed-action')->set_name('Timed action');
			$current_type->add_field("name")->setup_meta_storage();
			$current_type->add_field("time")->set_type('timestamp')->setup_meta_storage();
			$current_type->add_field("status")->setup_single_relation_storage('timed-action-status');
			$current_type->add_field("action")->setup_meta_storage();
			$current_type->add_field("actionData")->set_type('json')->setup_meta_storage();
			
			$current_type = $setup_manager->create_data_type('interval-action')->set_name('Interval action');
			$current_type->add_field("name")->setup_meta_storage();
			$current_type->add_field("time")->set_type('timestamp')->setup_meta_storage();
			$current_type->add_field("interval")->set_type('json')->setup_meta_storage();
			$current_type->add_field("action")->setup_meta_storage();
			$current_type->add_field("actionData")->set_type('json')->setup_meta_storage();
			
			$setup_manager->save_all();
		}
		
		public static function test_import() {
			echo("Imported \Admin\CustomPostTypes\PluginActivation<br />");
		}
	}
?>
