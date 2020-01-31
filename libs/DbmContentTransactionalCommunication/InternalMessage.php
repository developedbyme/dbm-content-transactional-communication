<?php
	namespace DbmContentTransactionalCommunication;

	class InternalMessage extends \DbmContent\DbmPost {
		
		protected $group_id = 0;
		
		function __construct($id) {
			//echo("\DbmContentTransactionalCommunication\InternalMessage::__construct<br />");
			
			parent::__construct($id);
		}
		
		public function get_group_id() {
			if(!$this->group_id) {
				$post_types = array_keys(get_post_types(array(), 'names'));
				$group_id = dbm_new_query('all')->set_field('post_type', $post_types)->set_field('post_status', array('publish', 'pending', 'draft', 'future', 'private', 'inherit'))->add_type_by_path('internal-message-group')->add_relations_with_children_from_post($this->get_id(), 'internal-message-groups')->get_post_id();
				$this->group_id = $group_id;
			}
			
			return $this->group_id;
		}
		
		public function get_group() {
			return dbmtc_get_internal_message_group($this->get_group_id());
		}
		
		public function get_view_url() {
			$permalink = $this->get_group()->get_view_url();
			$permalink = add_query_arg('message', $this->get_id(), $permalink);
			
			return $permalink;
		}
		
		public function is_type($type) {
			return dbm_has_post_relation($this->get_id(), 'internal-message-types/'.$type);
		}
		
		public function notify() {
			
			$group = $this->get_group();
			$from_user = get_user_by('id', get_post($this->get_id())->post_author);
			
			$default_email_template_id = dbm_new_query('dbm_additional')->add_relation_by_path('global-transactional-templates/new-internal-message')->get_post_id();
		
			$communications = array();
			
			$from_user_id = 0;
			if($from_user) {
				$from_user_id = $from_user->ID;
			}
			$from_contact = dbmtc_get_user_contact($from_user);
			
			$user_ids = get_post_meta($group->get_id(), 'users_to_notify', true);
			foreach($user_ids as $user_id) {
				if($user_id !== $from_user_id) {
					$current_user = get_user_by('id', $user_id);
					$email = $current_user->user_email;
					
					$to_contact = dbmtc_get_user_contact($current_user);
					
					$email_template_id = apply_filters('dbmtc/im/notification_template_for_user', $default_email_template_id, $current_user, $this);
					
					if(!$email_template_id) {
						continue;
					}
					$template = dbmtc_create_template_from_post($email_template_id);
					
					if($from_contact) {
						$template->add_keywords_provider($from_contact->create_keywords_provider(), 'from');
					}
					$template->add_keywords_provider($to_contact->create_keywords_provider(), 'to');
					$template->add_keywords_provider($this->create_keywords_provider(), 'message');
					$template->add_keywords_provider($group->create_keywords_provider(), 'messageGroup');
					
					do_action('dbmtc/im/setup_notification_template', $template, $current_user, $this);
					
					$content = $template->get_content();
					
					if(!$content['title'] && !$content['content']) {
						continue;
					}
			
					$communications[] = dbm_content_tc_send_email($content['title'], $content['content'], $email, dbmtc_get_default_from_email());
				}
			}
		
			$this->add_meta('sent_notifications', array('time' => time(), 'communications' => $communications));
			
			return $this;
		}
		
		public function testNotification($as_user, $email) {
			
			$group = $this->get_group();
			$from_user = get_user_by('id', get_post($this->get_id())->post_author);
			
			$default_email_template_id = dbm_new_query('dbm_additional')->add_relation_by_path('global-transactional-templates/new-internal-message')->get_post_id();
		
			$from_user_id = 0;
			if($from_user) {
				$from_user_id = $from_user->ID;
			}
			$from_contact = dbmtc_get_user_contact($from_user);
			
			$to_contact = dbmtc_get_user_contact($as_user);
			
			$email_template_id = apply_filters('dbmtc/im/notification_template_for_user', $default_email_template_id, $as_user, $this);
			
			if(!$email_template_id) {
				return false;
			}
			$template = dbmtc_create_template_from_post($email_template_id);
			
			if($from_contact) {
				$template->add_keywords_provider($from_contact->create_keywords_provider(), 'from');
			}
			$template->add_keywords_provider($to_contact->create_keywords_provider(), 'to');
			$template->add_keywords_provider($this->create_keywords_provider(), 'message');
			$template->add_keywords_provider($group->create_keywords_provider(), 'messageGroup');
			
			do_action('dbmtc/im/setup_notification_template', $template, $as_user, $this);
			
			$content = $template->get_content();
			
			if(!$content['title'] && !$content['content']) {
				return false;
			}
	
			dbm_content_tc_send_email($content['title'], $content['content'], $email, dbmtc_get_default_from_email());
			
			return true;
		}
		
		public function get_sent_communications() {
			return get_post_meta($this->get_id(), 'sent_notifications', false);
		}
		
		public function create_keywords_provider() {
			$provider = new \DbmContentTransactionalCommunication\Template\MessageKeywordsProvider();
			
			$provider->set_message($this);
			
			return $provider;
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\InternalMessage<br />");
		}
	}
?>
