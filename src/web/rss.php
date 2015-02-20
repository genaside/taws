<?php
  // Generate rss page
  
  function generateRSS( $ss, $conditions ){
    // Use the conditions to get a list of results for rss
    $postdata = http_build_query(
      array(        
        'procedure' => 'search_and_get_result',
        'ss' => $ss,
        'conditions' => $conditions,
        'limit' => '24',
        'offset' => '0'
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
    // The results we need ro populate rss items
    $results = json_decode( file_get_contents( "http://localhost:4132/server.php", false, $context ), true );
    
    // Build each item according to the results
    $items = '';
    foreach( $results as $row ){
      $link = htmlspecialchars(  $row['page_url'] );      
      $title = htmlspecialchars( $row[ "page_title" ] );      
      $description = htmlspecialchars( $row['page_description'] );     
      $time = '';
      if( $row["published_time"] && $row["published_time"]!= 0 ){
        date_default_timezone_set( 'GMT' );
        $time = date( "Y-m-d", $row["published_time"] );
        $time = htmlspecialchars( $time );
      }
      // Build
      $items .= <<<EOT
      <item>
        <title>$title</title>
        <link>$link</link>
        <description>$description</description>
        <pubDate>$time</pubDate>
      </item>\n
EOT;
    }
    // Now lets create pages structure  then insert the items
    
    // Need the current url to show in rss
    $url = 'http'.(isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}/{$_SERVER['REQUEST_URI']}";
    $url = htmlentities( $url );  
    
    $rss = <<<EOT
<?xml version="1.0" encoding="utf-8" ?>
<rss version="2.0">
<channel>
  <title>Taws - Search Engine</title>  
  <description>Search from the privacy of your own home</description>
  <link>$url</link>  
$items   
</channel>
</rss> 
EOT;
  
    // Now echo and done  
    echo $rss;
  }
  
  

  






























?>