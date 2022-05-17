<?php
	namespace DbmContentTransactionalCommunication\Template;
	
	// \DbmContentTransactionalCommunication\Template\WcSubscriptionKeywordsProvider
	class WcSubscriptionKeywordsProvider extends \DbmContentTransactionalCommunication\Template\WcOrderKeywordsProvider {
		
		protected $order = null;
		
		function __construct() {
			//echo("\DbmContentTransactionalCommunication\Template\WcSubscriptionKeywordsProvider::__construct<br />");
			
		}
		
		public function get_keyword_replacement($keyword, $template = null) {
			//echo("\DbmContentTransactionalCommunication\Template\WcSubscriptionKeywordsProvider::get_keyword_replacement<br />");
			//var_dump($keyword);
			
			if(!$this->order) {
				return null;
			}
			
			switch($keyword) {
				case 'endDate':
					$date = $this->order->get_date('end');
					if(!$date) {
						return "-";
					}
					return date('Y-m-d', strtotime($date));
			}
			
			return parent::get_keyword_replacement($keyword, $template);
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\Template\WcSubscriptionKeywordsProvider<br />");
		}
	}
?>
