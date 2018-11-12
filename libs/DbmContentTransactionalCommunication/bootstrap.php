<?php

function DbmContentTransactionalCommunication_Autoloader( $class ) {
	//echo("DbmContentTransactionalCommunication_Autoloader<br />");
	
	$namespace_length = strlen("DbmContentTransactionalCommunication");
	
	// Is a DbmContentTransactionalCommunication class
	if ( substr( $class, 0, $namespace_length ) != "DbmContentTransactionalCommunication" ) {
		return false;
	}

	// Uses namespace
	if ( substr( $class, 0, $namespace_length+1 ) == "DbmContentTransactionalCommunication\\" ) {

		$path = explode( "\\", $class );
		unset( $path[0] );

		$class_file = trailingslashit( dirname( __FILE__ ) ) . implode( "/", $path ) . ".php";

	}

	// Doesn't use namespaces
	elseIf ( substr( $class, 0, $namespace_length+1 ) == "DbmContentTransactionalCommunication_" ) {

		$path = explode( "_", $class );
		unset( $path[0] );

		$class_file = trailingslashit( dirname( __FILE__ ) ) . implode( "/", $path ) . ".php";

	}

	// Get class
	if ( isset($class_file) && is_file( $class_file ) ) {

		require_once( $class_file );
		return true;

	}

	// Fallback to error
	return false;

}

spl_autoload_register("DbmContentTransactionalCommunication_Autoloader"); // Register autoloader