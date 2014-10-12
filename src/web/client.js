//gloabals
var offset = 0;
var query = '';
var optimizedquery = '';
var advancequery = '';
var mode = 0;

//This will be used in a where cluase for sql
var conditions = { // Remember the values are from Init pre, check index.php
  d: '', //domains
  scheme: -1, //url scheme
  l: '', //language
  t: '' //Type of site
};


//this will help with the sql query builder, NOTE remove this, maybe
var query_status = {
  is_single_word: false,//What does it mean when a user enters a single word
  is_few_words: false,//What if a user puts more then 1 and less then 3 words
  is_question: false,//Is this a question?
  is_exact: false,//Is the string a qoute or an exact phrase
  is_math_operation: false//N/A
};


// INIT-POST stuff todo when the dom finishes
$( document ).ready(function() {    
  //add enter key signal to the input
  $("#search-input").keyup(function( e ){
    if( e.keyCode == 13 ) {
      newSearch();
    }
  });  
  
  // Set phrase completetion
  $( "#search-input" ).autocomplete({
    source: function( request, response ) {      
      $.ajax({
	type: 'POST',
        url: "server.php",
        dataType: "json",
        data: {
          term: request.term,
	  procedure: 'complete_phrase'
        },
        success: function( data ){ response( data ); }
      });
    },    
    minLength: 1,
    delay: 0
  });
  
  // Set up tooltip
  $( document ).tooltip({
    position: {
      my: "left top",
      at: "right+15 top-5"
    }
  });
  
  // NOTE Remove this
  $('#main-menu').menu({
    select: function( event, ui ) {
      if( ui.item.text() == "Update Search Database" ){
	$.post( "middleman.php", { procedure: "update_search_db" })
        .always(function( data ){
          alert( "Data Loaded: " + data );
        });
      }      
    }
  });
  
  //NOTE remove this
  //toggle menu popup
  $('#menubutton').click(function(){
    $('#menuitems').slideToggle();
  });
  
  // Set options that prevoisly came from GET
  $( '#textbox-domain' ).val( conditions.d );    
  $( "input[name=scheme-group][value=" + conditions.scheme + "]" ).prop('checked', true);
  $( "input[name='language-group']" ).each(function(){
    if( conditions.l.indexOf( $( this ).val() ) > -1 ){      
      $( this ).prop( 'checked', true );
    }    
  });
  $( "input[name='type-group']" ).each(function(){
    if( conditions.t.indexOf( $( this ).val() ) > -1 ){      
      $( this ).prop( 'checked', true );
    }    
  }); 
  
  // Set ui of items
  $( "#buttonset-scheme" ).buttonset();
  $( "#button-language" ).button({
    icons: {      
      secondary: "ui-icon-triangle-1-e"
    }
  });
  $( "#button-sitetype" ).button({
    icons: {      
      secondary: "ui-icon-triangle-1-e"
    }
  });   
  $( "#button-reset" ).button();
  $( "#button-top" ).button({
    icons: {      
      primary: "ui-icon-circle-arrow-n",
      secondary: "ui-icon-circle-arrow-n"
    }
  });
  
  //Set button clicks
  //dialog
  $( "#button-language" ).click(function() {
    $( "#select-language-dialog" ).dialog( "open" );
  });
  $( "#button-sitetype" ).click(function() {
    $( "#select-type-dialog" ).dialog( "open" );
  });
  //other
  $( "#button-reset" ).click(function() {
    $( '#textbox-domain' ).val( '' );
    $( "input[name=scheme-group][value=" + -1 + "]" ).prop('checked', true);
    $( "#buttonset-scheme" ).buttonset( 'refresh' );
    $( "input[name='language-group']" ).each(function(){      
	$( this ).prop( 'checked', false );     
    });
    $( "input[name='type-group']" ).each(function(){      
	$( this ).prop( 'checked', false );     
    });
  });
  $( "#button-top" ).click(function(){
    $(window).scrollTop( 0 );
  });
  
  
  // Set the dialogs
  $( "#select-language-dialog" ).dialog({
    autoOpen: false,
    modal: true,
    draggable: false     
  });
  $( "#select-type-dialog" ).dialog({
    autoOpen: false,
    modal: true,
    draggable: false,
    width: 400     
  });
  
  // Set other events
  //Need some way todo infiniteScroll
  $(window).scroll(function() {
    // toggle to-top button
    if( $(window).scrollTop() == 0 ){
      $( "#button-top" ).hide();
    }else{
      $( "#button-top" ).show();
    }
    if($(window).scrollTop() + $(window).height() == $(document).height() ) {     
      infiniteScroll();
    }
  });      
});


/**
 * This begins the start of a new search.
 * This also clears and reset defaults
 */
function initialSearch(){
  //incomming new search lets clean up the old and set default.
  offset = 0;
  
  query = $('#search-input').val();
  if( query.charAt( 0 ) == '/' ){//special mode
    //go into option mode
    if( query.charAt( 1 ) == 'a' ){//advance search
      mode = 1;
    }    
  }  
  
  //clear content
  $( "#search-results-container" ).empty();
  
  //determineSearchType( query );
  //optimizeQuery();
  //extractOptions(  );
  getAndDisplayResults();
}

/**
 * Get the items from the server and display them.
 */
function getAndDisplayResults(){  
  if( query.length < 1 ){
    alert
  }
  
  //a user wants this string search 
  var searchstring = query;  
  if( optimizedquery != "" ){
    searchstring = optimizedquery;    
  }  
  
  //sending string elsewhere to be handled. Futermore, ask to get number of results back  
  $.post( "server.php", { procedure: 'search_and_get_result',
                          ss: searchstring,
	                  conditions: conditions,
                          limit: 16, 
	                  offset: offset, 
	                  mode: mode, 
	                  query_status: JSON.stringify( query_status ) } )
  .done(function( data, textStatus, response ) {  
    //check a spacific header to see if any problems occured
    if( response.getResponseHeader( 'error' )  ){
      alert( "Error: " + response.getResponseHeader('message') + " " +  data );      
    }     
    
    //alert( data );
    //Looks ok, lets continue by displaying results for user
    var na = $.parseJSON( data );
    $.each( na, function( key, value ){
      if( key == "dym" ){
	if( value == '' ){
	  $('#spellfix-container').hide();//TODO might not need
	}else{
	  $('#spellfix').attr( 'href','?q=' + encodeURIComponent( value ) );	  
	  $('#spellfix').text( value );  
	  $('#spellfix-container').show();
	}	
	return true;//same as continue here.
      }else if( key == "resultless" ){	
	if( offset == 0 ){
	  var rn = $( "<p>", { class: "result-none", text: "You look into the void, it looks back."  });
	  $( '#search-results-container' ).append( rn );	
	}
	return true;//same as continue here.
      }
      
      var div = $( "<div>", { class: "result-item" } );
      var pos = $( "<p>", { class: "result-item-posnum", text: key + '.'  });
      
      var scheme = '';
      if( value.page_scheme == 2 ){
	scheme = 'https';
      }else{
	scheme = 'http';
      }
      
      var title = $( "<a>", { 
	class: "result-item-title", 
	text: value.page_title, 
	href: scheme + "://" + value.page_url, 'data-ubm_id': value.id 
      });
      
      var description = $( "<p>", { class: "result-item-description", text: value.page_description + "(...)" });      
      //var http = $( "<a>", { class: "result-item-schema-http", text: "http", href: "http://" + value.domain_name + "/" });
      //var https = $( "<a>", { class: "result-item-schema-https", text: "https", href: "https://" + value.domain_name + "/" });
      
      if( value.domain_schema == 0){
	https.attr("class", "disable");
      }
      if( value.domain_schema == 1){
	http.attr("class", "disable");
      }
            
      var nl = $( "<br>" );      
      var domain = $( "<a>", { class: "result-item-domain", text: value.domain_name, href: scheme + "://" + value.domain_name + "/" });      
      
      div.append( pos );
      div.append( title );  
      div.append( description );  
      //div.append( http );
      //div.append( https );
      div.append( nl );
      div.append( domain );
      
      $( '#search-results-container' ).append( div );      
    }); 
    //alert( "Data Loaded: " + data  );
    offset += 16;
    $( '.result-item-title' ).click(function(){
      //Send this to the local server, so it can handle it.       
      $.post( "server.php", { procedure: "store_user_query_and_result", 
	                      query: query, 
	                      result_id:$( this ).attr( 'data-ubm_id' ) } );
      return false;
    });
  });
}

/**
 * Send the string to the server so it will determind weather
 * to optimize or not. Sever will return the optimized string.
 * Or empty string if the option is disable.
 */
function optimizeQuery(){
  $.ajax({
    async: false,
    data: { optimize: query },
    success: function( data ){ optimizedquery = data; },
    type: 'POST',
    url: "server.php"
  });
}


/**
 * Parse string to figure out what the user is asking.
 * This will help build a better sql query.
 */
function determineSearchType( str ){
  if( str.split(' ').length == 1 ){
    query_status.is_single_word = true;
  }
}



/**
 * Prepare for a new search by setting the GET so that 
 * the reloaded page knows what to do.
 */                
function newSearch(){  
  // NOTE Only set get values that is not the defualt 
  var text = $('#search-input').val();  
  
  var d = '';
  if( ( d = $( '#textbox-domain' ).val() ) == null || d == '' ){
     d = '';   
  }else{
    d = '&d=' + encodeURIComponent( d );
  }
  
  var scheme = '';  
  if( ( scheme = $( "input[name=scheme-group]:checked" ).val() ) == null || scheme == -1 ){
    scheme = '';   
  }else{
    scheme = '&scheme=' + scheme;
  }    
  
  var lang = [];
  $( "input[name='language-group']:checked" ).each(function(){    
    lang.push( parseInt( $( this ).val() ) );
  });  
  if( lang.length > 0 ){
    lang = '&lang=' + encodeURIComponent( lang.join( ',' ) );
  }else{
    lang = '';
  }
  
  // Handle the site types
  var type = [];
  $( "input[name='type-group']:checked" ).each(function(){    
    type.push( parseInt( $( this ).val() ) );
  });
  if( type.length > 0 ){
    type = '&sitetype=' + encodeURIComponent( type.join( ',' ) );
  }else{
    type = '';
  }

    
  text = text.replace( /\s+/g, ' ' ); 
  if( text == '' || text == ' ' ){
    //You've enter nothing
    return;
  }
  if( text != ''){
    document.location.href = '/?q=' + encodeURIComponent( text ) + d + scheme + lang + type;
  }  
}    

/**
 * When scrolled to the bottom get more results
 */
function infiniteScroll(){
  getAndDisplayResults();
}
                 

                 
//Function to communicate with middleman

function getWordDB(){
  //ok the user wants a new database for spell correct and word completion
  $.post( "middleman.php", { procedure: "get_word_db" })
  .done(function( data ) {
    alert( "Data Loaded: " + data );
  });
}
                 
                 
                 
                 
                 
                 
                 
                 