<?php

  /**
  * This script will do all operations that requires downloading remote files
  */
 
  require_once './config/server_config.php'; 
  require_once './multisqlwrapper.php';  
  
  date_default_timezone_set( 'UTC' ); 
    
  // Set some script param options, long and short
  
  // Short
  $shortopts  = "";  
  $shortopts .= "h";
  $shortopts .= "a"; 
  $shortopts .= "c";   
  $shortopts .= "f:"; 
  $shortopts .= "u";
  $shortopts .= "i";
  $shortopts .= "x";
  
  // Long
  $longopts  = array(    
    "help",
    "check",
    "create-tables",    
    "file:",
    "update",
    "index",
    "initial"
  );  
  $options = getopt( $shortopts, $longopts );
  
  
  foreach( $options as $opt=>$opt_value ){
    switch( $opt ){      
      case 'h':
      case 'help':
        echo "\n";
        echo "This a standalone script for doing varios things out side taws\n";
        echo "-h     --help            Display options and help\n";
        echo "       --create-tables   Create or Re-create tables only. Existing tables and data will be deleted.\n";
        echo "-c     --check           Check if database is up to date, if not then by how much?\n";        
        echo "-f     --file            specify file(tar.gz) for data source when used with -u or -i\n";        
        echo "-i     --initial         The very first update.\n";
        echo "-u     --update          Update the database. Note that data will be at most a week old.\n"; 
        echo "-x     --index           Refresh Sphinx index.\n";  
             
        echo "\n";
      break;
      case 'a':
        echo "starting updater.\n";
        runUpdater();
      break;
      case 'c':
      case 'check':     
        $ltime = getLocalDataBaseTime();        
        $value = getRemoteDataBaseTime() - $ltime;
        
        // https://stackoverflow.com/questions/5088109/seconds-to-minutes-and-days-to-weeks
        if( $value > 0 && $ltime != 0 ){
          $units = array(
            'week' => 604800,
            'day' => 86400,
            'hour' => 3600,
            'minute' => 60
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
          //end( $strs );
          //$lastkey = key( $strs );
          //$strs[ $lastkey ] = 'and ' . $strs[ $lastkey ];
          echo "Your behind -> " . implode( ', ', $strs );         
        }else if( $value == 0 ){
          echo "You're up to date, congrats go and party";
        }else{
          echo "It doesnt seem you've did the initial update yet";
        }         
      break;
      case 'create-tables':
        createTables();
      break;
      case 'u':
      case 'update':
        if( array_key_exists( 'f', $options ) ){
	  update( $options[ 'f' ] );
	}else{
	  if( isUpdated() ){
	    echo "Already up to date.\n";
	    return;
	  }else{
	    echo "Going to update, this may take a while...\n";   
	    update();
	  }   
	}              
      break;
      case 'x':
      case 'index':
        refreshIndexes();
      break;
      case 'i':
      case 'initial':                
        if( array_key_exists( 'f', $options ) ){
          firstUpdate( $options[ 'f' ] );
        }else{
          firstUpdate();
        }
      break; 
      default: // This dont work
       echo "ERROR, option not supported \n";
    }
    echo "\n";
  }
  
  
  
  
  /** 
  * Check the the time of last update and the 
  * remote server's last update. Then compare them to
  * determine if update is needed.
  */
  function isUpdated(){    
    $rtime = getRemoteDataBaseTime(); // This should be bigger
    $ltime = getLocalDataBaseTime();
    
    if( ( $rtime - $ltime ) < 3600 ){ // Don't spam, give it some time
      return true;
    }else{      
      return false;
    } 
  }
  
  /**
  * Tables on the server changes alot.
  * Here is a funtion that will alter the
  * tables and files in ./sql folder.
  * The function will get schemas from the remote
  * database then using the downloaded data, alter the
  * tables to fit any changes.
  * @return status
  */
  function updateTableSchemas(){
  }
  
  /**
  * Run update in deamon mode
  */
  function runUpdater(){
    global $taws_server_config;
    //date_default_timezone_set( 'UTC' );
    date_default_timezone_set( $taws_server_config[ 'php_timezone' ] );
    
    while( true ){
      //day
      time_sleep_until( strtotime( "{$taws_server_config[ 'automatic_update_every' ]}", mktime( 0, 0, 0 ) ) );
      //time
      //echo strtotime( "+1 day", mktime( 0, 0, 0 ) );
      //echo strtotime( "{$taws_server_config[ 'automatic_update_at' ]}" ) . "  == " . time();
      time_sleep_until( strtotime( "{$taws_server_config[ 'automatic_update_at' ]} +1 sec" ) );
      // It's time to update 
      update();
      //done! that is it.
    }  
  }
  
    
  /**
  * Create or Recreate tables for database
  */
  function createTables(){ 
    global $taws_server_config;    
    
    $gsql = new GenericSQL;
    if( !$gsql->connect() ){
      exit( "ERROR: can't connect to database" );
    }  
    
    // Delete tables
    echo "Droping any existing tables ...\n";
    $gsql->exec( file_get_contents( "./sql/drop_tables.{$taws_server_config[ 'sql_program' ]}" ) );
         
    // Recreate tables
    echo "Creating tables ...\n";
    $gsql->exec( file_get_contents( "./sql/create_tables.{$taws_server_config[ 'sql_program' ]}" ) );
    
    $gsql->disconnect(); 
  }
  
  /**
  * Re index data for sphinx.
  * Stop sphinx, start the indexer, then start sphinx again( in that order )
  * @param index_names specify the index(es) that needs to be indexed. 
  * @note 'index_names' Seperate indexes by a space.
  */
  function refreshIndexes( $index_names = '--all' ){     
    $indexes = array( 
      'taws_ar_idx_c0',
      'taws_ar_idx_c1',
      'taws_zh_idx_c0',
      'taws_zh_idx_c1',
      'taws_en_idx_c0',
      'taws_en_idx_c1',  
      'taws_fr_idx_c0',
      'taws_fr_idx_c1',
      'taws_de_idx_c0',
      'taws_de_idx_c1',
      'taws_it_idx_c0',
      'taws_it_idx_c1',
      'taws_ja_idx_c0',
      'taws_ja_idx_c1',
      'taws_pl_idx_c0',
      'taws_pl_idx_c1',
      'taws_ru_idx_c0',
      'taws_ru_idx_c1',
      'taws_es_idx_c0',
      'taws_es_idx_c1',
      'taws_idx'
    );  
    $index_names = implode( ' ', $indexes );
    
    // If sphinx is running stop it.
    
    // Typical places for the pid file to be located
    $pid_files = array(
      '/var/lib/log/searchd.pid'
    );
    
    $pid_is_running = false;
    $pid = null;
    // find the actual pid file then test if the pid is running
    foreach( $pid_files as $file ){
      if( file_exists( $file ) ){
        // get and test the pid
        $pid = file_get_contents( $file );
        
        if( shell_exec( "ps aux | grep searchd | grep " . (int)$pid . " | wc -l" ) > 1 ){
          $pid_is_running = true;          
        }
      }
    }
    
    // if sphinx is running then stop it 
    if( $pid_is_running ){
      echo "Stopping Sphinx via 'searchd --stop'.\n";
      passthru( "searchd --stop;" );
      // Now wait for it to finish
      
      $count = 0;
      while( shell_exec( "ps aux | grep searchd | grep " . (int)$pid . " | wc -l" ) > 1 ){
        if( $count > 10 ){
          exit( "ERROR: Stopping Sphinx(searchd) timed out." );
        }
        usleep( 500000 );
        ++$count;
      }
    }
        
    // Start indexing the database
    echo "Staring indexer.\n";
    passthru( "indexer $index_names" );
    echo "Indexing Done.\n";
    
    // start sphinx
    echo "Starting Sphinx.\n";
    passthru( "searchd" );
  }
  
  /**
  * Run the post query, for tweeking
  */
  function runPostQuery(){
    global $taws_server_config;
    $gsql = new GenericSQL;
    if( !$gsql->connect() ){
      exit( "ERROR: can't connect to database" );
    }
    echo "Running post query\n";
    $gsql->exec( $taws_server_config[ 'post_update_query' ] );
    $gsql->disconnect();   
  }
  
  /**
  * Update Database by downloading NEW data from the server or file.
  * After the update-file has been downloaded, it will 
  * be unpacked and inserted into the database. 
  * @param file_dump instead getting the data remotly, you can select a file
  */
  function update( $file_dump = null ){    
    global $taws_server_config;
    
    // Create a lock file to make program unique
    $file = fopen( "./serverdata/update_status", "w" ); 
    if( !flock( $file, LOCK_EX | LOCK_NB ) ){
      echo "ERROR: Another process, has a lock to './serverdata/update_status'.\n"; 
      return;
    }
    
    // local file vs remote file
    if( $file_dump == null ){
      // Download new data
      echo "Downloading generated Database dump ...\n";
      $postdata = http_build_query(
	array(        
	  'start_time' => getLocalDataBaseTime(), // send a starting timestamp
	  'languages' => $taws_server_config[ 'sql_update_language' ],
	  'data' => $taws_server_config[ 'enable_default_table_update' ],
	  'data_rss' => $taws_server_config[ 'enable_rss_table_update' ],
	  'data_files' => $taws_server_config[ 'enable_file_table_update' ]
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
      $res = file_put_contents( 
	"./serverdata/db_temp.tar.gz",
	fopen( $taws_server_config[ 'source_remote_url' ], 'r', false, $context ) 
      ); 
      if( $res === false ){
	echo "Error downloading update from server.\n";
	return;
      }
      
      // Unpack data
      echo "Unpacking archive ...\n";
      exec( "tar -zxvf ./serverdata/db_temp.tar.gz -C ./serverdata/" );    
      
      // Delete packed data
      echo "Deleting downloaded archive ...\n";
      unlink( "./serverdata/db_temp.tar.gz" );
    }else{
      echo "Unpacking archive ...\n";
      exec( "tar -zxvf $file_dump -C ./serverdata/" );    
    }            
        
    // Insert data into the Database
    $gsql = new GenericSQL;
    if( !$gsql->connect() ){
      exit( "ERROR: can't connect to database" );
    }
    echo "Updating tables ...\n";
    $gsql->exec( file_get_contents( "./sql/update.{$taws_server_config[ 'sql_program' ]}" ) );    
    
    $gsql->disconnect();          
    fclose( $file );
    
    runPostQuery();
    
    // If everything went well then set time
    setLocalDataBaseTime();
      
    echo "Cleaning up ...\n";
    exec( "rm ./serverdata/dump/*" ); // too lazy
    
    // I guess iam done
    echo "Update is complete.\n";
  }  
  
  
  /**
  * This is different from the regular update since its attened for 
  * inserting huge data in a already empty database. Also, it is faster
  * then update because it bypasses foreign key checks and unique checks.
  * It will be possible tp insert more data using this function if you know it
  * wont conflic.
  * @param file_dump instead getting the data remotly, you can select a file
  */
  function firstUpdate( $file_dump = null ){
    global $taws_server_config;
        
    // Create a file
    $file = fopen( "./serverdata/update_status", "w" ); 
    if( !flock( $file, LOCK_EX | LOCK_NB ) ){
      echo "ERROR: another process, has a lock to this 'update_status' file.\n"; 
      return;
    }
    
    $gsql = new GenericSQL;    
    if( !$gsql->connect() ){
      exit( "ERROR: cant' connect to database" );
    }
            
    // anon function to download csv compressed files    
    $downloadFile = function( $source, $destination ){
      $opts = array('http' =>
	array(
	  'method'  => 'POST',
	  'header'  => 'Content-type: application/x-www-form-urlencoded'
	)
      );
      $context = stream_context_create( $opts );  
      
      $res = file_put_contents( $destination, fopen( $source, 'r', false, $context ) );
      
      if( $res === false ){
        echo "\nError: downloading file\n";            
        return;
      }
      
    };
    
    // anon funtion to extract compressed file, and insert the data into the database.
    $updateTables = function()  use( &$taws_server_config, &$gsql ){
      echo "Unpacking file ...\n";
      exec( "tar -zxvf ./serverdata/db_temp.tar.gz -C ./serverdata/" );
      echo "Cleaning up ...\n";
      unlink( './serverdata/db_temp.tar.gz' );
      echo "Populating tables ...\n";
      $gsql->exec( file_get_contents( "./sql/bulk_insert.{$taws_server_config[ 'sql_program' ]}" ) ); 
      echo "Cleaning up ...\n";
      exec( "rm ./serverdata/dump/*" );
    };    
        
    // If it should be done by local file or remote server
    if( $file_dump != null ){
      echo "Unpacking file ...\n";
      exec( "tar -zxvf $file_dump -C ./serverdata/" );      
      echo "Populating tables ...\n";
      $gsql->exec( file_get_contents( "./sql/bulk_insert.{$taws_server_config[ 'sql_program' ]}" ) ); 
      echo "Cleaning up ...\n";
      exec( "rm ./serverdata/dump/*" );   
    }else{
      foreach( $taws_server_config[ 'sql_update_language' ] as $language ){     
	if( $taws_server_config[ 'enable_default_table_update' ] ){
	  echo "Downloading db_$language.tar.gz ...\n";
	  $downloadFile( 
	    "https://www.genaside.net/taws/downloads/db_$language.tar.gz", 
	    "./serverdata/db_temp.tar.gz" 
	  ); 
	  $updateTables();	
	}
	
	if( $taws_server_config[ 'enable_rss_table_update' ] ){
	  echo "Downloading db_rss_$language.tar.gz ...\n";
	  $downloadFile( 
	    "https://www.genaside.net/taws/downloads/db_rss_$language.tar.gz", 
	    "./serverdata/db_temp.tar.gz" 
	  ); 
	  $updateTables();
	}             
      }    
      
      if( $taws_server_config[ 'enable_file_table_update' ] ){
	echo "Downloading db_files.tar.gz ...\n";
	$downloadFile( 
	  "https://www.genaside.net/taws/downloads/db_files.tar.gz", 
	  "./serverdata/db_temp.tar.gz" 
	); 
	$updateTables();
      }    
    }     
    $gsql->disconnect();    
    
    runPostQuery();
    
    // Set up time stamp    
    setLocalDataBaseTime( true );    
    
    flock( $file, LOCK_UN );
    fclose( $file );    
    
    echo "done.\n";
  }
  
  /**
  * Set the time so show when database was last updated
  */
  function setLocalDataBaseTime( $get_dump_time = false ){
    if( $get_dump_time == false ){
      file_put_contents( './serverdata/db_timestamp.txt', serialize( getRemoteDataBaseTime() ) );
    }else{
      file_put_contents( './serverdata/db_timestamp.txt', serialize( getRemoteDataDumpTime() ) );
    }    
  }
  
  /**
  * Get the time of the last update
  */
  function getLocalDataBaseTime(){
    return unserialize( file_get_contents( './serverdata/db_timestamp.txt' ) );    
  }
  
  /**
  * Get the time of the remote database of when it updated
  */
  function getRemoteDataBaseTime(){
    $opts = array('http' =>
      array(
        'method'  => 'POST',
        'header'  => 'Content-type: application/x-www-form-urlencoded',        
      )
    );
    $context = stream_context_create( $opts );
    return file_get_contents( "https://www.genaside.net/taws/to_update.php", false, $context );    
  }
  
  /**
  * Get the time of the the remote database's dumps, the cvs tar.gz compressed files.
  */
  function getRemoteDataDumpTime(){
    $postdata = http_build_query(
      array(        
        'dump' => true
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
    return file_get_contents( "https://www.genaside.net/taws/to_update.php", false, $context );    
  }
  
  
?>