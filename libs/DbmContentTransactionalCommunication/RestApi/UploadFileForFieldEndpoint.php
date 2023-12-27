<?php
	namespace DbmContentTransactionalCommunication\RestApi;

	use \WP_Query;
	use \DbmContentTransactionalCommunication\OddCore\RestApi\EndPoint as EndPoint;

	// \DbmContentTransactionalCommunication\RestApi\UploadFileForFieldEndpoint
	class UploadFileForFieldEndpoint extends EndPoint {

		function __construct() {
			// echo("\OddCore\RestApi\UploadFileForFieldEndpoint::__construct<br />");
		}
		
		protected function create_folders_and_move_file($full_path, $temp_path) {
			
			$parts = explode('/', $full_path);
			$file = array_pop($parts);
			$dir = '';
			
			foreach($parts as $part) {
				if(!is_dir($dir .= "/$part")) {
					mkdir($dir);
				}
			}
			
			return move_uploaded_file($temp_path, $full_path);
		}

		public function perform_call($data) {
			//echo("\OddCore\RestApi\UploadFileForFieldEndpoint::perform_call<br />");
			
			if(count($_FILES) !== 1) {
				return $this->output_error('Endpoint only excepts 1 file exactly');
			}
			
			try {
				$file_keys = array_keys($_FILES);
				$file = $_FILES[$file_keys[0]];
				$original_name = $file['name'];
				
				$group_id = $data['group'];
				$field_name = $data['field'];
				
				$supported_extensions = apply_filters('dbmtc/supported_extensions', array('pdf', 'word', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'txt', 'json', 'xlsx', 'xls', 'rtf'), $group_id, $field_name);
				
				$extension = strtolower(pathinfo($original_name)['extension']);
				$is_supported = in_array($extension, $supported_extensions);
				$is_supported = apply_filters('dbmtc/supported_extension', $is_supported, $extension, $group_id, $field_name);
				
				if($extension === 'php' || $extension === 'cgi' || !$is_supported) {
					return $this->output_error('Unsupported format');
				}
			
				$wp_upload_dir = wp_upload_dir(null, false);
			
				$group = dbmtc_get_internal_message_group($group_id);
				$field_id = $group->get_field($field_name)->get_id();
			
				if(!$field_id) {
					return $this->output_error('No field named '.$field_name);
				}
			
				$field_type_id = dbm_get_single_post_relation($field_id, 'field-type');
				$field_type = get_term_by('id', $field_type_id, 'dbm_relation')->slug;
			
				$is_valid_field = ($field_type === 'file' || $field_type === 'image' || $field_type === 'multiple-files');
				//METODO: add filter for valid field types
				if(!$is_valid_field) {
					return $this->output_error('Field doesn\'t support upload');
				}
				if($field_type === 'image') {
					$content_type = mime_content_type($file['tmp_name']);
					switch($content_type) {
						case 'image/jpeg':
						case 'image/png':
						case 'image/gif':
						case 'image/svg+xml':
						case 'application/pdf':
							break;
						default:
							return $this->output_error('Unsupported image format '.$content_type);
					}
				}
			
				$file_name = time().'-'.uniqid().'.'.$extension;
				$path_to_file = '/dbmtc/groups/'.$group_id.'/'.$field_name.'/'.$file_name;
			
				$moved = $this->create_folders_and_move_file($wp_upload_dir['basedir'].$path_to_file, $file['tmp_name']);
			
				if(!$moved) {
					return $this->output_error('Could not move uploaded file');
				}
			
				$url = $wp_upload_dir['baseurl'].$path_to_file;
			
				$uploaded_file_id = dbm_create_data($original_name, 'uploaded-file', 'uploaded-files');
				$uploaded_file_group = dbmtc_get_internal_message_group($uploaded_file_id);
				$uploaded_file_group->set_field('fileName', $original_name);
				$uploaded_file_group->set_field('url', $url);
				$uploaded_file_group->add_outgoing_relation_by_name($group_id, 'uploaded-to');
				$uploaded_file_group->change_status('private');
			
				$field_data = array(
					'id' => $uploaded_file_id,
					'name' => $original_name,
					'url' => $url
				);
			
				if($field_type === 'multiple-files') {
					$file_list = $group->get_field_value($field_name);
					$file_list[] = $field_data;
					$group->set_field($field_name, $file_list);
					$file_list = $group->get_field_value($field_name);
				}
				else {
					$group->set_field($field_name, $field_data);
				}
			
				return $this->output_success($field_data);
			}
			catch(\Exception $error) {
				return $this->output_error($error->getMessage());
			}
		}

		public static function test_import() {
			echo("Imported \OddCore\RestApi\UploadFileForFieldEndpoint<br />");
		}
	}
