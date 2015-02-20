//$( document ).on("pagecontainerload", function() {
//$( document ).ready(function() {  
$( document ).on( "pageinit", "#main-page", function( event ) {
  
  // Set uo  Auto complete ( phrase complete )
  // TODO this code feels slower then the desktop version, take a look into it
  /*
  $( "#autocomplete" ).on( "filterablebeforefilter", function ( e, data ) {
    var $ul = $( this ), $input = $( data.input ), value = $input.val(), html = "";
    $ul.html( "" );
    
    if ( value && value.length > 1 ) {
      $ul.html( "<li><div class='ui-loader'><span class='ui-icon ui-icon-loading'></span></div></li>" );
      $ul.listview( "refresh" );
      $.ajax({
	type: 'POST',
	url: "server.php",
	dataType: "json",
	data: {
	  term: $input.val(),
	  procedure: 'complete_phrase'
	}
      })
      .then( function ( response ) {
	$.each( response, function ( i, val ) {
	    html += "<li>" + val + "</li>";
	});
	$ul.html( html );
	$ul.listview( "refresh" );
	$ul.trigger( "updatelayout");
      });
    }
  });
  $('#autocomplete').on( 'click', 'li', function(){
    var selectedItem = $(this).html();
    $(this).parent().parent().find('input').val(selectedItem);   
    $('#autocomplete').hide();
  });  
  */    
  //$('#search-form').submit(function( event ){  
  $(document).on("submit", '#search-form', function( event ){    
    newSearch();
    event.preventDefault();
  });
  
  $(document).on("submit", '#login-form', function( event ){    
    var plain_pass = $( '#plan_pass' ).val();
    $.ajax({
      async: false,
      data:{ 
	procedure: 'authenticate_password',
	plain_pass: plain_pass
      },
      success: function( data ){
	
	if( data == "" ){  	  
	  $( '#auth_status' ).text( "Status: authentication failed");	  
	}else{
	  $( '#auth_status' ).text( "Status: authentication sucessfull");
	  document.cookie = "taws_access=" + plain_pass;
	  location.reload();
	}
      },
      type: 'POST',
      url: "server.php"
    }); 
    
  });
  
  $(document).on('click', '#cmd-update',  function(){
   $.post( "middleman.php", { procedure: "update" } );   
   setTimeout(function(){ location.reload(); }, 500);   
  });
  
  $(document).on('click', '#cmd-init_update',  function(){
   $.post( "middleman.php", { procedure: "first_update" } );   
   setTimeout(function(){ location.reload(); }, 500);   
  });
  
  $(document).on('click', '#cmd-logout',  function(){
   document.cookie = "taws_access=;expires=Thu, 01 Jan 1970 00:00:01 GMT";
   setTimeout(function(){ location.reload(); }, 1);   
  });
  
  
  $(document).on('click', '.tri-value',  function() {      
    // Change value to the next available value.     
    var val_res = $(this).attr( 'value' );   
    
    var norm_in_ex = 0; // normal and/or inclusive and/or exclusive
             
    // Run threw the sibling items to see if any option are toggled in/ex
    $(this).siblings().each(function(){
      var val = $( this ).attr( 'value' );
      if( val == 1 ){
	norm_in_ex = 1;
	return;
      }else if( val == 2 ){
	norm_in_ex = 2;
	return;
      }
    });
     
    // Set the 2 or three available options the user can take, based on what he selected before          
    if( ++val_res > 2 ){
      val_res = 0;    
    }
    
    // Get the next available value
    if( norm_in_ex == 1 && val_res == 2 ){
      val_res = 0;
    }else if( norm_in_ex == 2 && val_res == 1 ){
      val_res = 2
    }    
    
    $(this).attr( 'value', val_res  );     
    
    // Change the icon of element
    var element = $(this).children("a:first");     
    switch( val_res ) {
     case 0:
        element.attr("class",'ui-btn');
        break;
     case 1:        
        element.attr("class",'ui-btn ui-btn-icon-right ui-icon-check');
        break;
     case 2:
        element.attr("class",'ui-btn ui-btn-icon-right ui-icon-delete');
    }    
  });
  
  //########## Reset varibles ######
  
  $( '#textbox-domain' ).val( conditions.domain );
  $( '#pri-group-by' ).val( conditions.group );
  $( '#pri-order-by' ).val( conditions.order );
  
  /**
   * Anom funtion to set html elements (select menu) 
   * based on saved variables.
   * @param menu_id id selector
   * @param opt_ptr selected values in string form(ex. 1,2,3,4 )
   */
  var parseMenu = function( menu_id, opt_ptr ){
    if( opt_ptr != '' ){
      // String list to array
      var temp_arr = opt_ptr.split( ',' );    
      $( menu_id + " > li" ).each(function(){   
	// Does the opt value of this element match any in the created array
	if( $.inArray( $( this ).attr( 'opt_value' ), temp_arr ) != -1 ){
	  if( temp_arr[ 0 ] == 'i' ){
	    $( this ).attr( 'value', '1' );
	    $( this ).children("a:first").attr( 'class', 'ui-btn ui-btn-icon-right ui-icon-check' );
	  }else{
	    $( this ).attr( 'value', '2' );
	    $( this ).children("a:first").attr( 'class', "ui-btn ui-btn-icon-right ui-icon-delete" );
	  }
	}
      });
    }   
  };
  parseMenu( "#scheme-selectmenu", conditions.scheme );
  parseMenu( "#language-selectmenu", conditions.language );
  parseMenu( "#type-selectmenu", conditions.type );
  parseMenu( "#subject-selectmenu", conditions.subject );
  //parseMenu( "#filetype-selectmenu", conditions.file_type );
  
  
});

// Includes, common variables 
//$.getScript( "genaside.net:4132/client_common.js" );

function initialSearch(){
  //incomming new search lets clean up the old and set default.
  //offset = 0; 
  
  //clear content
  //$( "#search-results-container" ).empty();
  
  
  query = $('#search-input').val();  
  getAndDisplayResults();
}

function getAndDisplayResults(){      
  
  //sending string elsewhere to be handled. Futermore, ask to get number of results back  
  $.post( "server.php", { procedure: 'search_and_get_result',
                          ss: query,
	                  conditions: conditions,
                          limit: limit, 
	                  offset: offset } )
  .done(function( data, textStatus, response ) {  
    //check a spacific header to see if any problems occured
    if( response.getResponseHeader( 'error' )  ){
      alert( "Error: " + response.getResponseHeader('message') + " " +  data );      
    }     
    
    //Looks ok, lets continue by displaying results for the user
    var na = $.parseJSON( data );
    $.each( na, function( key, value ){
      if( key == "resultless" ){	
	if( offset == 0 ){
	  var rn = $( "<p>", { class: "result-none", text: "You look into the void, it looks back."  });
	  $( '#search-results-container' ).append( rn );	
	}
	return true;//same as continue here.
      }
      
      var div = $( 
        "<div>", 
	{ class: "result-item" } 
      ); // container
      
      var pos = $( 
        "<p>", 
	{ class: "result-item-posnum", text: key + '.'  }
      ); // The number that shows the position in the results
            
      var title = $( "<a>", { 
	class: "result-item-title", 
	text: value.page_title, 
	href: value.page_url, 
	'data-ubm_id': value.id 
      }); // The title of the page
      
      var description = $( 
        "<p>", { 
	  class: "result-item-description", 
	  text: value.page_description + "(...)"	  
	}
      ); // The description of the page      
      
         
      //var nl = $( "<br>" );      
      var domain = $( "<a>", { 
	class: "result-item-domain", 
	text: value.domain_name, 
	href: "//" + value.domain_name + "/" 	
      });
      if( value.domain_description != null ){
	domain.attr( "title", value.domain_description );
      }      
      
      div.append( pos );
      div.append( title );  
      div.append( description );  
      //div.append( http );
      //div.append( https );
      
      // Setting indacators
      
      // Check if domain has mobil support
      if( value.hasmobilesupport != null ){
	var tag = $( "<img>", {
	    class: "result-item-meta-image",
	    src: "images/indicator-mobile.png"
	  } 
	);
	
	if( value.hasmobilesupport == 0 ){
	  tag.attr( "title", "Supports mobile devices via separate domain" );
	}else{
	  tag.attr( "title", "Supports mobile devices" );
	}	
	div.append( tag );
      }
      
      // Check if and set pub date
      if( value.published_time != null ){
	var date = new Date( value.published_time * 1000 );	
	var tag = $( 
	  "<p>", { 
	    class: "result-item-meta",
	    text: "["+ date.toDateString() +"]", 	  
	    title: "Published Date"
	  }
	);	
	div.append( tag );
      }
      
      
      
      div.append( $( "<br>" ) );
      div.append( domain );
      
      $( '#search-results-container' ).append( div );      
    });    
    // Increase offset for new results
    offset += 16;    
  });
}

function newSearch(){  
  var search_string = $('#search-input').val().replace( /\s+/g, ' ' ); 
  
  var parseMenu = function( menu_id ){
    var array = [];
    $( menu_id + " > li" ).each(function( index ){
      var val = $( this ).attr( 'value' ); 
      
      if( val > 0 ){
	// Try to get the very first status to determine opertion(exclusive or inclusive)
	if( array.length == 0 ){
	  if( val == 1 ){
	    array.push( 'i' );
	  }else{
	    array.push( 'e' );
	  }
	}
	// Push the opt values id
	array.push( $( this ).attr( 'opt_value' ) );      
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
  
  var domain = '';
  if( $( '#textbox-domain' ).val() ){    
     domain = '&domain=' + encodeURIComponent( $( '#textbox-domain' ).val() );     
  }
  
  var group_by = '';
  if( $( "#pri-group-by" ).val() ){    
    group_by = '&group_by=' + encodeURIComponent( $( "#pri-group-by" ).val() );
  } 
  
  var order_by = '';
  if( $( "#pri-order-by" ).val() ){      
    order_by = '&order_by=' + encodeURIComponent( $( "#pri-order-by" ).val() );
  }
  
  
  if( search_string == '' || search_string == ' ' ){    
    return;
  }else{        
    document.location.href = '/?q=' + encodeURIComponent( search_string ) +
    scheme + domain + lang + type + subject +
    group_by + order_by;    
  }    
}























