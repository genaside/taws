<?php
  include './config/server_config.php';
  //error_reporting( 0 );
  
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