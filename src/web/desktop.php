<?php
  // NOTE must be called from index.php
  // Php script for the descktop version
  
  
  require_once './languages/language_selector.php';
  require_once './multisqlwrapper.php';
  
  
  
  $searchquery = '';
  
  // A flag for wether to run the main page or the result page
  $result_window_flag = false; 
  
  
  
  // Taken the behavoir from other search engines when the 'q' var is defined
  if( isset( $_GET['q'] ) && $_GET['q'] != '' ){
    //search page with results    
    $searchquery = htmlentities( $_GET['q'] );  
    $result_window_flag = true; 
  }else{
    // go into main window(no reults)
    $result_window_flag = false;
  }  
  
  
  
  // Generate options from Database
  $lang_opt = '';
  $type_opt = '';
  $subject_opt = '';
  $ft_opt = '';
  
    
  $gs = new GenericSQL;
  $gs->connect();
  
  // Generate list of options like types, schemes , and languages
  $results = $gs->query( "SELECT * FROM Languages WHERE id > 0 ORDER BY full_name;" );  
  while( $row = $results->fetchRow() ){    
    $lang_opt .=
    "<li>
      <div id='language-{$row[ 'language' ]}' class='tri-value' value='0'>
        <span class='ui-icon ui-icon-blank'></span>
        <div value='{$row[ 'id' ]}'>{$row[ 'full_name' ]} ( {$row[ 'natural_name' ]} )</div>
      </div>
    </li>\n";             
  }
  
  $results = $gs->query( "SELECT * FROM Types;" );  
  while( $row = $results->fetchRow() ){	    
    $type_opt .=
    "<li>
      <div class='tri-value' value='0'>
        <span class='ui-icon ui-icon-blank'></span>
        <div value='{$row[ 'id' ]}'>{$row[ 'type' ]}</div>        
      </div>
    </li>\n";
  }
  
  $results = $gs->query( "SELECT * FROM Subjects;" );  
  while( $row = $results->fetchRow() ){	    
    $subject_opt .=
    "<li>
      <div class='tri-value' value='0'>
        <span class='ui-icon ui-icon-blank'></span>
        <div value='{$row[ 'id' ]}'>{$row[ 'subject' ]}</div>        
      </div>
    </li>\n";
  }
  
  $results = $gs->query( "SELECT * FROM FileTypes;" );  
  while( $row = $results->fetchRow() ){	    
    $ft_opt .=
    "<li>
      <div class='tri-value' value='0'>
        <span class='ui-icon ui-icon-blank'></span>
        <div value='{$row[ 'id' ]}'>{$row[ 'type' ]}</div>        
      </div>
    </li>\n";
  }
  
  $gs->disconnect();
  
    
  

  $ls_lang = (new LanguageSelect( "english" ))->getLanguage();
  
  $html_header_content = <<<EOT
  <meta charset="UTF-8">
  
  <meta name="description" content="{$ls_lang['HOMEPAGE_HTML_HEADER_DESCRIPTION']}" />
  <title>{$ls_lang['HOMEPAGE_HTML_HEADER_TITLE']}</title>  
  
  
  <link rel="icon" href="./images/taws-favicon.png">  
  <link rel="stylesheet" type="text/css" href="./includes/js/jquery-ui/jquery-ui.css">
  <link rel="stylesheet" type="text/css" href="./desktop.css">
  
  <script src="./includes/js/jquery-2.1.1.js"></script>  
  <script src="./includes/js/jquery-ui/jquery-ui.js"></script>
  
  <script src="./client_common.js"></script>
  <script src="./client_desktop.js"></script>
  
  <script>
    // INIT-PRE    
    conditions.domain = "{$_GET[ 'domain' ]}";
    conditions.language = "{$_GET[ 'lang' ]}"; 
    conditions.type = "{$_GET[ 'type' ]}";
    conditions.subject = "{$_GET[ 'subject' ]}";
    conditions.scheme = "{$_GET[ 'scheme' ]}";
    conditions.time_to = "{$_GET[ 'time_to' ]}";
    conditions.time_from = "{$_GET[ 'time_from' ]}";
    conditions.group = "{$_GET[ 'group_by' ]}";
    conditions.order = "{$_GET[ 'order_by' ]}";
    
    conditions.file_type = "{$_GET[ 'ft' ]}";    
    conditions.operation = {$_GET[ 'op' ]};
    
  </script>  
EOT;
  
  // List of all dialogs that will later be inserted in the html
  $popup_dialogs = <<<EOT
  <!-- popup login form -->
  <div id="login-dialog" class="dialog" title="Login">
    <form id="login-form" action="javascript:void(0)">
      <fieldset>	
	<label for="password">Admin Password:</label>
	<input type="password" name="password" id="password" class="text ui-widget-content ui-corner-all">
	<p>Status:</p>
	<input type="submit" tabindex="-1" style="position:absolute; top:-1000px">
      </fieldset>
    </form>
  </div>  
  
  <div id="about-dialog" class="dialog" title="About">
    <p>Taws v0.0.2 beta</p>
    <p>Created by a nameless soul at genaside.net</p>
  </div>    
  
  <div id="loading-alert">
    <p><img src="./images/ajax-loader.gif"/></p>
  </div>
   
EOT;


  
  
  $settings_menu_admin = "";
/*
  if( !$_SESSION[ 'authenticated' ]  ){
    $settings_menu_admin = <<<EOT
    <li id="cmd_settings_login"><span class="ui-icon ui-icon-person"></span>Admin login</li> 
EOT;
  }else{    
    $settings_menu_admin = <<<EOT
    <li><span class="ui-icon ui-icon-person"></span>
      Admin Tools
      <ul>
        <li id="cmd_settings_logout">Logout</li>	
	<li id="cmd_settings_update_db">Update Database</li>	
      </ul>
    </li> 
EOT;
  }
*/



  $settings_menu = <<<EOT
  <button id="button-settings">Settings</button>
  <ul id="settings-selectmenu">
  
    $settings_menu_admin 
    
    <li> </li>
    <li id="cmd_settings_display_rss">RSS Format</li>
    <li id="cmd_settings_about">About</li>      
    <li><span class="ui-icon ui-icon-heart"></span>
      Donate
	<ul>
	  <li><a target="_blank" href="bitcoin:1LLBBL9tdLK4qQs7AZ3eR3q63HZpVXDYvJ">Bitcoins</a></li>
	  <li><a target="_blank" href="litecoin:LckqWy236X5vyVQXz1rspxg3kzEcRkmS7U">Litecoin</a></li>
	</ul>
    </li>            
  </ul>
  
EOT;
  
  
  $seachpage = <<<EOT
<!DOCTYPE HTML>
<html lang="{$ls_lang['ISO_CODE']}">
<head>
  $html_header_content
  <script>
    document.title = "$searchquery";
  </script>
</head>

<body>
  $popup_dialogs

  <div class="header">
    <a href="/"><img src="./images/taws-logo-48.png"><h2 id="mainpage_logo_name">Taws</h2></a>
    
    <div id="search-container">
      <input id="search-input" type="text" value="$searchquery" autocomplete="off" >
      <button id="search-button" type="button"  onclick="newSearch()"></button>
    </div>
    
    
    $settings_menu


  </div>  
  
  <div id="advance-search-options">
    <div id="tabs">
      <ul>
        <li><a href="#tabs-1">Web</a></li>
        <li><a href="#tabs-2">RSS</a></li>
        <li><a href="#tabs-3">Files</a></li>
      </ul>
      
      
      <div id="tabs-1" class="accordion">
	<h3>Filter</h3>
	<div>
	  <h4 class="ui-widget-header">Scheme</h4> 
	  <ul id="scheme-selectmenu" class="menu">
	    <li>
	      <div class="tri-value" value="0">
		<span class="ui-icon ui-icon-blank"></span>
		<div value="1">http</div>
	      </div>
	    </li>
	    <li>
	      <div class="tri-value" value="0">       
		<span class="ui-icon ui-icon-blank"></span>
		<div value="2">https</div>              
	      </div>
	    </li>            
	  </ul>
	  
	  <div>
	    <h4 class="ui-widget-header">Domain Name</h4>
	    <input type="text" id="textbox-domain" placeholder="www.example.com" class="input-maxsize">
	  </div>
	  
	  <div>
	    <h4 class="ui-widget-header">Languages</h4>            
	    <ul id="language-selectmenu" class="menu">
	      $lang_opt
	    </ul>
	  </div>
	  
	  <div>
	    <h4 class="ui-widget-header">Types</h4>            
	    <ul id="type-selectmenu" class="menu">
	      $type_opt
	    </ul>
	  </div>
	  
	  <div>
	    <h4 class="ui-widget-header">Genre</h4>            
	    <ul id="subject-selectmenu" class="menu">
	      $subject_opt
	    </ul>
	  </div>
	  
	  <div>
	    <h4 class="ui-widget-header">Published Date Range</h4>
	    <label for="from">From:</label>
	    <input type="text" id="time_from" name="from" class="input-maxsize">
	    <br>
	    <label for="to">to:</label>
	    <input type="text" id="time_to" name="to" class="input-maxsize"> 
	  </div>       
	</div>
	
	<h3>Group By</h3>
	<div>
	  <form>
	    <fieldset>
	      <legend>Primary</legend>                
	      <select id="pri-group-by">
		<option value="" ></option>
		<option value="domain_id" selected>Domain</option>
	      </select>
	    </fieldset>
	  </form>
	</div>
	
	<h3>Order BY</h3>
	<div>
	  <form>
	    <fieldset>
	      <legend>Primary</legend>                
	      <select id="pri-order-by">
		<option value="" ></option>
		<!--
		<option value="timestamp DESC" >Timestamp (Descending)</option>
		<option value="timestamp ASC" >Timestamp (Ascending)</option>
		-->
		<option value="frequency DESC" selected>Frequency (Descending)</option>
		<option value="frequency ASC" >Frequency (Ascending)</option>
		<option value="published_time DESC" >Published Date (Descending)</option>
		<option value="published_time ASC" >Published Date (Ascending)</option>
	      </select>
	    </fieldset>
	    
	    <!--
	    <fieldset>
	      <legend>Secondary</legend>                
	      <select id="sec-order-by">
		<option></option>
		<option>Timestamp</option>
		<option>Frequency</option>
		<option>Published Date</option>                  
	      </select>
	    </fieldset>
	    -->
	  </form>
	</div>	
      </div>
      
      
      <div id="tabs-2">
        <p>No filter set up yet</p>
      </div>
      
      <div id="tabs-3" class="accordion">
	<h3>Filter</h3>
	<div>
	  <h4 class="ui-widget-header">File Type</h4> 
	  <ul id="filetype-selectmenu" class="menu">
	    $ft_opt   
	  </ul>	
	  <h4 class="ui-widget-header">File Size</h4> 
	  <form>
	    <fieldset>
	      <legend>Unit</legend>                
	      <select id="filesizeunit-menu">
		<option value="0" >B  (byte)</option>
		<option value="1" selected>KB (Kilobyte)</option>
		<option value="2" selected>MB (Megabyte)</option>
		<option value="3" selected>GB (Gigabyte)</option>
	      </select>
	      
	       <label for="amount">Size Range:</label>
               <input type="text" id="amount" readonly style="border:0; color:green; font-weight:bold;">
	      <div id="filesize-slider-range"></div>
	    </fieldset>
	  </form>
	</div>	
	
	
      </div>
      
      
      
    </div>
  
    
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

  $intropage = <<<EOT
<!DOCTYPE HTML>
<html lang="{$ls_lang['ISO_CODE']}">
<head>
  <meta charset="UTF-8">
  <title>{$ls_lang['HOMEPAGE_HTML_HEADER_TITLE']}</title>  
  <link rel="icon" href="./images/taws-favicon.png">
  <link rel="stylesheet" type="text/css" href="./desktop.css">
  <link rel="stylesheet" type="text/css" href="./includes/js/jquery-ui/jquery-ui.css">
  
  <script src="./includes/js/jquery-2.1.1.js"></script>  
  <script src="./includes/js/jquery-ui/jquery-ui.js"></script>
  
  <script src="./client_common.js"></script>
  <script src="./client_desktop.js"></script>
</head>
<body> 
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

  
  
  // Display html
  if( $searchquery != '' ){
    //run template
    echo $seachpage;
  }else{    
    echo $intropage;
  }  
  



?>






