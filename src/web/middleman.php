#!/usr/bin/php

<?php
  // This is a standalone
 
  include './config/server_config.php';  
  
  // Set some globals here
  
  $shortopts  = "";  
  $shortopts .= "h";
  $shortopts .= "c"; 
  $shortopts .= "u";
  $longopts  = array(    
    "help",
    "check",
    "update"
  );  
  $options = getopt( $shortopts, $longopts );
  
  foreach( $options as $opt=>$opt_value ){
    switch( $opt ){      
      case 'h':
      case 'help':
        echo "\n";
        echo "This a standalone script for doing varios things out side taws\n";
        echo "-h     --help     display options and help\n";
        echo "-c     --check    Check if database is up to date\n";
        //echo "       --start    Start Automated Updates\n";
        //echo "       --stop     Stop Automated Updates\n";
        echo "-u     --update   Update the database and restart sphinx\n";       
        //echo "-x                Update by removing data only\n";
        //echo "-z                Update by inserting data only\n";        
        echo "\n";
      break;
      case 'c':
      case 'check':
        echo "\n";
        $value = isUpdated();
        if( $value > 0  ){
          $units = array(
            'week' => 604800,
            'day' => 86400,
            'hour' => 3600//,
            //'minute' => 60,
            //'second' => 1
          );
          
          $strs = array();
          foreach( $units as $name=>$int ){
            if( $value < $int ){
              continue;
            }              
            $num = (int) ($value / $int);
            $value = $value % $int;
            $strs[] = "$num $name".(($num == 1) ? '' : 's');
          }

          echo "Your behind -> " . implode(', ', $strs);         
        }else if( $value == 0 ){
          echo "You're up to date, congrats go and party";
        }else{
          echo "It doesnt seem you've did the initial update yet";  
        } 
        echo "\n";
      break;    
      case 'u':
      case 'update':
        $value = isUpdated();
        if( $value == 0 ){
          echo "You're already up to date.\n";
        }else{
          echo "Going to update, this may take a while...\n";
          update();
        }        
        break;
    }
  }
  
    
  /**
  * Checks to see if the local database is in sync with the remote server 
  * @returns -1 if your just starting or an error came up, 0 if up to date, and n > 0 the number of days lost
  * NOTE there are two ways to do this, i think creating a timestamp is best.
  */
  function isUpdated(){
    global $taws_server_config;
    $time = -1;
    $filepath = "./serverdata/db_timestamp.txt";
    
    // check if the timestamp file exist, assuming if it doesnt , were stating a new
    if( !file_exists( $filepath ) ){
      return -1;
    }else{
      $time = unserialize( file_get_contents( $filepath ) );
      if( !is_numeric( $time ) ){
        return -1;
      }
    }
    if( $time < 1402980743 ){
      return -1;
    }
    
    // Now lets talk to the remote server to compare
    $postdata = http_build_query(
      array()
    );
    $opts = array('http' =>
      array(
        'method'  => 'POST',
        'header'  => 'Content-type: application/x-www-form-urlencoded',
        'content' => $postdata
      )
    );
    $context = stream_context_create( $opts );
    $rtime = file_get_contents( "https://www.genaside.net/taws/to_update.php", false, $context );
    
    if( ( $rtime - $time ) > 3600 ){ // Don't spam, give it some time
      return $rtime - $time;
    }else{      
      return 0;
    }           
  }
  
  /**
  * Dynamicly download cvs files dependings on the users query, since this
  * can be huge lets take it in little bits
  * Display progress
  * NOTE Iam going to get the full dumps in compress form instead.
  */
  function update(){
    global $taws_server_config;
    
    $languages = $taws_server_config[ 'sql_update_language' ]; 
    
    // TODO Show a sign that the data is being updated, so server.php knows
    // Mysql
    $mysqli = new mysqli( $taws_server_config[ 'mysql_server' ], 
                          $taws_server_config[ 'mysql_db_user' ], 
                          $taws_server_config[ 'mysql_db_pass' ], 
                          $taws_server_config[ 'mysql_db_name' ]);
                          
    $mysqli->options( MYSQLI_OPT_LOCAL_INFILE, true );    
    
    $driver = new mysqli_driver();
    $driver->report_mode = MYSQLI_REPORT_ALL;
    // Delete tables
    $mysqli->multi_query( file_get_contents( './sql/delete.mysql' ) );      
    while( $mysqli->more_results() ){ // I dont see anyway to escape this
      $mysqli->next_result(); 
    }
    
    // Recreate tables    
    $mysqli->multi_query( file_get_contents( './sql/create.mysql' ) );
    while( $mysqli->more_results() ){ // I dont see anyway to escape this
      $mysqli->next_result(); 
    }
    
        
    // Start downloading a db, extract it, and insert it into the sql. For each language
    foreach( $languages as $language ){      
      $postdata = http_build_query(
	array(
	  'language' => $language
	)
      );      
      $opts = array('http' =>
	array(
	  'method'  => 'POST',
	  'header'  => 'Content-type: application/x-www-form-urlencoded',
	  'content' => $postdata
	)
      );
      $context = stream_context_create($opts);  
      $res = file_put_contents( 
        "./serverdata/db_temp.tar.gz", 
        fopen( "https://www.genaside.net/taws/downloads/db_$language.tar.gz", 'r', false, $context ) 
      );
      if( $res == false ){
        echo "\nError: downloading database\n";
        continue;
      }
      
      // Extract
      exec( "tar -zxvf ./serverdata/db_temp.tar.gz -C ./serverdata/" );
      
      // Insert
      // Before populating tables lets set some things
      $mysqli->query( "SET sql_mode='NO_AUTO_VALUE_ON_ZERO';" );
      $mysqli->query( "SET FOREIGN_KEY_CHECKS = 0;" );
      $mysqli->query( "SET UNIQUE_CHECKS = 0;" );
      
      
      $query = <<<EOT
      LOAD DATA LOCAL INFILE './serverdata/dump/domains.csv' INTO TABLE Domains 
      FIELDS TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\\r\\n' IGNORE 1 LINES 
      (id,domain,http_https,type_id,subject_id,description);
EOT;
      $mysqli->query( $query );
      echo "Done populating Domains table.\n";
    
      $query = <<<EOT
      LOAD DATA LOCAL INFILE './serverdata/dump/data.csv' INTO TABLE Data 
      FIELDS TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\\r\\n' IGNORE 1 LINES 
      (id,page,domain_id,title,description,content,language_id,published_time,timestamp,frequency,scheme_id);
EOT;
      $mysqli->query( $query );
      echo "Done populating Data table.\n";
    
      $query = <<<EOT
      LOAD DATA LOCAL INFILE './serverdata/dump/ubm.csv' INTO TABLE ubm 
      FIELDS TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\\r\\n' IGNORE 1 LINES 
      ( query, data_id, rank );
EOT;
      $mysqli->query( $query );
      echo "Done populating UBM table.\n";
      
      // After populating tables lets set some things
      $mysqli->query( 'SET FOREIGN_KEY_CHECKS = 1' );  
      $mysqli->query( "SET UNIQUE_CHECKS = 1;" );
    }    
    
    // Remove some files, to free up space
    unlink( './serverdata/db_temp.tar.gz' );
    
    // Set up time stamp
    file_put_contents( './serverdata/db_timestamp.txt', serialize( time() ) );
    
    // Now lets update sphinx and call it a day
    passthru( "searchd --stop; sleep 2; indexer --all; searchd" );    
    
  }
  
  
  function download_db(){
  }
  
  
  exit();
  
  $ltime = 0;
  $rtime = 0;  
  
  /**
  * NOTE run this only the local database based on query is older then server.
  */
  function updateSearchDatabase(){
    //Don't run if another instance of this is running. 
    $status = file_get_contents( "./serverdata/state.txt" );
    if( $status && $status != '' ){
      echo "A instance of updating search database.";
      exit();
    }
    
    //set a way to run this one at a time
    file_put_contents( './serverdata/state.txt', 'RUNNING, started at ' . time() );
    set_time_limit( 0 );
  
    //Check if up to date first, if not the procede
    $db = new SQLite3( "./serverdata/masterv.sqlite", SQLITE3_OPEN_READONLY );
    if( !$db ){
      error_log( "middleman: can't open database" , 3, "./serverdata/log.txt" );
      exit();
    }
    $ltime = $db->querySingle( "SELECT max(timestamp) FROM Data;" );  
    $db->close();
    
    
    $postdata = http_build_query(
      array(
        'query' => "language = 'en' AND type != 'Pornography'",
      )
    );
    $opts = array('http' =>
      array(
        'method'  => 'POST',
        'header'  => 'Content-type: application/x-www-form-urlencoded',
        'content' => $postdata
      )
    );
    $context = stream_context_create( $opts );
    $rtime = file_get_contents( "https://www.genaside.net/taws/to_update.php", false, $context );
    
    if( $rtime == $ltime ){
      //update not required       
      echo "No updates";
      exit();
    }
    //update is required    
    
  
    $interval = 3600 * 12;//
    $min = $ltime;
    $max = $ltime;
    
    
    while( $min < $rtime ){
      //If at any time something change in the state.txt, abort.
      $status = file_get_contents( "./serverdata/state.txt" );
      if( !$status || $status == '' ){
        echo "Abourting update.";
        exit();
      }
    
      $max += $interval;
      
      $postdata = http_build_query(
        array(
          'query' => "language = 'en' AND type != 'Pornography'",
          'min' => $min,
          'max' => $max
        )
      );
      
      $opts = array('http' =>
        array(
          'method'  => 'POST',
          'header'  => 'Content-type: application/x-www-form-urlencoded',
          'content' => $postdata
        )
      );
      $context = stream_context_create($opts);  
      file_put_contents( "./serverdata/masterv_.sqlite", fopen( "https://www.genaside.net/taws/download1.php", 'r', false, $context ) );      
      
      //Now marge databases   
      $db = new SQLite3( "./serverdata/masterv.sqlite", SQLITE3_OPEN_READWRITE );
      if( !$db ){
        error_log( "middleman: can't open database" , 3, "./serverdata/log.txt" );
        exit();
      }
      $db->busyTimeout( 1000 * 60 * 5 );
      
      $db->exec( "PRAGMA synchronous = OFF;" );
      $db->exec( "PRAGMA journal_mode = MEMORY;" );
      $db->exec( "PRAGMA cache_size = 10000;" );  
      $db->exec( "PRAGMA temp_store = MEMORY;" ); 
      
      $db->exec( "ATTACH DATABASE './serverdata/masterv_.sqlite' AS external;" );
      $db->exec( "BEGIN TRANSACTION;" );
      $db->exec( "INSERT OR REPLACE INTO Data SELECT * FROM external.Data;" );
      $db->exec( "INSERT OR REPLACE INTO Domains SELECT * FROM external.Domains;" );
      $db->exec( "INSERT OR REPLACE INTO Types SELECT * FROM external.Types;" );
      $db->exec( "INSERT OR REPLACE INTO Languages SELECT * FROM external.Languages;" );     
      $db->exec( "END TRANSACTION;" );
      $db->exec( "DETACH DATABASE external;" );  
      
      $db->close();
      //echo "done";
      
      $min += $interval;    
    }  
    //release
    file_put_contents( './serverdata/state.txt', '' );
  }
  
  function searchDB_abortUpdate(){
    file_put_contents( './serverdata/state.txt', '' );  
  }
  
  function searchDB_isUpdating(){
    $status = file_get_contents( "./serverdata/state.txt" );
    if( $status && $status != '' ){
      return true;
    }else{
      return false;
    }  
  }  
  
  
  
  
  
  
  

  
  if( $_POST[ "procedure" ] == "get_word_db" ){
    $lang = $taws_sever_config[ "word_db_language" ];
    $filepath = "https://www.genaside.net/taws/languages/".$lang."/words.sqlite";
    file_put_contents( "./serverdata/words.sqlite", fopen( $filepath, 'rb', false ) );    
  }else if( $_POST[ "procedure" ] == "check_masterv_time" ){
    //ok ill check the difference, the times, but only by a curtain time
    $hour = 3600;
    $minute = 60;    
    $filepath = "./serverdata/counter.txt";
    
    $time = unserialize( file_get_contents( $filepath ) );
    if( $time ){      
      if( ( time() - $time ) > 0 ){
        //reset time
        file_put_contents( $filepath, serialize( time() ) );
        //ok time elapsed, lets check if database needs updating
        $db = new SQLite3( "./serverdata/masterv.sqlite", SQLITE3_OPEN_READONLY );
        if( !$db ){
          error_log( "middleman: can't open database" , 3, "./serverdata/log.txt" );
          exit();
        }
        $ltime = $db->querySingle( "SELECT timestamp FROM Data ORDER BY timestamp DESC LIMIT 1;" );  
        echo $ltime;
        //now get time from server
        $postdata = http_build_query(
          array(
            'query' => "language = 'en' AND type != 'Pornography'",
          )
        );

        $opts = array('http' =>
          array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $postdata
          )
        );
        $context = stream_context_create( $opts );
        $rtime = file_get_contents( "https://www.genaside.net/taws/to_update.php", false, $context );
        
        if( $rtime > $ltime ){
          //update required
          
        }
        
      }      
    }else{
      file_put_contents( $filepath, serialize( time() ) );
    }    
      
  }else if( $_POST[ "procedure" ] == "update_search_db"  ){
    updateSearchDatabase();   
  
  }
  
  
  /*
  $postdata = http_build_query(
    array(
      'query' => "language = 'en' AND type != 'Pornography'",
    )
  );

  $opts = array('http' =>
    array(
      'method'  => 'POST',
      'header'  => 'Content-type: application/x-www-form-urlencoded',
      'content' => $postdata
    )
  );
  $context = stream_context_create($opts);
  
  file_put_contents( "./serverdata/masterv.sqlite", fopen( "https://www.genaside.net/taws/download.php", 'r', false, $context ) );
  */
?>