<?php
  // Entry script 
  
  //session_start();
  
  require './includes/php/Mobile_Detect.php'; 
  
  // Check if the database is being uppdated
  $file = fopen( "./serverdata/update_status", "w" ); 
  if( !flock( $file, LOCK_EX | LOCK_NB ) ){
    echo "<h1>Taws is updating it's database. <br> Please wait a while then refresh the page.<h1>"; 
    return;
  }
  fclose( $file ); 
  
  
  
  $searchquery = '';   
  // Taken the behavoir from other search engines when the 'q' var is defined
  if( isset( $_GET['q'] ) && $_GET['q'] != '' ){
    //search page with results    
    $searchquery = htmlentities( $_GET['q'] );   
  }
  
  
  // Instalize keys for post/get if not installized 
  if( !isset( $_GET[ 'domain' ] ) ){
    $_GET[ 'domain' ] = '';
  }  
  if( !isset( $_GET[ 'lang' ] ) ){
    $_GET[ 'lang' ] = '';        
  }  
  if( !isset( $_GET[ 'type' ] ) ){    
    $_GET[ 'type' ] = '';
  } 
  if( !isset( $_GET[ 'subject' ] ) ){
    $_GET[ 'subject' ] = '';  
  }  
  if( !isset( $_GET[ 'scheme' ] ) ){    
    $_GET[ 'scheme' ] = '';
  }
  if( !isset( $_GET[ 'time_to' ] ) ){    
    $_GET[ 'time_to' ] = '';
  }
  if( !isset( $_GET[ 'time_from' ] ) ){    
    $_GET[ 'time_from' ] = '';
  }
  if( !isset( $_GET[ 'group_by' ] ) ){    
    $_GET[ 'group_by' ] = '';
  }  
  if( !isset( $_GET[ 'order_by' ] ) ){    
    $_GET[ 'order_by' ] = '';
  }
  
  if( !isset( $_GET[ 'ft' ] ) ){
    $_GET[ 'ft' ] = '';        
  }  
  if( !isset( $_GET[ 'op' ] ) ){    
    $_GET[ 'op' ] = 0;
  }
  
  
  // Installize seesion varibles
  session_start();  
  if( !isset( $_SESSION[ 'authenticated' ] ) ) {
    $_SESSION[ 'authenticated' ] = false;
  }
  session_write_close();
    
  // Special formats
  if( isset( $_GET[ 'format' ] ) && $_GET[ 'format' ] != '' ){    
    // Get results from database
    $conditions = array( 
      'domain' => $_GET[ 'domain' ], // domains
      'scheme' => $_GET[ 'scheme' ], // url scheme  
      'language' => $_GET[ 'lang' ], // language
      'type' => $_GET[ 'type' ], // Site type
      'subject' => $_GET[ 'subject' ], // Site type
      'time_from' => $_GET[ 'time_from' ], // time from
      'time_to' => $_GET[ 'time_to' ], // time to
      'group' => $_GET[ 'group_by' ], // group by
      'order' => $_GET[ 'order_by' ], // order by 
      'operation' => ''      
    );
    include './rss.php';
    header('Content-Type: application/rss+xml;charset=utf-8 ');
    generateRSS( $_GET['q'] ,$conditions );
    return;
  }  
  
  
  // Check if device is mobile or desktop and run appropriate program
  if( ( new Mobile_Detect )->isMobile()  ) {   
    include './mobile.php';    
  }else{
    include './desktop.php';    
  }

?>






