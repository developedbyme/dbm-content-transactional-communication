<?php
	namespace DbmContentTransactionalCommunication\OddCore\RestApi;
	
	use \WP_Query;
	use \WP_REST_Response;
	use \WP_Error;
	
	class EndPoint {
		
		protected $_namespace = 'oddapi';
		protected $_version = 1;
		protected $_method = 'GET';
		protected $_path = null;
		protected $_input_settings = array();
		protected $_headers = array();
		protected $_requiered_capability = null;
		
		function __construct() {
			//echo("\OddCore\RestApi\EndPoint::__construct<br />");
			
			
		}
		
		public function add_headers($headers) {
			
			foreach($headers as $key => $value) {
				$this->_headers[$key] = $value;
			}
			
			return $this;
		}
		
		public function setup($path, $namespace = 'oddapi', $version = 1, $method = 'GET') {
			//echo("\OddCore\RestApi\EndPoint::setup<br />");
			
			$this->_namespace = $namespace;
			$this->_version = $version;
			$this->_method = $method;
			$this->_path = $path;
			
			return $this;
		}
		
		public function set_requiered_capability($capability) {
			$this->_requiered_capability = $capability;
			
			return $this;
		}
		
		protected function output_success($data) {
			
			$return_response = new WP_REST_Response(array('code' => 'success', 'data' => $data));
			
			foreach($this->_headers as $key => $value) {
				$return_response->header($key, $value);
			}
			
			return $return_response;
		}
		
		protected function output_error($message, $additional_data = null) {
			
			return new WP_Error('error', $message, $additional_data);
		}
		
		public function perform_call($data) {
			//echo("\OddCore\RestApi\EndPoint::perform_call<br />");
			
			return $this->output_error("End point not implemented");
		}
		
		public function hook_perform_call($data) {
			return $this->perform_call($data);
		}
		
		public function hook_check_permission($request) {
			//echo("\OddCore\RestApi\EndPoint::hook_check_permission<br />");
			return current_user_can($this->_requiered_capability);
		}
		
		public function register_hooks() {
			//echo("\OddCore\RestApi\EndPoint::register_hooks<br />");
			
			$options = array(
				'methods' => $this->_method,
				'callback' => array($this, 'hook_perform_call'),
				'args' => $this->_input_settings
			);
			
			if($this->_requiered_capability) {
				$options['permission_callback'] = array($this, 'hook_check_permission');
			}
			else {
				$options['permission_callback'] = '__return_true';
			}
			
			register_rest_route(
				$this->_namespace.'/v'.$this->_version,
				$this->_path,
				$options
			);
		}
		
		public static function test_import() {
			echo("Imported \OddCore\RestApi\EndPoint<br />");
		}
	}
?>