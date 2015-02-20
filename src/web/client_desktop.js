

// INIT-POST stuff todo when the dom finishes
$( document ).ready(function() {    
  //add enter key signal to the search input
  $("#search-input").keyup(function( e ){
    if( e.keyCode == 13 ) {
      newSearch();
    }
  });  
  
  // Set phrase/word completetion fot the search input
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
  
  // Set up tooltips
  $( document ).tooltip({
    //position: { my: "left top", at: "right+15 top-5" },
    track: true
  });  
   
  // Set options that prevoisly came from GET/POST  
  $( '#textbox-domain' ).val( conditions.domain );
  $( '#time_to' ).val( conditions.time_to );
  $( '#time_from' ).val( conditions.time_from );
  $( '#pri-group-by' ).val( conditions.group );
  $( '#pri-order-by' ).val( conditions.order );
  //alert( conditions.time_to +" "+ conditions.time_from );
  
  // Anon function to reduce code repitition
  var parseMenu = function( menu_id, opt_ptr ){
    if( opt_ptr != '' ){
      var temp_arr = opt_ptr.split( ',' );    
      $( menu_id + " > li > div" ).each(function(){    
	if( $.inArray( $( this ).children( "div" ).attr( 'value' ), temp_arr ) != -1 ){
	  if( temp_arr[ 0 ] == 'i' ){
	    $( this ).attr( 'value', '1' );
	    $( this ).children( "span" ).attr( 'class', 'ui-icon ui-icon-check' );
	  }else{
	    $( this ).attr( 'value', '2' );
	    $( this ).children( "span" ).attr( 'class', "ui-icon ui-icon-close" );
	  }
	}
      });
    }   
  };
  parseMenu( "#scheme-selectmenu", conditions.scheme );
  parseMenu( "#language-selectmenu", conditions.language );
  parseMenu( "#type-selectmenu", conditions.type );
  parseMenu( "#subject-selectmenu", conditions.subject );
  parseMenu( "#filetype-selectmenu", conditions.file_type );

  
  // Set ui of items
  $( "#tabs" ).tabs({ 
    heightStyle: "fill",
    active: conditions.operation,
    activate: function( event, ui ){
      ui.newPanel.accordion( "refresh" );      
    }
  });
  
  $( "#filesize-slider-range" ).slider({
    range: true,
    min: 1,
    max: 1024,
    values: [ 1, 1024 ],
    slide: function( event, ui ) {
      var unit = $("#filesizeunit-menu option:selected").text().substring( 0, 3 );
      
      $( "#amount" ).val( 
        ui.values[ 0 ] +  unit +  
	ui.values[ 1 ] + unit
      );
    }
  });
  
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
  $( "#button-donate1" ).button();
  $( "#button-donate2" ).button();
  
  $( "#button-settings" ).button({
    icons: {
      primary: "ui-icon-gear",
      secondary: "ui-icon-triangle-1-s"
    },
    text: true,
    create: function( event, ui ){ 
      $( this ).click(function(){
        $( "#settings-selectmenu" ).toggle( 'slide', { direction: "up" }, 300 );
      });    
      $( this ).blur(function(){
        $( "#settings-selectmenu" ).hide( 'slide', { direction: "up" }, 300 );
      }); 
    }
  });  
  
   
  //Set button clicks
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
    draggable: false,
    width: 310 
  });
  $( "#select-type-dialog" ).dialog({
    autoOpen: false,
    modal: true,
    draggable: false
  });
  $( "#donate-bitcoin-dialog" ).dialog({
    autoOpen: false,
    modal: true,
    draggable: false,
    width: 400
  });
  $( "#donate-litecoin-dialog" ).dialog({
    autoOpen: false,
    modal: true,
    draggable: false
  });
  
  $( "#login-dialog" ).dialog({
    autoOpen: false
  });
  $( "#about-dialog" ).dialog({
    autoOpen: false
  });
  
  // Set other events
  // Need some way todo infiniteScroll
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

  $( "#pri-group-by" ).selectmenu();
  $( "#pri-order-by" ).selectmenu();
  $( "#sec-order-by" ).selectmenu();
  $( "#filesizeunit-menu" ).selectmenu({
    select: function( event, ui ) {       
      var unit = ui.item.label.substring( 0, 3 );
      
      var values = $( "#filesize-slider-range" ).slider( "option", "values" );      
      $( "#amount" ).val( 
        values[ 0 ] + unit +  
	values[ 1 ] + unit
      );      
    }
  });


  $(".accordion").accordion({
    collapsible: true,
    heightStyle: "fill",    
    icons: {
      header: "ui-icon-circle-arrow-e",
      activeHeader: "ui-icon-circle-arrow-s"
    }
  });



  $("#settings-selectmenu").menu({   
    //position: { my: "left", at: "left", of: "#button-settings" },
    create: function( event, ui ){ 
      $( this ).position({
        my: "left-40",
        at: "top+40",
        of: "#button-settings"
      });          
    },
    open: function( event, ui ) {
      // reset the input
    },    
    select: function( event, ui ){
      var item_id = ui.item.attr( 'id' );      
      
      switch( item_id ){
	case "cmd_settings_display_rss":
	  newSearch( 'rss' );
	  break;
	case "cmd_settings_about":
	  $( "#about-dialog" ).dialog( "open" );	  
	  break;
	case "cmd_settings_logout":
	  logoutAdmin();
	  break;
	case "cmd_settings_login":
	  $( "#login-dialog" ).dialog( "open" );	  
	  break;
	case "cmd_settings_update_db":
	  updateDatabase();  
	  break;	
      }      
    }    
  });
  
  // TODO not sure if i should have this
  $( "#login-form" ).submit(function( event ) {
    var password = $( this ).find( 'input[type=password]' ).val();
    
    var success = authenticateAdmin( password );    
    if( !success ){
      //$( this ).find( 'p' ).text( "Status: incorrect password." );
    }    
    event.preventDefault();
  });
  
  
  
  $(".menu").menu({
    items: "> :not(.ui-widget-header)",
    select: function( event, ui ){
      var menu = ui.item.parent(); // The menu repsented as a <ul> list
      var div1 = ui.item.children( 'div' ); // three value div
      var span = div1.children( 'span' ); // span that holds the icon
      var div2 = div1.children( 'div' ); // option div     
      
      var norm_in_ex = 0;
            
      // Run threw the menu item to see if any option are toggled in/ex
      menu.children().each(function(){
	var val = $( this ).children().attr( 'value' );	
	
	if( div2.html() != $( this ).children( 'div' ).children("div").html()  ){
	  if( val == 1 ){
	    norm_in_ex = 1;
	    return;
	  }else if( val == 2 ){
	    norm_in_ex = 2;
	    return;
	  }	  
	}
      });
      
      // Set the 2 or three available options the user can take, based on what he selected before       
      var val = div1.attr( 'value' );
      
      if( ++val > 2 ){
        val = 0;    
      }
      
      if( norm_in_ex == 1 && val == 2 ){
	val = 0;
      }else if( norm_in_ex == 2 && val == 1 ){
	val = 2
      }
      
      div1.attr( 'value', val );
      switch( val ) {
	case 0:
	    span.attr( 'class', "ui-icon ui-icon-blank" );
	    break;
	case 1:
	    span.attr( 'class', "ui-icon ui-icon-check" );        
	    break;
	case 2:
	    span.attr( 'class', "ui-icon ui-icon-close" );
      }
    }
  });
    
  $( "#time_from" ).datepicker({ 
    defaultDate: "+1w",      
    changeMonth: true,      
    numberOfMonths: 1,      
    onClose: function( selectedDate ) {        
        $( "#time_to" ).datepicker( "option", "minDate", selectedDate );      
    }    
  });    
  $( "#time_to" ).datepicker({      
    defaultDate: "+1w",      
    changeMonth: true,      
    numberOfMonths: 1,      
    onClose: function( selectedDate ) {        
        $( "#time_from" ).datepicker( "option", "maxDate", selectedDate );      
    }    
  });
  
  
  // Something for popularity mining, but not sure this is good
  /*
  $( document ).on( 'mousedown', ".result-item-title", function(){    
    //Send this to the local server, so it can handle it.       
    $.post( "server.php", { 
      procedure: "store_user_query_and_result", 
      query: query, 
      result_id: $( this ).attr( 'data-ubm_id' ) 	  
    });
    return true;
  });
  */
   
});








/**
 * This begins the start of a new search.
 * This also clears and reset defaults
 */
function initialSearch(){
  //incomming new search lets clean up the old and set default.
  query = $('#search-input').val();  
  
  //clear content
  $( "#search-results-container" ).empty(); 
  
  getAndDisplayResults();  
}

/**
 * Get the items from the server and display them.
 */
function getAndDisplayResults(){  
  if( query.length < 1 ){
    // Empty query 
    return;
  }
  
  // A user wants this string search 
  var searchstring = query;    
  
  var buildItems = function( data ){
    $.each( data, function( key, value ){	
      // The main container
      var div = $( "<div>", { class: "result-item" } );
      
      var pos = $( "<p>", { 
	class: "result-item-posnum",
	text: key + '.'
      });
      
      var title = $( "<a>", { 
	class: "result-item-title", 
	text: value.page_title, 
	href: value.page_url, 
	'data-ubm_id': value.id // TODO: remove this
      });
      
      //title.on( 'mousedown', function(){ alert("here"); });
      
      var description = $( "<p>", { 
	class: "result-item-description", 
	text: value.page_description + "(...)" 	
      });
      
      var domain = $( "<a>", { 
	class: "result-item-domain", 
	text: value.domain_name, 
	href: "//" + value.domain_name
      });
      
      
      // container for meta infomation like time stamp
      var div_meta = $( "<div>", { class: "result-item-meta" } );
      
      // Check and build the meta div
      if( value.published_time != null ){
	var date = new Date( value.published_time * 1000 );	
	var tag = $( "<p>", { 
	  class: "result-item-meta-item",
	  text: "Posted On: "+ date.toDateString()
	});	
	div_meta.append( tag );
      }
	      
      // Build the main div
      div.append( pos );
      div.append( title );  
      div.append( description );       
      div.append( div_meta );      
      div.append( domain );
      
      // Finally add the item to the page
      $( '#search-results-container' ).append( div );
    });
    // Increase offset when done
    offset += limit;
  };
  
  
  var build_items_for_file_search = function( data ){    
    $.each( data, function( key, value ){
      // The main container
      var div = $( "<div>", { class: "result-item" } );
      
      var pos = $( "<p>", { 
	class: "result-item-posnum",
	text: key + '.'
      });      
      
      var title = $( "<a>", { 
	class: "result-item-title", 
	text: value.filename, 
	href: "//" + value.page_url
      });
      var description = $( "<p>", { 
	class: "result-item-description", 
	text: value.description 
      });
      
      // container for meta infomation like time stamp
      var div_meta = $( "<div>", { class: "result-item-meta" } );    
            
      var sizes = [ 'Bytes', 'KB', 'MB', 'GB', 'TB' ];      
      var i = parseInt( Math.floor( Math.log( value.filesize ) / Math.log( 1024 ) ) );
      var fs = Math.round( value.filesize / Math.pow( 1024, i ), 2 ) + ' ' + sizes[ i ];
      var tag = $( "<p>", {
	class: "result-item-meta-item",
	text: "FileSize: " + fs + " ( "+ value.filesize +" BYTES )" 	    
      });
      div_meta.append( tag );
      
      // Last mod
      var tag = $( "<p>", {
	class: "result-item-meta-item",
	text: "Last Modified: "+ value.last_modified    
      });
      div_meta.append( tag );
      
      
      div.append( pos );
      div.append( title );
      div.append( description );  
      div.append( div_meta );
      $( '#search-results-container' ).append( div );
    });
    // Increase offset when done
    offset += limit;    
  };
  
  
  var build_items_for_rss_search = function( data ){    
    $.each( data, function( key, value ){
      // The main container
      var div = $( "<div>", { class: "result-item" } );
      
      var pos = $( "<p>", { 
	class: "result-item-posnum",
	text: key + '.'
      });
      
      var title = $( "<a>", { 
	class: "result-item-title", 
	text: value.page_title, 
	href: "//" + value.page_url
      });
      var description = $( "<p>", { 
	class: "result-item-description", 
	text: value.page_description + "(...)" 	
      });
      
      div.append( pos );
      div.append( title );
      div.append( description );        
      $( '#search-results-container' ).append( div );
    });
    // Increase offset when done
    offset += limit;    
  };
  
  
  // NOTE i dont think i need this, need to test more.
  // Something to prevent two or more instance running at the same time
  if( unique_async_flags.search_results_loading ){
    // A instance of this is already running     
    return;
  }
  
  // Ok, it's going to it's stuff
  unique_async_flags.search_results_loading = true;
  $( '#loading-alert' ).show();
  
  $.ajax({
    type: 'POST',
    url: "server.php",
    data: {
      procedure: 'search_and_get_result',
      ss: searchstring,
      conditions: conditions,
      limit: limit, 
      offset: offset
    },    
    dataType: 'json',
    async: true,
    complete: function(){
      // Loading is done change load flag
      unique_async_flags.search_results_loading = false;
      $( '#loading-alert' ).hide();
    },
    success: function( data, textStatus, response ){   
      // Show things the user want to know about what went wrong
      if( response.getResponseHeader( 'error' ) ){
	if( offset == 0 ){
	  var rn = $( "<p>", { 
	    class: "result-none", 
	    text: response.getResponseHeader( 'error' )
	  });
	  $( '#search-results-container' ).append( rn );
	  
	}	        
	return;
      }
      
      // Build the search items depending on the type
      if( conditions.operation == OPERATION_DEFAULT_SEARCH ){
	buildItems( data );
      }else if( conditions.operation == OPERATION_FILE_SEARCH ){
	var rn = $( "<p>", { 
	  class: "result-none", 
	  text: "Beware, files are uploaded be users and may contain harmful contents."
	});
	$( '#search-results-container' ).append( rn );
	
	build_items_for_file_search( data );
      }else if( conditions.operation == OPERATION_RSS_SEARCH ){
	build_items_for_rss_search( data );
      }
      
    }
  });
}

/**
 * Send the string to the server so it will determind whether
 * to optimize or not. Sever will return the optimized string.
 * Or empty string if the option is disable.
 */
function optimizeQuery(){  
}


/**
 * Parse string to figure out what the user is asking.
 * This will help build a better sql query.
 */
function determineSearchType( str ){  
}



/**
 * Prepare for a new search by setting the GET so that 
 * the reloaded page knows what to do.
 */                
function newSearch( display_mode ){  
  if( typeof( display_mode ) === 'undefined' ) display_mode = null;
  
  // NOTE Only set get values that is not the defualt   
  
  var text = $('#search-input').val();  
  
  // Build input text 
  var domain = '';
  if( $( '#textbox-domain' ).val() ){    
     domain = '&domain=' + encodeURIComponent( $( '#textbox-domain' ).val() );     
  }  
  var time_to = '';
  if( $( '#time_to' ).val() ){
     time_to = '&time_to=' + encodeURIComponent( $( '#time_to' ).val() );     
  }
  var time_from = '';
  if( $( '#time_from' ).val() ){
     time_from = '&time_from=' + encodeURIComponent( $( '#time_from' ).val() );     
  }
  
  // anon funtion to reduce code
  var parseMenu = function( menu_id ){
    var array = [];
    $( menu_id + " > li > div" ).each(function( index ){
      var val = $( this ).attr( 'value' ); 
      
      if( val > 0 ){
	if( array.length == 0 ){
	  if( val == 1 ){
	    array.push( 'i' );
	  }else{
	    array.push( 'e' );
	  }
	}
	// Push the values id
	array.push( $( this ).children( "div" ).attr( 'value' ) );      
      }    
    });  
    array = array.join( ',' );    
    return array;
  };  
  
  var scheme = parseMenu( "#scheme-selectmenu" );
  if( scheme != '' ){
    scheme = '&scheme=' + encodeURIComponent( scheme );
  }
  
  var lang = parseMenu( "#language-selectmenu" );
  if( lang != '' ){
    lang = '&lang=' + encodeURIComponent( lang );
  }
  
  var type = parseMenu( "#type-selectmenu" );
  if( type != '' ){
    type = '&type=' + encodeURIComponent( type );    
  }
  
  var subject = parseMenu( "#subject-selectmenu" );
  if( subject != '' ){
    subject = '&subject=' + encodeURIComponent( subject );    
  }
  
  var ft = parseMenu( "#filetype-selectmenu" );
  if( ft != '' ){
    ft = '&ft=' + encodeURIComponent( ft );    
  }

  // Build combobox     
  var group_by = '';
  if( $( "#pri-group-by" ).val() ){    
    group_by = '&group_by=' + encodeURIComponent( $( "#pri-group-by" ).val() );
  } 
  
  var order_by = '';
  if( $( "#pri-order-by" ).val() ){      
    order_by = '&order_by=' + encodeURIComponent( $( "#pri-order-by" ).val() );
  }
  
  var active = '';
  if( $( "#tabs" ).tabs( "option", "active" ) >= 0 ){
    var active = "&op=" + $( "#tabs" ).tabs( "option", "active" );
  }
    
    
  text = text.replace( /\s+/g, ' ' ); 
  if( text == '' || text == ' ' ){
    //You've enter nothing
    return;
  }
  
  if( display_mode == 'rss' ){
    display_mode = '&format=rss';
  }else{
    display_mode = '';
  }
  
  if( text != ''){
    document.location.href = '/?q=' + encodeURIComponent( text ) + 
    active +
    domain + scheme +  lang + type + subject + time_from + time_to +
    group_by + order_by +
    ft +
    display_mode;
  }  
}    

/**
 * When scrolled to the bottom get more results
 */
function infiniteScroll(){
  getAndDisplayResults();
}     