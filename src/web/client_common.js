// Here is a file that contains common varibles

var query = ''; // The string to search  
var offset = 0; // Where to start in the search
var limit = 24; // The number of results to return. TODO this should be changed by a user action.

// A mamping of of conditions that will be used in the sql
var conditions = { // Remember the values are asigned from from a php script
  domain: '', // domains
  scheme: '', // url scheme  
  language: '', // language
  type: '', // Site type
  subject: '', // Site Genre
  time_from: '', // time from
  time_to: '', // time to
  group: '', // group by
  order: '', // order by
  // Files
  file_type: '', // If this is a file type search then what ext to search for 
  // other
  operation: 0 // What type of search to perform(regualar, file,rss, ...) 
};

// magic number resolution
var OPERATION_DEFAULT_SEARCH = 0;
var OPERATION_RSS_SEARCH = 1;
var OPERATION_FILE_SEARCH = 2;

// Other const- like varibles
var COOKIE_TAWS_NAME = "taws_access";

// unique ajax async flags
var unique_async_flags = {
  search_results_loading: false // piling results should wait
}




/**
 * If the password matches, set it to a cookie and refresh page
 * @param plain_pass A plan password
 */
function authenticateAdmin( plain_pass ){   
  $.ajax({
    async: false,
    data:{ 
      procedure: 'authenticate_admin',
      plain_pass: plain_pass
    },
    success: function( data ){
      // If pass is correct data will be true          
      if( data ){ 	
	location.reload();
      }
    },
    type: 'POST',
    dataType: 'json',
    url: "server.php"
  });  
  return false;
}


/**
 * Logout by clearing cookies and refresh page
 */
function logoutAdmin(){
  $.ajax({
    async: false,
    data:{ 
      procedure: 'admin_logout'
    },
    success: function(){    
      location.reload();      
    },
    type: 'POST',
    url: "server.php"
  });  
}

/**
 * Make the database up to date
 */
function updateDatabase(){
  $.post( "server.php", { procedure: "update" } );   
  setTimeout(function(){ location.reload(); }, 500);   
}


























