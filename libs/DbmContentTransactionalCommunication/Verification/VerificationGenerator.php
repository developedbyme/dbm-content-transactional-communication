<?php
	namespace DbmContentTransactionalCommunication\Verification;
	
	// \DbmContentTransactionalCommunication\Verification\VerificationGenerator
	class VerificationGenerator {
		
		protected $code_range_min = 100000;
		protected $code_range_max = 999999;
		
		protected $hash_salt = null;
		protected $verifiction_type = null;
		
		function __construct() {
			//echo("\DbmContentTransactionalCommunication\Verification\VerificationGenerator::__construct<br />");
			
		}
		
		public function set_code_range($min, $max) {
			$this->code_range_min = $min;
			$this->code_range_max = $max;
			
			return $this;
		}
		
		public function set_salt($salt) {
			$this->hash_salt = $salt;
			
			return $this;
		}
		
		public function get_salt() {
			if($this->hash_salt === null) {
				$this->hash_salt = apply_filters('dbmtc/verification/default_salt', 'Tw?otIAwI%ourB-:@VeZ4tGLY0=Twh)1J Wwhxc!5AOg:*L$Ff@CAY+d-iW47Ztm', $this);
			}
			
			return $this->hash_salt;
		}
		
		public function set_type($verifiction_type) {
			$this->verifiction_type = $verifiction_type;
			
			return $this;
		}
		
		public function generate($value) {
			
			$code = mt_rand($this->code_range_min, $this->code_range_max);
			
			$hash = md5($value.$this->get_salt());
			
			$data_id = dbm_create_data('Verification '.$hash, 'address-verification', 'admin-grouping/address-verifications');
			$verification = dbmtc_get_verification($data_id);
			if($this->verifiction_type) {
				$verification->add_type_by_name('address-verification/'.$this->verifiction_type);
			}
			
			$verification->update_meta('verification_hash', $hash);
			$verification->update_meta('verification_code', $code);
			$verification->update_meta('verified', false);
			$verification->change_status('private');
			
			return $verification;
		}
		
		public function generate_and_send_to_user($value, $user, $preferred_order = null) {
			$verification = $this->generate($value);
			$verification_id = $verification->get_id();
			
			$user_id = $user->ID;
			$data_dbm_post->update_meta('user_id', $user_id);
			$send_methods = $this->get_preferred_send_methods_for_user($user, $preferred_order);
			
			foreach($send_methods as $method => $data) {
				$this->perform_send($verification_id, $method, $data);
			}
		}
		
		public function perform_send($verification_id, $method, $data, $to) {
			do_action('dbmtc/send_verification/'.$method, $verification_id, $data, $to);
		}
		
		public static function test_import() {
			echo("Imported \DbmContentTransactionalCommunication\Verification\VerificationGenerator<br />");
		}
	}
?>
