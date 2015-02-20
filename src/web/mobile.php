<?php  
  // Design for mobile devices
  require_once './languages/language_selector.php';
  require_once './multisqlwrapper.php';
  
      
  // Get the list of predifined localized strings to build site
  $ls_lang = (new LanguageSelect( "english" ))->getLanguage();
  
  $gs = new GenericSQL;
  $gs->connect();
  
  $type_opt = '';
  $results = $gs->query( "SELECT * FROM Types;" );  
  while( $row = $results->fetchRow() ){	    
    $type_opt .=     
    "<li class='tri-value' data-icon='false' value='0' opt_value=\"{$row[ 'id' ]}\">
       <a href='#'>{$row[ 'type' ]}</a> 
    </li>\n";
  }
  
  $lang_opt = '';
  $results = $gs->query( "SELECT * FROM Languages WHERE id > 0 ORDER BY full_name;" );  
  while( $row = $results->fetchRow() ){	    
    $lang_opt .=    
    "<li class='tri-value' data-icon='false' value='0' opt_value=\"{$row[ 'id' ]}\">
       <a href='#'>{$row[ 'full_name' ]} ( {$row[ 'natural_name' ]} )</a> 
    </li>\n";
  }
  
  $subject_opt = '';
  $results = $gs->query( "SELECT * FROM Subjects;" );  
  while( $row = $results->fetchRow() ){	    
    $subject_opt .=
    "<li class='tri-value' data-icon='false' value='0' opt_value=\"{$row[ 'id' ]}\">
      <a href='#'>{$row[ 'subject' ]}</a> 
    </li>\n";
  }
  
  $gs->disconnect();
  
  
  $html_header = <<<EOT
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0"> 
  
  <meta name="description" content="{$ls_lang['HOMEPAGE_HTML_HEADER_DESCRIPTION']}" />
  <title>{$ls_lang['HOMEPAGE_HTML_HEADER_TITLE']}</title>  
  
  
  <link rel="icon" href="./images/taws-favicon.png"> 
  
  <link rel="stylesheet" href="./includes/js/jquery-ui-mobile/jquery.mobile-1.4.5.css" />
  <link rel="stylesheet" href="./mobile.css" />
  
  <script src="./includes/js/jquery-2.1.1.js"></script>
  <script src="./includes/js/jquery-ui-mobile/jquery.mobile-1.4.5.js"></script>
  <script src="./includes/js/jsSHA-1.5.0/src/sha512.js"></script>  
    
  <script src="./client_common.js"></script>
  <script src="./client_mobile.js"></script>  
  
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
</head>
  
EOT;
  
  // Panel segments
  $advance_search_panel = <<<EOT
    <div data-role="panel" id="navpanel" data-display="push" data-position="left"
    data-position-fixed="true" >    
       <div data-role="collapsibleset" data-inset="false">
         <div data-role="collapsible" data-inset="false">
           <h2>Filter</h2>            
           
           <div data-role="collapsibleset" data-inset="false">
	    <div data-role="collapsible">
	      <h2>Scheme</h2>          
		<ul id="scheme-selectmenu" data-role="listview" data-inset="false">
		  <li class="tri-value" data-icon="false" value="0" opt_value="1">
		    <a href="#">HTTP</a> 
		  </li>
		  <li class="tri-value" data-icon="false" value="0" opt_value="2">
		    <a href="#">HTTPS</a> 
		  </li>    
		</ul> 
	    </div>
	    
	    <div data-role="collapsible">
	      <h2>Domain</h2>          
              <input id="textbox-domain" placeholder="www.example.com">
	    </div> 
	    
	    <div data-role="collapsible">
	      <h2>Language</h2>          
		<ul id="language-selectmenu" data-role="listview" data-inset="false">
		  $lang_opt      
		</ul> 
	    </div> 
	    
	    <div data-role="collapsible">
	      <h2>Types</h2>          
		<ul id="type-selectmenu" data-role="listview" data-inset="false">
		  $type_opt      
		</ul> 
	    </div>  
	    
	    <div data-role="collapsible">
	      <h2>Genre</h2>          
		<ul id="subject-selectmenu" data-role="listview" data-inset="false">
		  $subject_opt
		</ul> 
	    </div>  
	    
	    <div data-role="collapsible">
	      <h2>Published Date Range</h2>          
              <input data-role="date" type="text">
              <input data-role="date" type="text">
	    </div> 
	    
	   </div> 
	   
         </div> 
         
         
         
         <div data-role="collapsible">
           <h2>Group By</h2>    
           
           <fieldset class="ui-field-contain">
	     <legend>Primary</legend>                        
             <select id="pri-group-by">
               <option value="" ></option>
	       <option value="domain_id" selected>Domain</option>
             </select>             
           </fieldset>
           
         </div> 
         
         
         
         <div data-role="collapsible">
           <h2>Order By</h2>
           <form>
             <fieldset class="ui-field-contain">
	       <legend>Primary</legend>                        
               <select id="pri-order-by">
                 <option value="" ></option>
		
		 <option value="frequency DESC" selected>Frequency (Descending)</option>
		 <option value="frequency ASC" >Frequency (Ascending)</option>
		 <option value="published_time DESC" >Published Date (Descending)</option>
		 <option value="published_time ASC" >Published Date (Ascending)</option>
               </select>             
             </fieldset>
             
             <!--
             <fieldset class="ui-field-contain">
	      <legend>Secondary</legend>                        
               <select>
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
    </div>  
EOT;

  $settings_panel_login = "";
/*
  if( !$cookie_authenticated ){
    $settings_panel_login = <<<EOT
    <li data-role="collapsible" data-iconpos="right">
      <h2>Admin Login</h2>          
      <form id="login-form" action="javascript:void(0)">
        <label for="password">Password Input:</label>
        <input id="plan_pass" type="password" name="password" id="password" value="" />    
        <input type="submit" value="authenticate">  
        <label id="auth_status">status: </label>
      </form>
    </li>
EOT;

  }else{
    $settings_panel_login = <<<EOT
    <li data-role="collapsible" data-iconpos="right">
      <h2>Admin tools</h2> 
      <ul data-role="listview" data-inset="true">	
        <li><a id="cmd-init_update" style="color: red;" href="#" >(Re)Create Database</a></li>
	<li><a id="cmd-update" href="#">Update Database</a></li>	
	<li><a id="cmd-logout" href="#">Logout</a></li>
      </ul>
      
    </li>
EOT;
    
  }
*/  


  $settings_panel = <<<EOT
    <div data-role="panel" id="settings-panel" data-display="push" data-position="right">
      <ul data-role="listview" class="ui-listview-outer">
        <li data-icon="delete"><a href="#" data-rel="close">Close</a></li>  
        $settings_panel_login
        
        <!--
        <li data-role="collapsible" data-iconpos="right">
          <h2>Language</h2>          
	  <form>
          <fieldset data-role="controlgroup">
            <legend></legend>
            <input name="radio-choice-v-2" id="radio-choice-v-2a" value="1" type="radio" checked="checked" >
            <label for="radio-choice-v-2a">English</label>
            
            <input name="radio-choice-v-2" id="radio-choice-v-2b" value="2" type="radio">
            <label for="radio-choice-v-2b">Español</label>         
            
            <input name="radio-choice-v-2" id="radio-choice-v-2c" value="2" type="radio">
            <label for="radio-choice-v-2c">达伟</label>
          </fieldset>
        </form>        
	</li>
	-->
        <li data-role="collapsible" data-iconpos="right">
          <h2>Donate</h2>          
	  <ul data-role="listview" data-inset="true">
	    <li><a target="_blank" href="bitcoin:1LLBBL9tdLK4qQs7AZ3eR3q63HZpVXDYvJ">Bitcoins</a></li>
            <li><a target="_blank" href="litecoin:LckqWy236X5vyVQXz1rspxg3kzEcRkmS7U">Litecoin</a></li>	    
	  </ul>
	</li>
      </ul>
    </div>  
EOT;
  
  
  
  // Defualt Home Page
  $home_page = <<<EOT
<!DOCTYPE HTML>
<html lang="{$ls_lang['ISO_CODE']}">
$html_header
<body>   
  <div id="main-page" data-role="page">
    <!--Components-->    
    
    $advance_search_panel
    $settings_panel 
  
    <div data-role="header">
      <a data-icon="bars" class="ui-btn-left" href="#navpanel">Advance Search</a>
      <a data-icon="gear" class="ui-btn-right" href="#settings-panel">Settings</a>
    </div>
    
    <div role="main" class="ui-content" style="text-align:center; margin-top: 50px;">  
      <a  href="/"><img src="./images/taws-logo-48.png"><h1 class="h1-link">Taws (Mobile)</h1></a>     
      
      <form id="search-form" data-ajax="false">
        <input id="search-input" autocomplete="off" data-type="search" placeholder="Ask the Carrot ..." style="display: inline;">
        <ul id="autocomplete" data-role="listview" data-inset="true" data-filter="true" data-input="#search-input"></ul>
        <input type="submit" value="Search">        
      </form>
      
            
      
    </div>
    
    <div data-role="footer" style="text-align:center;"> 
      <p>Created by genaside.net</p>
      <p>Powered by Sphinx</p>
      <a href="//sphinxsearch.com/"><img src="./images/sphinx-logo.png"></a>
      <p>And other open source projects</p>
    </div>
    
  </div>
</body>
</html>
EOT;
  
  $searchquery = "";
  if( isset($_GET['q']) ){
    $searchquery = htmlentities( $_GET['q'] );
  }
    
  $results_page = <<<EOT
<!DOCTYPE HTML>
<html lang="{$ls_lang['ISO_CODE']}">
$html_header
<body>   
  <script>    
   document.title = "$searchquery";
  </script>

  <div id="main-page" data-role="page">
    <!--Components-->    
    
    $advance_search_panel
    $settings_panel 
  
    <div data-role="header">
      <a data-icon="bars" class="ui-btn-left" href="#navpanel">Advance Search</a>
      <a data-icon="gear" class="ui-btn-right" href="#settings-panel">Settings</a>
    </div>
    
    <div role="main" class="ui-content" style="text-align:center; margin-top: 50px;">  
      <h2 style="vertical-align: 10px;display: inline; ">Taws(mobile)</h2>      
      
      <form id="search-form" data-ajax="false">
        <input id="search-input" autocomplete="off" data-type="search" value="$searchquery">
        <ul id="autocomplete" data-role="listview" data-inset="true" data-filter="true" data-input="#search-input"></ul>
        <input type="submit" value="Search">        
      </form>
      
      <div id="search-results-container">     
      </div>
    </div>
    
    <div data-role="footer" style="text-align:center;">       
    </div>
    
  </div>
  
  <script>    
    initialSearch();
  </script>
</body>
</html>
EOT;
  
  
  
  
  // Display html
  if( $searchquery != '' ){
    //run template
    echo $results_page;
  }else{    
    echo $home_page;
  }
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
?>