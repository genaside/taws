<?php
  require_once './config/server_config.php';  
  
  /**
  * @class GenericSQL A wrapper to be used by taws to wrap 
  * the sqlite,mysql,postgres sql programs.
  */
  class GenericSQL{
    // Variables
    //private $dbconn = null; // Object to sql
    //private $driver = null;
    
    // Functions    
    function __construct() { 
      //$driver = new mysqli_driver();
      //$driver->report_mode = MYSQLI_REPORT_ALL;
    }
    function __destruct() {
        
    }
    
    /**
    * Function to make a mysql,sqlite, or postgresql
    * @return A object to the sql, else return null if fail
    */
    function connect(){
      global $taws_server_config;   
      
      if( $taws_server_config[ "sql_program" ] == 'mysql' ){    
        $this->dbconn = new mysqli( $taws_server_config[ 'mysql_server' ], 
                                     $taws_server_config[ 'mysql_db_user' ], 
                                     $taws_server_config[ 'mysql_db_pass' ], 
                                     $taws_server_config[ 'mysql_db_name' ],
                                     $taws_server_config[ 'mysql_db_port' ]); 
        //$this->dbconn->options( MYSQLI_OPT_LOCAL_INFILE, true );
        
      }else if( $taws_server_config[ "sql_program" ] == 'postgresql' ){    
        $this->dbconn = pg_connect( "host={$taws_server_config[ 'postgresql_server' ]}                       
                                     user={$taws_server_config[ 'postgresql_db_user' ]} 
                                     password={$taws_server_config[ 'postgresql_db_pass' ]}
                                     dbname={$taws_server_config[ 'postgresql_db_name' ]} 
                                     port={$taws_server_config[ 'postgresql_db_port' ]}" );                             
      }else if( $taws_server_config[ "sql_program" ] == 'sqlite' ){    
        $this->dbconn = new SQLite3( $taws_server_config[ 'sqlite_db_path' ], SQLITE3_OPEN_READONLY );       
      }else{    
        // Not supported
      }  
    
      // return true if successfull
      if( $this->dbconn ){
        return true;
      }else{
        return false;
      }
    }     
    
    /**
    * Close a database
    * Also try the alter the query for specific sql***
    * @param dbconn An open connection of the DB  
    */
    function disconnect(){
      global $taws_server_config;
    
      if( $taws_server_config[ "sql_program" ] == 'mysql' ){
        $this->dbconn->close();
      }else if( $taws_server_config[ "sql_program" ] == 'postgresql' ){
        pg_close( $this->dbconn );
      }else if( $taws_server_config[ "sql_program" ] == 'sqlite' ){
        $this->dbconn->close();
      }else{
        // Not supported
      }  
    }
    
    /**
    * Run a SQL query with no expected results.
    * This function will also run multiple statments,
    * just make sure to use the semicolon to Seperate statments
    * @param sql_query Query to perform
    * @return true if successfull
    * @warning Only use general sql queries, some sql queries are not capitable with others
    * @NOTE using multi_query() for mysql is too wierd
    */
    function exec( $sql_query  ){
      global $taws_server_config;
      
      // Delete commented lines -- or /**/
      $sql_query = preg_replace( '/^--.*$/m', '', $sql_query );
      
      // Seperate Queries
      $statements = explode( ';', $sql_query );
      
      // Remove empty elements
      $is_empty = function( $var ){
        if( !ctype_space( $var ) || $var != "" ){
          return true;        
        }      
      };
      $statements = array_filter( $statements, $is_empty );
           
      if( $taws_server_config[ "sql_program" ] == 'mysql' ){    
        foreach( $statements as $elem ){          
          if( $elem != '' ){
            try{
              $this->dbconn->query( $elem );
            }catch( Exception $e ){
              echo $e;              
            }            
          }          
        }              
      }else if( $taws_server_config[ "sql_program" ] == 'postgresql' ){    
        $results = pg_query( $this->dbconn, $sql_query );      
      }else if( $taws_server_config[ "sql_program" ] == 'sqlite' ){    
        $results = $this->dbconn->query( $sql_query );
      }
      
    }
    
    /**
    * This will try to take a query and run it under the correct sql.
    * Also try the alter the query for specific sql***
    * @param dbconn An open connection of the DB
    * @param sql_query Query to perform
    * @return results of return from query
    * @warning Only use general sql queries, somethings are not capitable with others
    */
    function query( $sql_query  ){
      global $taws_server_config;
    
      $results = null;      
      
      if( $taws_server_config[ "sql_program" ] == 'mysql' ){    
        $results = $this->dbconn->query( $sql_query );      
      }else if( $taws_server_config[ "sql_program" ] == 'postgresql' ){    
        $results = pg_query( $this->dbconn, $sql_query );      
      }else if( $taws_server_config[ "sql_program" ] == 'sqlite' ){    
        $results = $this->dbconn->query( $sql_query );
      }
      
      if( !$results ){
	return null;
      }
      return new GenericSQL_Results( $results );     
    }
    
    // vvv Seperate to different class vvv
    
    /**
    * Prepare and bind in one function    
    * @param sql_query Query to prepare
    * @param binds the array of values to bind
    */
    function prepareAndBind( $sql_query, $binds ){ 
      global $taws_server_config;
      
      $results = null;
      if( $taws_server_config[ "sql_program" ] == 'mysql' ){
	$stmt = $this->dbconn->prepare( $sql_query ); 
	if( !$stmt ){
	  return null;
	}
	
	$types = '';
	foreach( $binds as $type ){
	  // The database just has strings and ints
	  if( !is_numeric( $type ) ){
	    $types .= 's'; 
	  }else{
	    $types .= 'i'; 
	  }
	}      
	array_unshift( $binds, $types );
	      
	call_user_func_array( array( &$stmt, 'bind_param' ), $this->refValues( $binds ) );      
	$stmt->execute();    
	$results = $stmt->get_result();      
      }else if( $taws_server_config[ "sql_program" ] == 'postgresql' ){   
	pg_prepare( $this->dbconn, "stmt", '$sql_query' );
	$results = pg_execute( $this->dbconn, "stmt", $binds );          
      }else if( $taws_server_config[ "sql_program" ] == 'sqlite' ){
	$stmt = $this->dbconn->prepare( $sql_query );  
	
	$count = 0;      
	foreach( $binds as &$value ){
	  ++$count;
	  if( !is_numeric( $value ) ){
	    $stmt->bindParam( $count, $value, SQLITE3_TEXT );
	  }else{
	    $stmt->bindParam( $count, $value, SQLITE3_INTEGER );
	  }
	} 
	$results = $stmt->execute();       
      }    
      return new GenericSQL_Results( $results );
    }
    
    function refValues( $arr ){
      if( strnatcmp( phpversion(), '5.3' ) >= 0){
	  $refs = array();
	  foreach($arr as $key => $value)
	      $refs[$key] = &$arr[$key];
	  return $refs;
      }
      return $arr;
    }
    
  }
  
  class GenericSQL_Results{
  
    private $results = null;
  
    /**
    * Constructor
    * @param results the result passed from the main sql
    */
    function __construct( $resultset ){     
      $this->results = $resultset;      
    }
    
    /**
    * Fetch row from results, associative ONLY      
    * @return A row from results
    */
    Function fetchRow(){
      global $taws_server_config;
    
      $row = null;
      if( $taws_server_config[ "sql_program" ] == 'mysql' ){
        $row = $this->results->fetch_assoc();      
      }else if( $taws_server_config[ "sql_program" ] == 'postgresql' ){
        $row = pg_fetch_assoc( $result );
      }else if( $taws_server_config[ "sql_program" ] == 'sqlite' ){      
        $row = $this->results->fetchArray( SQLITE3_ASSOC );
      }else{
        // Not supported
      }  
      return $row;    
    }      
    
    /**  
    * Count the number of rows in a result    
    * @return The number of rows
    */
    function getNumberOfRows(){
      global $taws_server_config;
      
      $num = 0;
      if( $taws_server_config[ "sql_program" ] == 'mysql' ){
	$num = $this->results->num_rows;
      }else if( $taws_server_config[ "sql_program" ] == 'postgresql' ){
	$num = pg_num_rows( $this->results );
      }else if( $taws_server_config[ "sql_program" ] == 'sqlite' ){
	// I have to count it myself, since php's sqlite3 doesn't have it
	$this->results->reset();
	while( $this->results->fetchArray() ){
	  $num++;
	}        
      }else{
	// Not supported
      } 
      return $num;  
    }  
    
    
  }
  
  
  // Testing
  /*
  $gs = new GenericSQL;
  $gs->connect();
  $result = $gs->query( "SELECT * FROM ubm LIMIT 10;" );
  //echo $result->getNumberOfRows();
  $row = $result->fetchRow();
  var_dump( $row );
  $gs->disconnect();
  */

?>