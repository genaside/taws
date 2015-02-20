<?php

  // Log Levels    
  define( "TAWS_LOG_NONE",     0 );
  define( "TAWS_LOG_CRITICAL", 1 );
  define( "TAWS_LOG_INFO",     2 );
  define( "TAWS_LOG_OTHER",    4 );
  define( "TAWS_LOG_ALL",      7 );  
    
  
  /**
  * A function to handle logs. All messages are pass in here
  * so that it can be properly handle.
  * NOTE people spoke of klogger, iam using php's error_log for now 
  * NOTE Options must be on to work.
  * @param message The messege that needs to be logged
  * @param type the nature of the messege and how it will be filtered in respects to log level.
  * @param typestr string to catogrize the messege
  */
  function logger( $message, $type, $typestr ){    
    $datatime = date( 'Y-m-d H:i:s' );
    $user = $_SERVER[ 'REMOTE_ADDR' ]; // AS in IP    
        
    // Filter out messeges depending on the setting
    global $taws_server_config;
    
    if( $type | $taws_server_config[ 'log_level' ] ){
      error_log( "[$datatime][$user][$typestr]: $message \n", 3, "./logs/server.log" );
    }    
    
    
  }
  

?>