<?php
	namespace DbmContentTransactionalCommunication;
	
	use \WP_Query;
	
	// \DbmContentTransactionalCommunication\ChangePostHooks
	class ChangePostHooks {
		
		function __construct() {
			//echo("\DbmContentTransactionalCommunication\ChangePostHooks::__construct<br />");
			
			
		}
		
		protected function register_hook_for_type($type, $hook_name) {
			add_action('wprr/admin/change_post/'.$type, array($this, $hook_name), 10, 2);
		}
		
		public function register() {
			//echo("\DbmContentTransactionalCommunication\ChangePostHooks::register<br />");
			
			
			
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\ChangePostHooks<br />");
		}
	}
?>