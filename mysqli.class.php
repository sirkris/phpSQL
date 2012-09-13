<?php

/**
 * phpSQL : php_mysqli
 * 
 * This is the recommended class if you're connecting to a MySQL database server.
 */

require_once( "config_sql.class.php" );

class SQL extends Config_SQL
{
	public function __construct()
	{
		$args = func_get_args();
		
		foreach ( $args as $argarr )
		{
			foreach ( $argarr as $key => $value )
			{
				$this->$key = $value;
			}
		}
		
		/* Load the override settings, if specified.  --Kris */
		if ( isset( $this->sql_obj ) && isset( $this->sql_obj[$this->sql_obj] ) )
		{
			foreach ( $this->sql_obj[$this->sql_obj] as $var => $val )
			{
				$this->$var = $val;
			}
		}
		
		if ( $this->sql_ssl_on == TRUE )
		{
			$this->db = mysqli_init();
			
			/*
			 * Currently BROKEN!  Possibly due to bug in MySQL.  See bug report for details:
			 * 
			 * http://bugs.mysql.com/bug.php?id=64870
			 * 
			 * --Kris
			 */
			//$this->db->ssl_set( $this->sql_ssl_key, $this->sql_ssl_cert, $this->sql_ssl_ca, NULL, NULL );
			
			$this->db->real_connect( $this->sql_host, $this->sql_user, $this->sql_pass, $this->sql_db, 
						$this->sql_port, NULL, MYSQLI_CLIENT_SSL );			
		}
		else
		{
			$this->db = new mysqli( $this->sql_host, $this->sql_user, $this->sql_pass, 
						$this->sql_db, $this->sql_port );
		}
	}
	
	public function query( $query, $params = array(), $returntype = 1 )
	{
		$stmt = $this->db->prepare( $query ) or die( "Error preparing SQL statement : " . $this->db->error );
		
		$args = array();
		$args[0] = NULL;
		foreach ( $params as $var => $val )
		{
			if ( is_numeric( $val ) && strcmp( intval( $val ), $val ) == 0 )
			{
				$vartype = "i";
			}
			else if ( is_numeric( $val ) )
			{
				$vartype = "d";
			}
			else
			{
				$vartype = "s";
			}
			
			$$var = $val;
			
			$args[0] .= $vartype;
			$args[] = $$var;
		}
		
		if ( !empty( $params ) )
		{
			call_user_func_array( array( $stmt, "bind_param" ), self::sanitize( $args ) );
		}
		
		$stmt->execute();
		
		switch ( $returntype )
		{
			default:
			case 0:
				$returnval = NULL;
				break;
			case 1:
				$returnval = self::fetch( $stmt );
				break;
			case 2:
				$returnval = $stmt->affected_rows;
				break;
			case 3:
				$stmt->store_result();
				$returnval = $stmt->num_rows();
				break;
			case 4:
				$returnval = $stmt;
				break;
		}
		
		$stmt->close();
		
		return $returnval;
	}
	
	/* Original function by fabio at kidopi dot com dot br, posted at http://www.php.net/manual/en/mysqli-stmt.bind-param.php.  --Kris */
	public function sanitize( $arr )
	{
		if ( strnatcmp( phpversion(), "5.3" ) >= 0 )
		{
			$arr2 = array();
			
			foreach ( $arr as $arrkey => $arrval )
			{
				$arr2[$arrkey] =& $arr[$arrkey];
			}
			
			return $arr2;
		}
		else
		{
			return $arr;
		}
	}
	
	/* Original function by nieprzeklinaj at gmail dot com, posted at http://www.php.net/manual/en/mysqli-stmt.bind-result.php.  --Kris */
	public function fetch( $result )
	{   
		$array = array();
		
		if ( $result instanceof mysqli_stmt )
		{
			$result->store_result();
			   
			$variables = array();
			$data = array();
			$meta = $result->result_metadata();
			   
			while ( $field = $meta->fetch_field() )
			{
				$variables[] = &$data[$field->name]; // pass by reference
			}
			   
			call_user_func_array( array( $result, "bind_result" ), $variables );
			   
			$i = 0;
			while( $result->fetch() )
			{
				$array[$i] = array();
				foreach ( $data as $k => $v )
				{
					$array[$i][$k] = $v;
				}
				
				$i++;
			}
		}
		else if( $result instanceof mysqli_result )
		{
			while ( $row = $result->fetch_assoc() )
			{
				$array[] = $row;
			}
		}
			   
		return $array;
	}
	
	public function close()
	{
		return $this->db->close();
	}
	
	public function build_where_clause( $columns = array(), $values = array(), $and = TRUE )
	{
		if ( ( empty( $columns ) && empty( $values ) ) 
			|| count( $columns ) != count( $values ) )
		{
			return FALSE;
		}
		
		if ( !is_array( $columns ) )
		{
			$columns = array( $columns );
		}
		
		if ( !is_array( $values ) )
		{
			$values = array( $values );
		}
		
		$andor = ( $and == TRUE ? "AND" : "OR" );
		
		$where = NULL;
		foreach ( $columns as $ckey => $col )
		{
			if ( $where != NULL )
			{
				$where .= " $andor ";
			}
			
			$where .= "$col = ?";
		}
		
		return array( 0 => $where, 1 => $values );
	}
	
	public function addescape( $string )
	{
		return addslashes( $string );
	}
}
