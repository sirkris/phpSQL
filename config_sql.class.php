<?php

/*
 * This file is included in the Git repo but changes to it should NOT be tracked unless you are making a structural change!  
 * If you want to track it, remove it from .gitignore (but don't commit that change or I will be very annoyed with you!) 
 * and enter the following command in the Git working directory:  git update-index --no-assume-unchanged config_sql.class.php
 * When you're done, re-add it to .gitignore and enter the following command:  git update-index --assume-unchanged config_sql.class.php
 * 
 * If you just cloned this and this file is being tracked, use the update-index --assume-unchanged command above and that should fix it.
 * 
 * --Kris
 */

define( "SQL_RETURN_NULL", 0 );
define( "SQL_RETURN_RESULTS", 1 );  // Default.
define( "SQL_RETURN_AFFECTEDROWS", 2 );
define( "SQL_RETURN_NUMROWS", 3 );
define( "SQL_RETURN_OBJECT", 4 );

define( "SQL_AND", TRUE );
define( "SQL_OR", FALSE );

require_once( "config.parent.php" );

class Config_SQL extends Config
{
	/* This function contains all the configuration settings that you can change.  --Kris */
	private function config_settings()
	{
		/* The SQL server type (essentially the DB class filename prefix).  --Kris */
		$this->sql_type = "mysqli";  // Currently supported types:  mysql, mysqli
		
		/* The host of the SQL server.  --Kris */
		$this->sql_host = "127.0.0.1";  // Tip:  Use "127.0.0.1" instead of "localhost" to avoid a Windows IPv6 bug in PHP 5.2.x.  --Kris
		
		/* The port that the SQL server is listening on.  --Kris */
		$this->sql_port = 3306;
		
		/* The SQL username.  --Kris */
		$this->sql_user = "phpnova";
		
		/* The SQL password is set in config_sql_secure.php.  --Kris */
		
		/* The SQL database.  --Kris */
		$this->sql_db = "phpnova";
		
		/*
		 * WARNING : SSL support for MySQL is only partially supported by phpSQL at this time!  Client cert authentication is broken!
		 * 
		 * --Kris
		 */
		
		/* Use SSL?  --Kris */
		$this->sql_ssl_on = FALSE;
		
		/* The path to the SSL key file.  --Kris */
		$this->sql_ssl_key = "/var/www/certs/phpNova-MySQL-Certs/clients/client_phpnova-seattle-web_key.pem";
		
		/* The path to the SSL certificate file.  --Kris */
		$this->sql_ssl_cert = "/var/www/certs/phpNova-MySQL-Certs/clients/client_phpnova-seattle-web_cert.pem";
		
		/* The path to the SSL certificate authority file.  --Kris */
		$this->sql_ssl_ca = "/var/www/certs/phpNova-MySQL-Certs/ca-cert.pem";
		
		/* The path to a directory that contains trusted SSL CA certificates in PEM format.  --Kris */
		$this->sql_ssl_ca_dir = NULL;
		
		/* A list of allowable ciphers to use for SSL encryption.  --Kris */
		// http://www.openssl.org/docs/apps/ciphers.html
		//$this->sql_ssl_ciphers = "DHE-RSA-AES256-SHA";
		$this->sql_ssl_ciphers = NULL;
		
		/*
		 * Configuration objects for additional SQL connections.
		 * 
		 * Create as many objects here as you like.  Each one can contain its own separate configuration options.  
		 * This allows you to have connections to multiple separate SQL databases using the same generic database class.  
		 * If a setting is omitted, it will default to its counterpart above.  The array keys do not matter (i.e. they 
		 * can be strings or just a simple increment, etc) so long as each is unique.
		 * 
		 * To use one of these objects, simply pass it via that "obj" key when instantiating, as follows:
		 * 
		 * $sql_mediawiki = new sql( array( "config" => $this->config, "obj" => $this->config->$sql_obj["mediawiki"] ) );
		 * 
		 * --Kris
		 */
		$this->sql_obj = array();
		
		/* The database include.  --Kris */
		$this->paths["sql_class"] = array( "path" => $this->sql_type . ".class.php", "perms" => array( "R" ), "type" => "file", "create" => FALSE );
		
		/* Configuration objects for additional SQL connections.  --Kris */
		
		// Example - Connect to separate host and db, but everything else (login info, etc) is the same.
		// $this->sql_obj["mediawiki"] = (object) array( "sql_db" => "mediawiki", "sql_host" => "my-mediawiki-server.com" );
		
		$this->sql_obj["another_db"] = (object) array( 
								"sql_host" => "some_host.com", 
								"sql_db" => "kris_craig_enemies_list", 
								"sql_ssl_on" => TRUE 
							);
	}
	
	/*
	 * ----------------------------
	 * DO NOT EDIT BELOW THIS LINE!
	 * ----------------------------
	 */
	public function __construct()
	{
		$this->config_settings();
		$this->setup_ext();
		$this->setup_paths();
		$this->qa();
	}
	
	/* Load the database.  --Kris */
	public function load_db()
	{
		require_once( $this->paths["sql_class"]["path"] );
		
		return ( new sql() );
	}
	
	private function setup_ext()
	{
		require( "config_sql_secure.php" );
	}
}
