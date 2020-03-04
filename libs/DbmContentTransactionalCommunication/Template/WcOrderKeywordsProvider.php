<?php
	namespace DbmContentTransactionalCommunication\Template;
	
	// \DbmContentTransactionalCommunication\Template\WcOrderKeywordsProvider
	class WcOrderKeywordsProvider {
		
		protected $order = null;
		
		function __construct() {
			//echo("\DbmContentTransactionalCommunication\Template\WcOrderKeywordsProvider::__construct<br />");
			
		}
		
		public function set_order($order) {
			$this->order = $order;
			
			return $this;
		}
		
		public function get_keyword_replacement($keyword, $template = null) {
			//echo("\DbmContentTransactionalCommunication\Template\WcOrderKeywordsProvider::get_keyword_replacement<br />");
			//var_dump($keyword);
			
			if(!$this->order) {
				return null;
			}
			
			switch($keyword) {
				case 'id':
					return $this->order->get_id();
				case 'firstName':
					return $this->order->get_billing_first_name();
				case 'lastName':
					return $this->order->get_billing_last_name();
				case 'total':
					return $this->order->get_total();
				case 'orderDate':
					return date('Y-m-d', strtotime($this->order->get_date_created()));
				case 'paidDate':
					return date('Y-m-d', strtotime($this->order->get_date_paid()));
				case 'email':
					return $this->order->get_billing_email();
				case 'phoneNumber':
					return $this->order->get_billing_phone();
				case 'items':
					$item_names = array();
					foreach($this->order->get_items() as $item_id => $item_data) {
						$item_names[] = $item_data->get_product()->get_name();
					}
					return implode(',', $item_names);
			}
			
			return null;
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\Template\WcOrderKeywordsProvider<br />");
		}
	}
?>
