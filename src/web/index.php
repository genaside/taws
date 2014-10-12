<?php
  //session_start();
  include './languages/english.php';
  
  $searchquery = '';
  if( isset( $_GET['q'] ) && $_GET['q'] != '' ){
    //search page with results    
    $searchquery = htmlentities( $_GET['q'] );     
  }else{
    //search page, main page    
  } 
  
  $domain = '';
  if( isset( $_GET['d'] ) && $_GET[ 'd' ] != '' ){
    $domain = $_GET['d'];
  }
  
  $lang = '';
  if( isset( $_GET[ 'lang' ] ) && $_GET[ 'lang' ] != '' ){
    $lang = explode( ',', $_GET[ 'lang' ] );
    $lang = implode( ',', $lang );
  }
  $st = '';
  if( isset( $_GET[ 'sitetype' ] ) && $_GET[ 'sitetype' ] != '' ){
    $st = explode( ',', $_GET[ 'sitetype' ] );
    $st = implode( ',', $st );
  }
  
  $scheme = -1;
  if( isset( $_GET[ 'scheme' ] ) && $_GET[ 'scheme' ] != -1 ){
    $scheme = $_GET[ 'scheme' ];
  }
  
  $menu = <<< EOT
  <ul id="main-menu">
    <li><a href="#">Spring</a></li>    
    <li><a href="#">Java</a>
      <ul>
	<li><a href="#">Java IO</a></li>	
      </ul>
    </li>
    <li><a href="#">Settings</a></li>    
  </ul>
EOT;

  
  $seachpage = <<< EOT
<!DOCTYPE HTML>
<html lang="$lang_code">

<head>
  <title>$main_page_title</title>
  <meta charset="UTF-8">
  <link rel="icon" href="./images/taws-favicon.png">
  <link rel="stylesheet" type="text/css" href="./style.css">
  <link rel="stylesheet" type="text/css" href="./js/jquery-ui/jquery-ui.css">
  
  <script src="./includes/js/jquery-2.1.1.js"></script>
  <script src="./includes/js/jquery-ui/jquery-ui.js"></script>
  <script src="./client.js"></script>
  <script>
    // INIT-PRE    
    conditions.d = "$domain";
    conditions.l = "$lang"; 
    conditions.t = "$st";
    conditions.scheme = $scheme;
  </script>
</head>

<body>
  <div class="header">
    <a href="/"><img src="./images/taws-logo-48.png"><h2 id="mainpage_logo_name">Taws</h2></a>
    
    <div id="search-container">
      <input id="search-input" type="text" value="$searchquery" autocomplete="off" >
      <button id="search-button" type="button"  onclick="newSearch()"></button>
    </div>
<!--
    <div id="menu">
      <a id="menubutton" >&#9776;</a>
      <ul id="menuitems">
        <li>Update Search Database</li>
        <li><a >Get Help</a></li>
        <li><a >Settings</a></li>
      </ul>      
    </div>
--> 
  </div>  
  
  <div id="advance-search-options">
    <h3>Advance Search</h3>
    <h4>URL Schema</h4>
    
    <form>
      <div id="buttonset-scheme" title='Select a specific url scheme'>
        <input type="radio" id="button-schema-all" name="scheme-group" value="-1" checked>
	<label for="button-schema-all">All</label> 
	<input type="radio" id="button-schema-http" name="scheme-group" value="1">
	<label for="button-schema-http">http</label>
	<input type="radio" id="button-schema-https" name="scheme-group" value="2">
	<label for="button-schema-https">https</label>	
      </div>
    </form>    
    
    <h4>Domain</h4>
    <input type="text" id="textbox-domain" size="28">
    
    <h4>Language</h4>
    <button id="button-language" title='Select specific language(s) to search with'>Languages</button>
    <div id="select-language-dialog" title="Select Languages">      
      <input type="checkbox" id="language-en" name="language-group" value="1">
      <label for="language-en">English</label>
      <input type="checkbox" id="language-es" name="language-group" value="2">
      <label for="language-es">Spanish</label>
    </div>
    
    <h4>Site Type</h4>
    <button id="button-sitetype" title='Select specific Types(s) of sites to search for'>Types</button>   
    <div id="select-type-dialog" title="Select Types">      
      <input type="checkbox" id="type-0" name="type-group" value="0">
      <label for="type-0">Encyclopedia</label>
      <input type="checkbox" id="type-1" name="type-group" value="1">
      <label for="type-1">Manual</label>
      <input type="checkbox" id="type-2" name="type-group" value="2">
      <label for="type-2">General</label>
      <br>
      <input type="checkbox" id="type-3" name="type-group" value="3">
      <label for="type-3">Pornography</label>       
      <input type="checkbox" id="type-4" name="type-group" value="4">
      <label for="type-4">Entertainment</label>  
      <br>
      <input type="checkbox" id="type-6" name="type-group" value="6">
      <label for="type-6">Dictionary</label>
      
    </div>
    
    <hr>
    <button id="button-reset" title='Reset advance options to defualt'>Reset</button>
    
    <hr>
    <h3>Donate</h3>    
    <p style='word-wrap: break-word;'>Bitcon:<br>1LLBBL9tdLK4qQs7AZ3eR3q63HZpVXDYvJ</p>    
    <p style='word-wrap: break-word;'>Litecoin:<br>LckqWy236X5vyVQXz1rspxg3kzEcRkmS7U</p>
    
    
  </div>
  
  
  <div id="spellfix-container">
    <p id="did-you-mean">Did you mean: </p>
    <a id="spellfix"></a>
  </div>
  
  <button id="button-top">Top</button>
  <div id="search-results-container">     
  </div>
  
  <script>    
    initialSearch();
  </script>  
</body>
</html>
EOT;

  $intropage = <<< EOT
<!DOCTYPE HTML>
<html lang="$lang_code">
<head>
  <meta charset="UTF-8">
  <title>$main_page_title</title>  
  <link rel="icon" href="./images/taws-favicon.png">
  <link rel="stylesheet" type="text/css" href="./style.css">
  <link rel="stylesheet" type="text/css" href="./js/jquery-ui/jquery-ui.css">
  
  <script src="./includes/js/jquery-2.1.1.js"></script>  
  <script src="./includes/js/jquery-ui/jquery-ui.js"></script>
  <script src="./client.js"></script>
</head>
<body>   
  <h2>Beta 0.0.1 : This site is for testing only</h2>
  <img id="homepage_logo" src="./images/taws-logo.png">
  <h2 id="homepage_logo_name">Taws</h2>
    
  <div id="homepage_search-container">  
    <input id="search-input" type="text" autocomplete="off" >
    <button id="search-button" type="button" onclick="newSearch()"></button>
  </div>
  
  <footer id="homepage_footer">
    <p>
      Powerd by
        <a href="//sphinxsearch.com/"><img src="./images/sphinx-logo.png"></a>
      and other opensource projects.
    </p>  
    <p>
      Created by Genaside.net 2014.
    </p>
  </footer> 
</body>
</html>
EOT;


  
  if( $searchquery != '' ){
    //run template
    echo $seachpage;
  }else{    
    echo $intropage;
  } 




?>






