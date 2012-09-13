<?php

/**
 * phpSQL : php_mysql
 * 
 * WARNING:  Use of this class is NOT recommended!!  The php_mysql extension does not support prepared statements, among other things.
 * This class gets around that by simulating them, but your script will still be vulnerable to SQL injection attacks!
 * 
 * This class is intended for isolated legacy support cases.  It is strongly recommended that you use php_mysqli instead!
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
		
		$this->link = mysql_connect( $this->sql_host, $this->sql_user, $this->sql_pass ) or die( "Unable to connect to MySQL : " . mysql_error() );
		
		mysql_select_db( $this->sql_db, $this->link ) or die( "Unable to connect to database : " . mysql_error() );
	}
	
	function query( $query, $params = array(), $returntype = 1 )
	{
		/* Prepared statements aren't supported, so let's just cheat and convert them.  --Kris */
		if ( !empty( $params ) )
		{
			if ( substr_count( $query, "?" ) != count( $params ) )
			{
				die( "(php_mysql Conversion Error):  Number of variables doesn't match number of parameters in prepared statement." );
			}
			
			$parseloop = 0;
			$newquery = NULL;
			foreach ( $params as $pkey => $val )
			{
				for ( $parseloop = $parseloop; $parseloop < strlen( $query ); $parseloop ++ )
				{
					if ( strcmp( $query{$parseloop}, "?" ) == 0 )
					{
						if ( is_numeric( $val ) )
						{
							$newquery .= $val;
						}
						else
						{
							$newquery .= "'" . $val . "'";
						}
						
						$parseloop++;
						break;
					}
					else
					{
						$newquery .= $query{$parseloop};
					}
				}
			}
			
			for ( $parseloop = $parseloop; $parseloop < strlen( $query ); $parseloop++ )
			{
				$newquery .= $query{$parseloop};
			}
			
			$query = $newquery;
		}
		
		$result = mysql_query( $query ) or die( "Query '$query' failed : " . mysql_error() );
		
		switch ( $returntype )
		{
			default:
			case 0:
				$returnvar = NULL;
				break;
			case 1:
				$returnvar = self::fetch( $result );
				break;
			case 2:
				$returnvar = mysql_affected_rows();
				break;
			case 3:
				$returnvar = mysql_num_rows( $result );
				break;
			case 4:
				$returnvar = $result;
				break;
		}
		
		return $returnvar;
	}
	
	function fetch( $result )
	{
		$resdata = array();
		while ( $line = mysql_fetch_array( $result, MYSQL_ASSOC ) )
		{
			$tmp = array();
			$sqloop = 0;
			foreach ( $line as $col_value )
			{
				$fieldname = mysql_field_name( $result, $sqloop );
				if ( strcmp( intval( $col_value ), $col_value ) == 0 )
				{
					/* To mimic the mysqli behavior as closely as possible.  --Kris */
					$tmp[$fieldname] = (int) $col_value;
				}
				else
				{
					$tmp[$fieldname] = $col_value;
				}
				$sqloop++;
			}
			
			$resdata[] = $tmp;
		}
		
		return $resdata;
	}
	
	function close()
	{
		return mysql_close( $this->link );
	}
	
	function build_where_clause( $columns = array(), $values = array(), $and = TRUE )
	{
		if ( ( empty( $columns ) && empty( $values ) ) 
			|| count( $columns ) != count( $values ) )
		{
			return FALSE;
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
	
	function addescape( $string )
	{
		return addslashes( $string );
	}
}
