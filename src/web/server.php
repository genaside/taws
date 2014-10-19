<?php
  require './includes/php/sphinxapi.php';
  require './config/server_config.php';  
  
  //error_reporting( 0 ); // Iam going to use a logger
  date_default_timezone_set( 'UTC' ); 
  set_time_limit( 5 );
  
  /**
  * With this i can send a messege to jquery's success opertion that
  * something went wrong.
  * @param message A string that tells what type of error is this.
  */
  function errorMessegeExit( $message ){
    header( 'error: 1' );
    header( 'message:' + $message );
    exit();
  }
  
  /*
  function runSqlite( $searchstring ){
    $sel1 = 'Data';
    $query_status = json_decode( $_POST[ 'query_status' ], true );
    if( $query_status[ 'is_single_word' ] ){
      $sel1 = 'title';
    }
    
    //since sqlitefts doesnt have stop words lets us a file to nock them out
  
    $sql_query = <<<EOT
      SELECT title, domain || '/' || COALESCE( page, '' ) AS page, domain,
      Data.description AS page_description, COALESCE( Domains.description, '' ) AS dd, COALESCE( Domains.name, '' ) AS dn,
      http_https, max(frequency) FROM Data
      INNER JOIN Domains ON Data.domain_id = Domains.id
      WHERE $sel1 MATCH ? 
      GROUP BY domain_id ORDER BY frequency DESC, timestamp DESC LIMIT ? OFFSET ?;
EOT;

    $sql_adv_query = <<<EOT
      SELECT title, domain || '/' || COALESCE( page, '' ) AS page, domain,
      Data.description AS page_description, COALESCE( Domains.description, '' ) AS dd, COALESCE( Domains.name, '' ) AS dn,
      http_https, max(frequency) FROM Data
      INNER JOIN (
        Domains LEFT OUTER JOIN Types ON Domains.type_id = Types.id
        LEFT OUTER JOIN Subjects ON Domains.subject_id = Subjects.id
      )ON Data.domain_id = Domains.id
      INNER JOIN Languages ON Data.language_id = Languages.id
      WHERE Data MATCH ?
      ORDER BY frequency DESC, timestamp DESC LIMIT ? OFFSET ?;
EOT;
    
    //Ok everything checks out. lets get some results
    $path = $taws_sever_config[ 'sqlite_file_path' ] = './serverdata/masterv.sqlite';
    $db = new SQLite3( $path, SQLITE3_OPEN_READONLY );
    
    //$db->loadExtension( './spellfix1.so' );
    
    //$result = $db->query( "SELECT * FROM Domains limit 10;" );
    $st = $db->prepare( $sql_query );      
    $st->bindParam( 1, $searchstring, SQLITE3_TEXT );
    $st->bindParam( 2, $_POST["limit"], SQLITE3_INTEGER );
    $st->bindParam( 3, $_POST["offset"], SQLITE3_INTEGER );
    $result = $st->execute(); 
    
    
    
    $resultset = array();
    $pos = $_POST["offset"] + 1;
    while( $rowc = $result->fetchArray() ){    
      //array_push( $resultset, $rowc ); 
      $resultset[ $pos ] = $rowc;
      ++$pos;
    }
    
    if( empty( $resultset ) ){
      //sorry no results, making a entry so javascript side knows that.
      $resultset[ "resultless" ] = true;
    }
    
    $st->close(); 
    $db->close();    
    return $resultset;
  }  
  */
  
  /**
  * By the power of mysql, I have the POWEEERRR.
  */
  function runSearch( $searchstring ){
    global $taws_server_config;
    
    // Create the SQL connection    
    $mysqli = connectDatabase();
       
    
    // Going to do some condition building based on the post/get values.    
    $conditions = array();  
    
    // NOTE no need to check with isset, these post value are alwasys set    
    if( $_POST[ 'conditions' ][ 'd' ] != '' ){ 
      // Do a quick query to get the id of the domain.      
      $result = queryDatabase( $mysqli, "SELECT id FROM Domains 
                                         WHERE domain = '{$_POST[ 'conditions' ][ 'd' ]}';" );      
      
      $result = fetchRow( $mysqli, $result );
      if( $result != 0 ){
        //$result = fetchRow( $mysqli, $result );
        $domain_id = $result[ 'id' ];  
        $_POST[ 'conditions' ][ 'd' ] = $domain_id;
        array_push( $conditions, "domain_id = $domain_id" );        
      }else{
        return array( "resultless" => true );
      }
            
    }
    if( $_POST[ 'conditions' ][ 'l' ] != '' ){ 
      $language_id =  $_POST[ 'conditions' ][ 'l' ];
      array_push( $conditions, "language_id IN( $language_id )" );      
    }    
    if( $_POST[ 'conditions' ][ 't' ] != '' ){ 
      $type_id =  $_POST[ 'conditions' ][ 't' ];
      array_push( $conditions, "type_id IN( $type_id )" );      
    }  
    if( $_POST[ 'conditions' ][ 'scheme' ] != -1 ){ 
      $scheme_id = $_POST[ 'conditions' ][ 'scheme' ];      
      array_push( $conditions, "scheme_id IN( $scheme_id )" );      
    }
    
    // Combine all conditions into one string to be use by the sql.
    $conditions = implode( ' AND ', $conditions );
    if( $conditions != '' ){
      $conditions = ' AND '. $conditions;
    }
    
    // Search Part 1 , using UBM( a mapping of direct query to result )    
    $sql_query = file_get_contents( "./sql/select_query1.{$taws_server_config[ 'sql_program' ]}" );
    $sql_query = str_replace( '$conditions', $conditions, $sql_query );
    
    $test = array( $searchstring, $_POST[ 'limit' ], $_POST[ 'offset' ] );     
    $result = bindQueryDatabase( $mysqli, $sql_query, $test );    
    
    $id_exclude_list = array(); // a list to exclude from the second search    
    $resultset = array(); // The results to return later
    
    // Start inserting data from DB and giving a placement 
    $pos = $_POST[ "offset" ] + 1;
    while( $row = fetchRow( $mysqli, $result ) ){      
      array_push( $id_exclude_list, $row[ 'id' ] );      
      $resultset[ $pos ] = $row;
      ++$pos;      
    }
    $count = numRows( $result ); // Number of rows returned by UBM
    
    
    // Cheaking If we optain the limit or not
    $testc = $_POST[ 'limit' ] - $count;
    if( $testc == 0 ){
      // Well, no need to get any more results right now, ubm covered it.
      return;
    }else if( $count == 0){
      // UBM no longer have extra data, so i must go back to get the ids for the exclude list.
      $temp1 = 0;
      $temp2 = 9999; // I would be surprised if it goes over 9999
      
      $test = array( $searchstring, $temp2, $temp1 );
      $result = bindQueryDatabase( $mysqli, $sql_query, $test ); 
      
      // Get old ids
      $num_of_rows = numRows( $result );
      while( $row = fetchRow( $mysqli, $result ) ){         
	array_push( $id_exclude_list, $row[ 'data_id' ] ); 
	$_POST[ 'offset' ] = $_POST[ 'offset' ] - $num_of_rows;
      }
    }else if( $testc > 0 ){
      // Only some of the limit is fulfilled by UBM, lets adjust the limit/offset for the other search.
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
      $result = queryDatabase( $mysqli, $sql_query );
      while( $row = fetchRow( $mysqli, $result ) ){
	//array_push( $resultset, $row ); 
	$resultset[ $pos ] = $row;
	++$pos;
      }  
    }
    // Clean up    
    disconnectDatabase( $mysqli );
    
    if( empty( $resultset ) ){
      //sorry no results, making a entry so that the javascript side knows that.
      $resultset[ "resultless" ] = true;
    }
    return $resultset;
  }
  
  // TODO move the sql wrappers to a class on a different file
  
  /**
  * Function to make a mysql,sqlite, or postgresql
  * @return A object to the sql, else return null if fail
  */
  function connectDatabase(){
    global $taws_server_config;    
    
    $dbconn = null;
    if( $taws_server_config[ "sql_program" ] == 'mysql' ){
    
      $dbconn = new mysqli( $taws_server_config[ 'mysql_server' ], 
                            $taws_server_config[ 'mysql_db_user' ], 
                            $taws_server_config[ 'mysql_db_pass' ], 
                            $taws_server_config[ 'mysql_db_name' ],
                            $taws_server_config[ 'mysql_db_port' ]);
      if( $dbconn->connect_error ){
        logger( 'Errorwhen connecting db: ' . $dbconn->connect_error );
        exit();
      }      
    }else if( $taws_server_config[ "sql_program" ] == 'postgresql' ){
    
      $dbconn = pg_connect( "host={$taws_server_config[ 'postgresql_server' ]}                       
                             user={$taws_server_config[ 'postgresql_db_user' ]} 
                             password={$taws_server_config[ 'postgresql_db_pass' ]}
                             dbname={$taws_server_config[ 'postgresql_db_name' ]} 
                             port={$taws_server_config[ 'postgresql_db_port' ]}" );
                             
    }else if( $taws_server_config[ "sql_program" ] == 'sqlite' ){
    
      $dbconn = new SQLite3( $taws_server_config[ 'sqlite_db_path' ], SQLITE3_OPEN_READONLY ); 
      
    }else{    
      // Not supported
    }  
    
    // return whatever connection
    return $dbconn;  
  } 
  
  
  /**
  * This will try to take a query and run it under the correct sql.
  * Also try the alter the query for specific sql***
  * @param dbconn An open connection of the DB
  * @param sql_query Query to perform
  * @return results of return from query
  * @warning Only use general sql queries, somethings are not capitable with others
  */
  function queryDatabase( $dbconn, $sql_query ){ 
    global $taws_server_config;
    
    $results = null;
    if( $taws_server_config[ "sql_program" ] == 'mysql' ){    
      $results = $dbconn->query( $sql_query );      
    }else if( $taws_server_config[ "sql_program" ] == 'postgresql' ){    
      $results = pg_query( $dbconn, $sql_query );      
    }else if( $taws_server_config[ "sql_program" ] == 'sqlite' ){    
      $results = $dbconn->query( $sql_query );
    }
    return $results; 
  }
  
  /**
  * Same thing like queryDatabase, but using bindings  
  * @param dbconn An open connection of the DB
  * @param sql_query Query to perform
  * @param $binds Array of values
  * @return results of return from query
  */
  function bindQueryDatabase( $dbconn, $sql_query, $binds ){ 
    global $taws_server_config;
    
    $results = null;
    if( $taws_server_config[ "sql_program" ] == 'mysql' ){
      $stmt = $dbconn->prepare( $sql_query ); 
      
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
            
      call_user_func_array( array( &$stmt, 'bind_param' ), refValues( $binds ) );      
      $stmt->execute();    
      $results = $stmt->get_result();      
    }else if( $taws_server_config[ "sql_program" ] == 'postgresql' ){   
      pg_prepare( $dbconn, "stmt", '$sql_query' );
      $results = pg_execute($dbconn, "stmt", $binds );          
    }else if( $taws_server_config[ "sql_program" ] == 'sqlite' ){
      $stmt = $dbconn->prepare( $sql_query );  
      
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
    return $results; 
  }
  
    
  /**
  * Fetch row from results, associative ONLY  
  * @param dbconn An open connection of the DB 
  * @param results From query executaion
  * @return A row from results
  */
  function fetchRow( $dbconn, $results ){ 
    global $taws_server_config;
    
    $row = null;
    if( $taws_server_config[ "sql_program" ] == 'mysql' ){
      $row = $results->fetch_assoc();      
    }else if( $taws_server_config[ "sql_program" ] == 'postgresql' ){
      $row = pg_fetch_assoc( $result );
    }else if( $taws_server_config[ "sql_program" ] == 'sqlite' ){      
      $row = $results->fetchArray( SQLITE3_ASSOC );
    }else{
      // Not supported
    }  
    return $row;
  }
  
  
  /**  
  * Count the number of rows in a result
  * @param results the results returned after query
  * @return The number of rows
  */
  function numRows( $results ){
    global $taws_server_config;
    
    $num = 0;
    if( $taws_server_config[ "sql_program" ] == 'mysql' ){
      $num = $results->num_rows;
    }else if( $taws_server_config[ "sql_program" ] == 'postgresql' ){
      $num = pg_num_rows( $results );
    }else if( $taws_server_config[ "sql_program" ] == 'sqlite' ){
      // I have to count it myself, since php's sqlite3 doesn't have it
      $results->reset();
      while( $results->fetchArray() ){
        $num++;
      }        
    }else{
      // Not supported
    } 
    return $num;  
  }
  
  
  /**
  * Close a database
  * Also try the alter the query for specific sql***
  * @param dbconn An open connection of the DB  
  */
  function disconnectDatabase( $dbconn ){ 
    global $taws_server_config;
    
    if( $taws_server_config[ "sql_program" ] == 'mysql' ){
      $dbconn->close();
    }else if( $taws_server_config[ "sql_program" ] == 'postgresql' ){
      pg_close( $dbconn );
    }else if( $taws_server_config[ "sql_program" ] == 'sqlite' ){
      $dbconn->close();
    }else{
      // Not supported
    }  
  }
  
  
  /*
  * Workaround for binds wanting variables and not values
  */
  function refValues( $arr ){
    if( strnatcmp( phpversion(), '5.3' ) >= 0){
        $refs = array();
        foreach($arr as $key => $value)
            $refs[$key] = &$arr[$key];
        return $refs;
    }
    return $arr;
  }
  
   
   
  /**
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
    $s->setMaxQueryTime( 500 );     
    if( !empty( $id_exclude_list ) ){      
      $s->SetFilter( '@id', $id_exclude_list, true );
    }
    
    // I dont really need a hundred item from a domain unless it's the only thing being searched
    if( $_POST[ 'conditions' ][ 'd' ] !== '' ){      
      $s->SetFilter( 'domain_id', [ $_POST[ 'conditions' ][ 'd' ] ] );
    }else{
      $s->SetGroupby( 'domain_id', SPH_GROUPBY_ATTR, 'frequency DESC' );
    }   
    
    if( $_POST[ 'conditions' ][ 'scheme' ] != -1 ){       
      $s->SetFilter( 'scheme_id', [ $_POST[ 'conditions' ][ 'scheme' ] ] );
    }
    
    if( $_POST[ 'conditions' ][ 't' ] != '' ){       
      $s->SetFilter( 'type_id', explode( ",", $_POST[ 'conditions' ][ 't' ] ) );
    }
    if( $_POST[ 'conditions' ][ 'l' ] != '' ){       
      $s->SetFilter( 'language_id', explode( ",", $_POST[ 'conditions' ][ 'l' ] ) );
    }
    
    $s->SetSortMode( SPH_SORT_ATTR_DESC, 'frequency' );  
    $s->SetLimits( (int)$_POST[ 'offset' ], (int)$_POST[ 'limit' ] );  
    
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
  * @param string The string to complete.
  * @return Returns an array of suggestions.  
  */
  function completePhrase( $string ){    
    $a = strtolower( $string );
    $b = $a.'z';
    $resultset = array();
    $sql_query = "SELECT query FROM ubm WHERE query >= '$a' AND query <= '$b' GROUP BY query ORDER BY rank DESC LIMIT 10";
    
    $dbconn = connectDatabase();
    $results = queryDatabase( $dbconn, $sql_query );
    while( $row = fetchRow( $dbconn, $results ) ){	
      $string = $row[ 'query' ];
      array_push( $resultset, $string );
    }
    $dbconn = disconnectDatabase( $dbconn );
    return $resultset;    
  }
  
  /**
  * A function to handle logs. All messages are pass in here
  * so that it can be properly handle.
  * NOTE people spoke of klogger, iam using php's error_log for now 
  * NOTE Options must be on to work.
  * @param message The messege that needs to be logged
  */
  function logger( $message ){    
    $datatime = date( 'Y-m-d H:i:s' );
    error_log( "$datatime: $message \n", 3, "./logs/server.log" );
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
	$res = runSearch( $_POST["ss"] );
	
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
    }  
  }  
  
  
  //Start optimazing the query. Remove, Replace, Add
  if( isset( $_POST[ 'optimize' ] ) ){  
  return;
    $optimizedquery = '';
    if( $taws_sever_config[ "enable_synonym_optimization" ] ){
      //ok lets do this
      $words = explode( " ", $_POST[ 'optimize' ] );
      $num_of_words = count( $words );
      
      $db = new SQLite3( "./opti.sqlite", SQLITE3_OPEN_READONLY );
      for( $i = 0; $i < $num_of_words; $i++ ){
	$val = $words[ $i ];
	$res = $db->querySingle( "SELECT word_group FROM Synonyms WHERE word_group MATCH '$val';" );   
	if( $res != '' ){
	  $syn = explode( ",", $res );
	  unset( $syn[ array_search( $val, $syn ) ] );	  
	  $words[ $i ] = $words[ $i ] . " OR " . implode( " OR ", $syn );	  
	}
      }   
      $optimizedquery = implode( " ", $words );
      $db->close();
    }
    echo $optimizedquery;//send the query back  
  }
  
  
?>