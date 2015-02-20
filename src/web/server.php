<?php
  require './includes/php/sphinxapi.php';
  require './config/server_config.php';  
  include './multisqlwrapper.php';  
  include './logger.php';  
  
  //session_start();
  
  //error_reporting( 0 ); // Iam going to use a logger
  date_default_timezone_set( 'UTC' ); 
  set_time_limit( 5 ); // Don't let it run for too long
    
  /**
  * File search.
  * @param search A string to search.
  */
  function runFileSearch( $searchstring ){
    global $taws_server_config;
    
    $s = new SphinxClient();
    $s->setServer( $taws_server_config[ 'sphinx_server' ], $taws_server_config[ 'sphinx_port' ] );
    $s->setMatchMode( SPH_MATCH_EXTENDED2 );
    $s->setMaxQueryTime( 300 ); 
    $s->SetLimits( (int)$_POST[ 'offset' ], (int)$_POST[ 'limit' ] );
    
    $createFilter = function( $method_name, $field_name ) use( &$s ){
      $ids = $_POST[ 'conditions' ][ $method_name ];
      $flag = substr( $ids, 0, 1 );
      $ids = substr( $ids, 2 );
      
      if( $flag == 'i' ){
        $s->SetFilter( $field_name, explode( ",", $ids ) );
      }else{
        $s->SetFilter( $field_name, explode( ",", $ids ), TRUE );
      }   
    };
    
    if( $_POST[ 'conditions' ][ 'file_type' ] != '' ){ 
      $createFilter( 'file_type', 'filetype_id' );    
    }
    
    $s->setRankingMode( SPH_RANK_PROXIMITY_BM25 );    
    //$s->setRankingMode( SPH_RANK_BM25 );
    $results = $s->query( $searchstring, "taws_files_idx" );
    $s->close();
    
    $resultset = array();
    
    if( $results[ 'total' ] > 0 && !empty( $results[ 'matches' ] ) ){
      $ids = array_keys( $results[ 'matches' ] );
      $ids = implode( ',', $ids );
      $ids = "IN( $ids )"; 
      
      $sql_query = file_get_contents( "./sql/select_file_query.{$taws_server_config[ 'sql_program' ]}" );
      $sql_query = str_replace( '$ids', $ids, $sql_query );
      
      // Get the data that sphinx helped find
      $dbconn = new GenericSQL;
      $dbconn->connect();
      $pos = $_POST[ "offset" ] + 1;
      $result = $dbconn->query( $sql_query );
      while( $row = $result->fetchRow() ){
        if( $row[ 'last_modified' ] != 0 ){
          $row[ 'last_modified' ] = date( 'm/d/Y  h:i:s', $row[ 'last_modified' ] );
        }else{
          $row[ 'last_modified' ] = 'unknown';
        }
        
	$resultset[ $pos ] = $row;
	++$pos;
      }
      $dbconn->disconnect();
    }   
    
    if( empty( $resultset ) ){
      //sorry no results, making a entry so that the javascript side knows that.
      header( 'error: No results found. You look towards the void, it looks back at you.' );
    }
    return $resultset;    
  }
  
  /**
  * rss search.
  * @param search A string to search.
  */
  function runRssSearch( $searchstring ){
    global $taws_server_config;
    
    $s = new SphinxClient();
    $s->setServer( $taws_server_config[ 'sphinx_server' ], $taws_server_config[ 'sphinx_port' ] );
    $s->setMatchMode( SPH_MATCH_EXTENDED2 );
    $s->setMaxQueryTime( 300 ); 
    $s->SetLimits( (int)$_POST[ 'offset' ], (int)$_POST[ 'limit' ] );    
    $s->setRankingMode( SPH_RANK_PROXIMITY_BM25 );   
    $results = $s->query( $searchstring, "taws_rss_idx" );
    $s->close();
    
    $resultset = array();
    
    if( $results[ 'total' ] > 0 && !empty( $results[ 'matches' ] ) ){
      $ids = array_keys( $results[ 'matches' ] );
      $ids = implode( ',', $ids );
      $ids = "IN( $ids )"; 
      
      $sql_query = file_get_contents( "./sql/select_rss_query.{$taws_server_config[ 'sql_program' ]}" );
      $sql_query = str_replace( '$ids', $ids, $sql_query );
      
      // Get the data that sphinx helped find
      $dbconn = new GenericSQL;
      $dbconn->connect();
      
      $pos = $_POST[ "offset" ] + 1;;
      $result = $dbconn->query( $sql_query );
      while( $row = $result->fetchRow() ){	
	$resultset[ $pos ] = $row;	
	++$pos;
      }
      $dbconn->disconnect();
    }   
    
    if( empty( $resultset ) ){
      //sorry no results, making a entry so that the javascript side knows that.
      header( 'error: No results found. You look towards the void, it looks back at you.' );
    }
    return $resultset;    
  }
  
  /**
  * The is an attemp for a better searching technique.
  * This will not use UBM seperatly but rather forcing it in
  * sphinx
  * @param search A string to search.
  */
  function runSearch_v2( $searchstring ){
    global $taws_server_config;    
    
    if( $_POST[ 'conditions' ][ 'domain' ] != '' ){ 
      // Do a quick query to get the id of the domain.
      $dbconn = new GenericSQL;      
      if( !$dbconn->connect() ){        
        logger( "Couldn't start sql connection. " . __FILE__ . ': near ' . __LINE__, TAWS_LOG_CRITICAL, "CRITICAL" );
      }                                             
        
      $result = $dbconn->prepareAndBind( "SELECT id FROM Domains WHERE domain = ?;", array( $_POST[ 'conditions' ][ 'domain' ] ) );  
      if( !$result ){        
        logger( "Couldn't query database. " . __FILE__ . ': near ' . __LINE__, TAWS_LOG_CRITICAL, "CRITICAL" );
      } 
      
      
      $result = $result->fetchRow();
      if( $result != 0 ){        
        $domain_id = $result[ 'id' ];          
        // I just want the id, domain name isn't needed
        $_POST[ 'conditions' ][ 'domain' ] = $domain_id;         
      }else{
        // No domain the no results        
        header( 'error: Domain conditional search failed. Domain does not exist in the database.' );  
        logger( "domain '{$_POST[ 'conditions' ][ 'domain' ]}' does not exist in the database", TAWS_LOG_INFO, "INFO" );
        return array();
      }  
      $dbconn->disconnect();
    }
    
    $s = new SphinxClient();
    $s->setServer( $taws_server_config[ 'sphinx_server' ], $taws_server_config[ 'sphinx_port' ] );
        
    $s->setMatchMode( SPH_MATCH_EXTENDED2 );
    $s->setMaxQueryTime( 500 );     
    // Here! take advantage of UBM
    $s->SetFieldWeights( array( 'ubm_query' => 15, 'title' => 10, 'Data.description' => 9 ) );
    
        
    // something to reduce code repetition 
    $createFilter = function( $method_name, $field_name ) use( &$s ){
      $ids = $_POST[ 'conditions' ][ $method_name ];
      $flag = substr( $ids, 0, 1 );
      $ids = substr( $ids, 2 );
      
      if( $flag == 'i' ){
        $s->SetFilter( $field_name, explode( ",", $ids ) );
      }else{
        $s->SetFilter( $field_name, explode( ",", $ids ), TRUE );
      }   
    };
    
    if( $_POST[ 'conditions' ][ 'scheme' ] != '' ){ 
      $createFilter( 'scheme', 'scheme_id' );    
    }  
    if( $_POST[ 'conditions' ][ 'language' ] != '' ){ 
      $createFilter( 'language', 'language_id' );        
    }    
    if( $_POST[ 'conditions' ][ 'type' ] != '' ){ 
      $createFilter( 'type', 'type_id' );
    }
    if( $_POST[ 'conditions' ][ 'subject' ] != '' ){ 
      $createFilter( 'subject', 'subject_id' );
    }
    
    // Remember this varible is the id of the domain and and not the name.   
    if( $_POST[ 'conditions' ][ 'domain' ] != '' ){   
      $s->SetFilter( 'domain_id', [ $_POST[ 'conditions' ][ 'domain' ] ] );
    }    
    
    // min and max times
    $time_from = 1;
    $time_to = 1999999999;
    if( $_POST[ 'conditions' ][ 'time_to' ] != '' ){   
      $time_to = strtotime( $_POST[ 'conditions' ][ 'time_to' ] );
    }
    if( $_POST[ 'conditions' ][ 'time_from' ] != '' ){   
      $time_from = strtotime( $_POST[ 'conditions' ][ 'time_from' ] );
    }
    if( $_POST[ 'conditions' ][ 'time_to' ] != '' || $_POST[ 'conditions' ][ 'time_from' ] != '' ){   
      $s->SetFilterRange( 'published_time', $time_from, $time_to );
    }
    
    
    if( $_POST[ 'conditions' ][ 'group' ] != '' ){
      $group_order = "@relevance DESC";
      if( $_POST[ 'conditions' ][ 'order' ] != '' ){ 
        $group_order = $_POST[ 'conditions' ][ 'order' ];
      }
      $s->SetGroupby( $_POST[ 'conditions' ][ 'group' ], SPH_GROUPBY_ATTR, $group_order );       
      //$s->SetGroupby( $_POST[ 'conditions' ][ 'group' ], SPH_GROUPBY_ATTR, "@group DESC" ); 
    }
    
    if( $_POST[ 'conditions' ][ 'order' ] != '' ){      
      $s->SetSortMode( SPH_SORT_EXTENDED, $_POST[ 'conditions' ][ 'order' ] );       
    }else{
      $s->SetSortMode( SPH_SORT_EXTENDED, '@relevance DESC, frequency DESC' ); 
    }   
      
    $s->SetLimits( (int)$_POST[ 'offset' ], (int)$_POST[ 'limit' ] );  
    $s->setRankingMode( SPH_RANK_PROXIMITY_BM25 );    
    //$s->setRankingMode( SPH_RANK_BM25 ); 
    
    // Conditions set now lets query, from idexes
    $results = $s->query( $searchstring, "taws_idx" );
    
    $status = $s->getLastError();
    if( $status != '' ){
      logger( "Sphinx, $status. " . __FILE__ . ': near ' . __LINE__, TAWS_LOG_CRITICAL, "CRITICAL" );    
    }    
    $s->close();//Ok got what i want, end sphinx
    
    $resultset = array(); // The results to return later
    
    if( $results[ 'total' ] > 0 && !empty( $results[ 'matches' ] ) ){
      // Create the SQL connection    
      $dbconn = new GenericSQL;      
      if( !$dbconn->connect() ){        
        logger( "Couldn't start sql connection. " . __FILE__ . ': near ' . __LINE__, TAWS_LOG_CRITICAL, "CRITICAL" );
      }
    
      $ids = array_keys( $results[ 'matches' ] );
      $ids = implode( ',', $ids );
                  
      $sql_query = file_get_contents( "./sql/select_query2.{$taws_server_config[ 'sql_program' ]}" );
      $sql_query = str_replace( '$ids', $ids, $sql_query );
      
      // Get the data that sphinx helped find
      $result = $dbconn->query( $sql_query );
      if( !$result ){        
        logger( "Couldn't query database. " . __FILE__ . ': near ' . __LINE__, TAWS_LOG_CRITICAL, "CRITICAL" );
      } 
      
      $pos = $_POST[ "offset" ] + 1;
      while( $row = $result->fetchRow() ){	
	$resultset[ $pos ] = $row;
	++$pos;
      }  
      
      // Close DB
      $dbconn->disconnect();
    }
    
    /*
    // If there is no results and there is only one word, it's probably a stop word
    if( empty( $resultset ) && count( explode( " ", $searchstring ) ) ){
      // A stop word may have been encountered, no choice but to use UBM but for only one time   
      if( $_POST[ "offset" ] == 0 ){
        $dbconn = new GenericSQL;
        $dbconn->connect();
        
        $conditions = '';
        $sql_query = file_get_contents( "./sql/select_query1.{$taws_server_config[ 'sql_program' ]}" );
        $sql_query = str_replace( '$conditions', $conditions, $sql_query );
    
        $binds = array( $searchstring, $_POST[ 'limit' ], $_POST[ 'offset' ] );  
        $result = $dbconn->prepareAndBind( $sql_query, $binds );  
               
	// Start inserting data from DB and giving a position number
	$pos = $_POST[ "offset" ] + 1;
	while( $row = $result->fetchRow() ){	  
	  $resultset[ $pos ] = $row;
	  ++$pos;      
	}       
        $dbconn->disconnect();
      }         
    }
    */
    // TODO if there is still no searches what else can i do ?
    
    
    if( empty( $resultset ) ){
      //sorry no results, making a entry so that the javascript side knows that.
      header( 'error: No results found. You look towards the void, it looks back at you.' );   
      logger( "User searched for '$searchstring', but got no results. " , TAWS_LOG_INFO, "INFO" );         
    }
    
    if( $taws_server_config[ 'enable_stcfbs' ] ){
      // NOTE This is developer tools tools, not meant for regular users
      // OK fix future attemps by using google.
      // pass the url and query to taws crawler so that it can have the results
      
      $encoded_query = urlencode( $searchstring );
      $google_url = "https://www.google.com/search?q=$encoded_query";
      
      // Enter the google url in the url to crawl database
      $db = new SQLite3( "/var/www/genaside.net/www/taws/uploads/urls.sqlite", SQLITE3_OPEN_READWRITE );
      $stmt = $db->prepare( 'INSERT OR IGNORE INTO urls VALUES( ? );' );
      $stmt->bindParam( 1, $google_url, SQLITE3_TEXT );
      $stmt->execute();
      $stmt->close();
      $db->close();   
    }
    
    return $resultset;  
  }
  
  
  
  
  
  
  
  
    
  /**
  * By the power of mysql, I have the POWEEERRR.
  * TODO remove this
  * @param search A string to search.
  */
  function runSearch( $searchstring ){
    global $taws_server_config;
    
    // Create the SQL connection    
    $dbconn = new GenericSQL;
    $dbconn->connect();
    
    /**
    * Start out by building conditions for the sql based 
    * on the post/get values.
    */    
    $conditions = array();    
    
    if( $_POST[ 'conditions' ][ 'domain' ] != '' ){ 
      // Do a quick query to get the id of the domain.      
      $result = $dbconn->query( "SELECT id FROM Domains 
                                  WHERE domain = '{$_POST[ 'conditions' ][ 'domain' ]}';" );      
      
      $result = $result->fetchRow();
      if( $result != 0 ){        
        $domain_id = $result[ 'id' ];  
        $_POST[ 'conditions' ][ 'domain' ] = $domain_id; // I just want the id
        array_push( $conditions, "domain_id = $domain_id" );        
      }else{
        // No domain the no results        
        header( 'error: Domain conditional search failed. Domain does not exist in the database.' );        
        return array();
      }            
    }
    
    // A anon functions to reduce code repetition 
    $createFilter = function( $method_name, $field_name ) use( &$conditions ){
      $ids = $_POST[ 'conditions' ][ $method_name ];
      $flag = substr( $ids, 0, 1 );
      $ids = substr( $ids, 2 );
      
      if( $flag == 'i' ){
        array_push( $conditions, "$field_name IN( $ids )" );
      }else{
        array_push( $conditions, "$field_name NOT IN( $ids )" );
      }   
    };
    //
    if( $_POST[ 'conditions' ][ 'scheme' ] != '' ){ 
      $createFilter( 'scheme', 'scheme_id' );    
    }   
    if( $_POST[ 'conditions' ][ 'language' ] != '' ){ 
      $createFilter( 'language', 'language_id' );       
    }
    if( $_POST[ 'conditions' ][ 'type' ] != '' ){ 
      $createFilter( 'type', 'type_id' );      
    }    
    if( $_POST[ 'conditions' ][ 'subject' ] != '' ){ 
      $createFilter( 'subject', 'subject_id' );       
    }    
    
    if( $_POST[ 'conditions' ][ 'time_from' ] != '' ){   
      $time_from = strtotime( $_POST[ 'conditions' ][ 'time_from' ] );
      array_push( $conditions, "published_time > $time_from" ); 
    }
    if( $_POST[ 'conditions' ][ 'time_to' ] != '' ){   
      $time_to = strtotime( $_POST[ 'conditions' ][ 'time_to' ] );
      array_push( $conditions, "published_time < $time_to" );     
    }   
    
    // Combine all conditions into one string to be use by the sql.
    $conditions = implode( ' AND ', $conditions );
    if( $conditions != '' ){
      $conditions = ' AND '. $conditions;
    }
    
    // add other conditions like order by and group by
    if( $_POST[ 'conditions' ][ 'group' ] != '' ){
      $conditions .= " GROUP BY Data.{$_POST[ 'conditions' ][ 'group' ]} ";
    }    
    $conditions .= " ORDER BY rank DESC ";
    if( $_POST[ 'conditions' ][ 'order' ] != '' ){
      $conditions .= " ,Data.{$_POST[ 'conditions' ][ 'order' ]}";
    }
        
    // Done condition building
    
    // Search Part 1 , using UBM( a mapping of direct query to result )  
    $ubmSearch = function() use( &$taws_server_config, &$dbconn, &$searchstring, &$conditions ){          
    };
    
    $sql_query = file_get_contents( "./sql/select_query1.{$taws_server_config[ 'sql_program' ]}" );
    $sql_query = str_replace( '$conditions', $conditions, $sql_query );
    
    $binds = array( $searchstring, $_POST[ 'limit' ], $_POST[ 'offset' ] );  
    
    // For a true order condition to work ubm must be stoped in two places.
    // Here and ubm past calculations
    if( $_POST[ 'conditions' ][ 'order' ] != '' ){
      $binds = array( $searchstring, 0, 0 );      
    }   
    
    $result = $dbconn->prepareAndBind( $sql_query, $binds );  
    
    
    
        
    
    $id_exclude_list = array(); // a list to exclude from the second search    
    $resultset = array(); // The results to return later
    
    // Start inserting data from DB and giving a position number
    $pos = $_POST[ "offset" ] + 1;
    while( $row = $result->fetchRow() ){      
      array_push( $id_exclude_list, $row[ 'id' ] );      
      $resultset[ $pos ] = $row;
      ++$pos;      
    }
    
    // Get the number of rows returned by UBM
    $count = $result->getNumberOfRows(); 
    
    
    // Cheaking If we optain the limit or not
    $testc = $_POST[ 'limit' ] - $count;
    if( $testc == 0 ){
      // Well, no need to get any more results right now, ubm covered it.
      return;
    }else if( $count == 0){
      // UBM no longer have extra data, so i must go back to get the ids for the exclude list.
      $offset_temp = 0;
      $limit_temp = 9999; // I would be surprised if it goes over 9999      
      $binds = array( $searchstring, $limit_temp, $offset_temp );
      
      // Part 2 of ubm disable
      if( $_POST[ 'conditions' ][ 'order' ] != '' ){
        $binds = array( $searchstring, 0, 0 );      
      }
      
      $result = $dbconn->prepareAndBind( $sql_query, $binds );       
      
      // Get old ids
      $num_of_rows = $result->getNumberOfRows();
      while( $row = $result->fetchRow() ){         
	array_push( $id_exclude_list, $row[ 'data_id' ] ); 
	$_POST[ 'offset' ] = $_POST[ 'offset' ] - $num_of_rows;
      }
    }else if( $testc > 0 ){
      // Them limit is partially filled by UBM, lets adjust the limit/offset for the other search.
      $_POST[ 'limit' ] = $_POST[ 'limit' ] - $count;
      $_POST[ 'offset' ] = 0;
    }    
         
    // Now Part 2 of the search, sphinx    
    $results = runSphinx( $searchstring, $id_exclude_list );
    
    // Sphinx retived all ids, let use mysql to get more data
    if( $results[ 'total' ] > 0 && !empty( $results[ 'matches' ] ) ){
      $ids = array_keys( $results[ 'matches' ] );
      $ids = implode( ',', $ids );
                  
      $sql_query = file_get_contents( "./sql/select_query2.{$taws_server_config[ 'sql_program' ]}" );
      $sql_query = str_replace( '$ids', $ids, $sql_query );
      
      // Get the data that sphinx helped find
      $result = $dbconn->query( $sql_query );
      while( $row = $result->fetchRow() ){
	//array_push( $resultset, $row ); 
	$resultset[ $pos ] = $row;
	++$pos;
      }  
    }
    // Clean up    
    $dbconn->disconnect();
    
    if( empty( $resultset ) ){
      //sorry no results, making a entry so that the javascript side knows that.
      header( 'error: No results found. You look towards the void, it looks back at you.' );  
      //$resultset[ "resultless" ] = true;
    }
    return $resultset;
  } 
   
   
  /**
  * TODO remove this
  * By the power of Sphinx I have the power
  * This is the second part for the search.
  * All Sql will use this to complete it's search
  * @param $searchstring Pass along the search string from search 1
  * @param id_exclude_list A list of ids to exclude.
  * @return list of ids that will be used by the sql
  */
  function runSphinx( $searchstring, $id_exclude_list ){
    global $taws_server_config;
  
    $s = new SphinxClient();
    $s->setServer( $taws_server_config[ 'sphinx_server' ], $taws_server_config[ 'sphinx_port' ] );
    $s->setMatchMode( SPH_MATCH_EXTENDED2 );
    $s->SetFieldWeights( array( 'title' => 10, 'Data.description' => 9 ) );
    $s->setMaxQueryTime( 500 );     
    if( !empty( $id_exclude_list ) ){      
      $s->SetFilter( '@id', $id_exclude_list, true );
    }
    
    // something to reduce code repetition 
    $createFilter = function( $method_name, $field_name ) use( &$s ){
      $ids = $_POST[ 'conditions' ][ $method_name ];
      $flag = substr( $ids, 0, 1 );
      $ids = substr( $ids, 2 );
      
      if( $flag == 'i' ){
        $s->SetFilter( $field_name, explode( ",", $ids ) );
      }else{
        $s->SetFilter( $field_name, explode( ",", $ids ), TRUE );
      }   
    };
    
    if( $_POST[ 'conditions' ][ 'scheme' ] != '' ){ 
      $createFilter( 'scheme', 'scheme_id' );    
    }  
    if( $_POST[ 'conditions' ][ 'language' ] != '' ){ 
      $createFilter( 'language', 'language_id' );        
    }    
    if( $_POST[ 'conditions' ][ 'type' ] != '' ){ 
      $createFilter( 'type', 'type_id' );
    }
    if( $_POST[ 'conditions' ][ 'subject' ] != '' ){ 
      $createFilter( 'subject', 'subject_id' );
    }
    
    // Remember this varible is the id of the domain and not the name.   
    if( $_POST[ 'conditions' ][ 'domain' ] != '' ){   
      $s->SetFilter( 'domain_id', [ $_POST[ 'conditions' ][ 'domain' ] ] );
    }    
    
    // min and max times
    $time_from = 1;
    $time_to = 1999999999;
    if( $_POST[ 'conditions' ][ 'time_to' ] != '' ){   
      $time_to = strtotime( $_POST[ 'conditions' ][ 'time_to' ] );
    }
    if( $_POST[ 'conditions' ][ 'time_from' ] != '' ){   
      $time_from = strtotime( $_POST[ 'conditions' ][ 'time_from' ] );
    }
    if( $_POST[ 'conditions' ][ 'time_to' ] != '' || $_POST[ 'conditions' ][ 'time_from' ] != '' ){   
      $s->SetFilterRange( 'published_time', $time_from, $time_to );
    }
    
    if( $_POST[ 'conditions' ][ 'group' ] != '' ){
      $group_order = "@relevance DESC";
      if( $_POST[ 'conditions' ][ 'order' ] != '' ){ 
        $group_order = $_POST[ 'conditions' ][ 'order' ];
      }
      //$s->SetGroupby( $_POST[ 'conditions' ][ 'group' ], SPH_GROUPBY_ATTR, $group_order ); 
      //$s->SetGroupby( $_POST[ 'conditions' ][ 'group' ], SPH_GROUPBY_ATTR, "published_time DESC" ); 
      //$s->SetGroupby( $_POST[ 'conditions' ][ 'group' ], SPH_GROUPBY_ATTR, "@relevance DESC" ); 
      $s->SetGroupby( $_POST[ 'conditions' ][ 'group' ], SPH_GROUPBY_ATTR, "@group DESC" ); 
    }
    
    if( $_POST[ 'conditions' ][ 'order' ] != '' ){      
      $s->SetSortMode( SPH_SORT_EXTENDED, $_POST[ 'conditions' ][ 'order' ] );       
    }   
      
    $s->SetLimits( (int)$_POST[ 'offset' ], (int)$_POST[ 'limit' ] );  
    $s->setRankingMode( SPH_RANK_PROXIMITY_BM25 );    
    //$s->setRankingMode( SPH_RANK_BM25 ); 
    
    // Conditions set now lets query, from idexes
    $results = $s->query( $searchstring, "taws_idx" );
    $s->close();//Ok got what i want, end sphinx
    return $results;
  }
  
  /**
  * Check the spelling on the sentence and try to fix it.
  * @return string - The fixed version of the original string. 
  * TODO I think there are better ways to do 'did you mean'. This is slow  
  */
  function spellCheck( $sentence ){
    $keywords = preg_split( "/\s+/", $sentence );
    $arr_size = count( $keywords );
    
    //$pspell_config = pspell_config_create("en");
    //pspell_config_mode( $pspell_config, PSPELL_FAST );
    //$pspell_link = pspell_new_config( $pspell_config );
    $pspell_link = pspell_new_personal( "./custom.pws", "en", "", "", "", PSPELL_FAST );
    
    for( $i = 0; $i < $arr_size; ++$i ){
      if( !pspell_check( $pspell_link, $keywords[ $i ] ) ){     
        $candidates = pspell_suggest( $pspell_link, $keywords[ $i ] );
        $selected = '';
        //sometimes it returns 2 words, i dont want that. find next available word
        foreach( $candidates as $value ){
          if( !preg_match( '/\s|-/',$value ) ){
            $selected = $value;
            break;
          }
        }
        //if (strpos($str, '.') !== FALSE)
        $keywords[ $i ] = strtolower( $selected );
      }
    }    
    return implode( " ", $keywords );    
  }
    
    
  /**
  * TODO umm
  * Stores the user's search query and the selected result in a sqlite
  * file. Only do this when a curtian option is enabled
  */
  function store_ubm(){
    global $taws_server_config;
    if( !$taws_server_config[ 'enable_dc_ubm' ] ){
      return;
    }
    // I think sqlite is best for this
    $path = './serverdata/ubm.sqlite';
    
    $db = new SQLite3( $path, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE );
    if( !$db ){
      // Send to error log then exit.
      exit();
    }
    
    $db->exec( 'CREATE TABLE IF NOT EXISTS pairs( query TEXT, data_id INTEGER, UNIQUE( query, data_id ) );' );      
    $stmt = $db->prepare( 'INSERT OR IGNORE INTO pairs VALUES( ?, ? );' );
    $stmt->bindParam( 1, $_POST[ 'query' ], SQLITE3_TEXT );
    $stmt->bindParam( 2, $_POST[ 'result_id' ], SQLITE3_INTEGER );
    $stmt->execute();
    
    $stmt->close();
    $db->close();
    
    unset( $path, $db, $stmt );  
  }
  
  
  /**
  * Here is an auto complete function.
  * NOTE i thought this will be better to auto complete phrases in umb then just using a list of words.
  * TODO I should make it do both phrase and word completion some how
  * @param string The string to complete.
  * @return Returns an array of suggestions.  
  */
  function completePhrase( $string, $reslimit = 10 ){  
    
    $phrase = strtolower( $string );
    $phrase_end = $phrase . 'z';    
    
    $sql_query = "SELECT query FROM uq
                  LEFT OUTER JOIN ubm ON query_id = id
                  WHERE query >= ? AND query <= ?
                  GROUP BY query
                  ORDER BY rank DESC
                  LIMIT ?";
    
    $db = new GenericSQL;
    $db->connect(); 
    
    $binds = array( $phrase, $phrase_end, $reslimit );       
    $results = $db->prepareAndBind( $sql_query, $binds );
    
    $resultset = array();
    while( $row = $results->fetchRow() ){	
      $string = $row[ 'query' ];
      array_push( $resultset, $string );
    }
    // NOTE this might hang for quite a while, maybe.
    
    // If no results for phrase then try again useing word completeion
    if( empty( $resultset ) ){
      $wordlist = explode( ' ', $phrase );
      
      // Only if there more then one word or this wont make sense
      if( count( $wordlist ) > 1 ){
        $word = end( $wordlist );
        $word_end = $word . 'z';
        
        $binds = array( $word, $word_end, $reslimit );
        $results = $db->prepareAndBind( $sql_query, $binds );    
    
	while( $row = $results->fetchRow() ){	
	  $wordlist[ key( $wordlist ) ] = $row[ 'query' ];
	  array_push( $resultset, implode( ' ', $wordlist ) );
	}
      
      }     
    }
    
    $db->disconnect();
    
    return $resultset;    
  }
  
  /**
  * A function to handle logs. All messages are pass in here
  * so that it can be properly handle.
  * NOTE people spoke of klogger, iam using php's error_log for now 
  * NOTE Options must be on to work.
  * @param message The messege that needs to be logged
  */
  /*function logger( $message ){    
    $datatime = date( 'Y-m-d H:i:s' );
    error_log( "$datatime: $message \n", 3, "./logs/server.log" );
  }*/
  
  function adminAuthenticate( $plain_pass ){
    global $taws_server_config;
    
    // lets stop people from spamming
    //$max_time_in_seconds = 30;
    //$max_attempts = 3;
    // might need to use mysql
    
    session_start();
    if( $plain_pass == $taws_server_config[ 'user_password' ] ){
      return $_SESSION[ 'authenticated' ] = true;      
    }else{
      return false;
    }
    session_write_close();
  }
  
  function adminLogout(){
    session_start();
    $_SESSION[ 'authenticated' ] = false;
    session_write_close();
  }
  
  function update_DB(){
    session_start();
    session_write_close();
    if( $_SESSION[ 'authenticated' ] ){      
      exec( "php ./middleman.php -u > ./serverdata/server_update.fifo &" );      
    }else{
      //return erro
    }    
  }
  
  
  
  // Handle all procedures 
  if( isset( $_POST[ "procedure" ] ) ){
    
    /****************************
    * Here is the start to life *
    *****************************
    */
    switch( $_POST[ "procedure" ] ){
      case 'store_user_query_and_result':
	store_ubm();
      break;
      case 'search_and_get_result':   
        if( $_POST[ 'conditions' ][ 'operation' ] == 0 ){ 
          // Defualt search
	  //$res = runSearch( $_POST["ss"] );
	  $res = runSearch_v2( $_POST["ss"] );
	  
        }else if( $_POST[ 'conditions' ][ 'operation' ] == 1 ){
	  // RSS search
	  $res = runRssSearch( $_POST["ss"] );
        }else if( $_POST[ 'conditions' ][ 'operation' ] == 2 ){
	  // File search
	  $res = runFileSearch( $_POST["ss"] );
        }else{
	  // not supported
        }
	
	
	/* It is just too slow
	$dym = spellCheck( $_POST["ss"] );
	if( strcmp( $dym, $_POST["ss"] ) != 0 ){
	  $res[ 'dym' ] = spellCheck( $_POST["ss"] );
	}
	*/      
	echo json_encode( $res );
      break; 
      case 'complete_phrase':
	echo json_encode( completePhrase( $_POST[ 'term' ] ) );
      break;
      case 'authenticate_admin':
        $val = adminAuthenticate( $_POST[ 'plain_pass' ] );        
	echo json_encode( $val );
      break;
      case 'admin_logout':
        adminLogout();	
      case 'update':
        update_DB();	
      break;
    }  
  }    
  
  
?>