<?php
/*
	Plugin Name: DBM content transactional communication
	Plugin URI: http://developedbyme.com
	Description: Transactional communication functionality for DBM content
	Version: 0.1.0
	Author: Mattias Ekenedahl
	Author URI: http://developedbyme.com
	License: MIT
*/



/* ====================================================================
|  SETUP AND GENERAL
|  General features and setup actions
'---------------------------------------------------------------------- */

define("DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_VERSION", "0.1.0");
define("DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_DOMAIN", "dbm-content-transactional-communication");
define("DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_TEXTDOMAIN", "dbm-content-transactional-communication");
define("DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_MAIN_FILE", __FILE__);
define("DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_DIR", untrailingslashit( dirname( __FILE__ )  ) );
define("DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_URL", untrailingslashit( plugins_url('',  __FILE__ )  ) );

// Plugin textdomain: dbm-content-transactional-communication
function dbm_content_transactional_communication_load_textdomain() {
	
	load_plugin_textdomain( DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_TEXTDOMAIN, false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

}
add_action( 'plugins_loaded', 'dbm_content_transactional_communication_load_textdomain' );

require_once( DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_DIR . "/libs/DbmContentTransactionalCommunication/bootstrap.php" );
//require_once('vendor/autoload.php');

global $DbmContentTransactionalCommunicationPlugin;
$DbmContentTransactionalCommunicationPlugin = new \DbmContentTransactionalCommunication\Plugin();

require_once( DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_DIR . "/register-acf-fields.php" );
require_once( DBM_CONTENT_TRANSACTIONAL_COMMUNICATION_DIR . "/external-functions.php" );

function dbm_content_transactional_communication_plugin_activate() {
	global $DbmContentTransactionalCommunicationPlugin;
	$DbmContentTransactionalCommunicationPlugin->activation_setup();
}
register_activation_hook( __FILE__, 'dbm_content_transactional_communication_plugin_activate' );

?>