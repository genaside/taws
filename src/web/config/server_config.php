<?php
/*
Setting for the sever.php and middleman to use
*/

$taws_server_config = array();

//////////////////
// User web login
//////////////////

$taws_server_config[ 'user_name' ] = 'administrator'; // Don't need this since only one user acount exist
$taws_server_config[ 'user_password' ] = 'pass';

///////////////
// General SQL
///////////////

// What sql program will you like to use? mysql, postgresql, sqlite
$taws_server_config[ 'sql_program' ] = 'mysql';
//$taws_server_config[ 'sql_program' ] = 'postgresql'; Not supported
//$taws_server_config[ 'sql_program' ] = 'sqlite'; Not supported

//////////////////
// Mysql Options
////////////////

// The server/host name or ip
$taws_server_config[ 'mysql_server' ] = 'localhost';

// Name of database
$taws_server_config[ 'mysql_db_name' ] = 'taws_db';

// User name for database
$taws_server_config[ 'mysql_db_user' ] = 'taws';

// Password for database
$taws_server_config[ 'mysql_db_pass' ] = '';

// Port of server
$taws_server_config[ 'mysql_db_port' ] = 3306;


///////////////////////
// PostgreSQL otions //
///////////////////////

//location of the sqlite file
$taws_server_config[ 'postgresql_server' ] = 'localhost';

// Name of database
$taws_server_config[ 'postgresql_db_name' ] = 'taws_db';

// User name for database
$taws_server_config[ 'postgresql_db_user' ] = 'taws';

// Password for database
$taws_server_config[ 'postgresql_db_pass' ] = '';

// Port of server
$taws_server_config[ 'postgresql_db_port' ] = 5432;


//////////////////
// SQLite otions
//////////////////

// location of the sqlite db file
$taws_server_config[ 'sqlite_db_path' ] = '';

//////////////////
// Sphinx otions
//////////////////
//NOTE if anyone knows how to get  sphinx working sqlite please help.

// The server/host name or ip
$taws_server_config[ 'sphinx_server' ] = 'localhost';

// The ports
$taws_server_config[ 'sphinx_port' ] = 9312;

//
$taws_server_config[ 'sphinx_ql_port' ] = 9306;

////////////////////
// Data Collection
////////////////////
// NOTE here are tools that will help taws learn, it will require you to send infomation to the remote server.

// Enable storing of search queries and selected results maps  
// For example, when a user makes a search for 'santa cluase' then selects a result, it'll be stored as a pair
$taws_server_config[ 'enable_dc_ubm' ] = false;


//////////////////////
// Other 
//////////////////////
/*
  Description:
    Option to tell the logger what stuff to log
  Possible Values: bool
    true, false 
*/
$taws_server_config[ 'log_level' ] = 7;

// You can get updates from more then one place, but for now it's just one
/*
  Description:
    option to tell the updater where to get its updates.    
  Possible Values: string
    https://www.genaside.net/taws/
  Notes:
    This only works for the regular update and not the initial
*/
$taws_server_config[ 'source_remote_url' ] = "https://www.genaside.net/taws/update.php";

/*
  Description:
    Currently the database dumps are seperated by language.
    Populate the the database based on the language you want
  Possible Values: string in array
    ['english'], ['spanish'], ['german'], ['french'], ['spanish'], ['russian'], ['chinese'], ['arabic']
  Notes:
    The language(s) you want must be in the array
  Example:
    $taws_server_config[ 'sql_update_language' ] = [ 'english', 'spanish', 'german', 'french', 'spanish', 'russian', 'chinese', 'arabic' ];
    $taws_server_config[ 'sql_update_language' ] = [ 'spanish' ];
    $taws_server_config[ 'sql_update_language' ] = [ 'english' ];
  
*/
//$taws_server_config[ 'sql_update_language' ] = [ 'arabic', 'chinese', 'english', 'french', 'german', 'japanese', 'polish', 'russian', 'spanish', 'russian' ];
$taws_server_config[ 'sql_update_language' ] = [ 'english' ];

/*
  Description:
    option to tell the updater if RSS data needs to be updated too.
  Possible Values: bool
    true, false 
*/
$taws_server_config[ 'enable_rss_table_update' ] = false;

/*
  Description:
    option to tell the updater if file data needs to be updated too.
  Possible Values: bool
    true, false 
*/
$taws_server_config[ 'enable_file_table_update' ] = false;

/*
  Description:
    Enable the updating for the default table(Data)
  Possible Values: bool
    true, false 
*/
$taws_server_config[ 'enable_default_table_update' ] = true;


/*
  Some thing for the updater  
*/
$taws_server_config[ 'php_timezone' ] = 'UTC';

/*
  Description:
    option to tell the updater on what days to update.
  Possible Values: strings
    +1 day, sunday, monday, tuesday, wendsday, friday and saterday
  Examples:
    $taws_server_config[ 'automatic_update_every' ] = 'monday';
    $taws_server_config[ 'automatic_update_every' ] = '+1 day';
  Notes:    
    '+1 day' means everyday.
    The day will be formated to start at the beggining of the day, 
    meaning at 12:00AM. 
*/
$taws_server_config[ 'automatic_update_every' ] = '+1 day';

/*
  Description:
    option to tell the updater on what time of the day to update
  Possible Values: strings
    any time from the 24 hour clock
    Use pm/am for 12 hour clocks 
  Notes:
   This uses the strtotime funtion, so i think u can do other cool things with time
*/
$taws_server_config[ 'automatic_update_at' ] = '3:00 am';


/*
  Description:
    One the update is finished run this query.
    This is perfect for removing "bad" content from your personal server
  Possible Values: strings
    a mysql query
  Examples:
    Lets say "bad" content refers to porn then the query will be:
    --
    DELETE FROM Types WHERE type = 'Pornography';
    --
    Since the Types table has it's foreign keys in Data table, all things
    connect to porn will get deleted    
  Notes:
    Binding is not yet supported.
*/
$taws_server_config[ 'post_update_query' ] = "DELETE FROM Types WHERE type = 'Pornography';";



//////////////////////
// Developer
//////////////////////

// NOTE these tools or for me or other developers to use and not for a user to user.

/*
  Description:
    Enable sending searches to the crawler to be checked aganst google.
    This is a way for me to get more data for the future.
    Send to crawler for better search(stcfbs)
  Possible Values: bool
    true, false 
  Notes: currenty this will use sqlite data to a local file on my computer, 
  meaning leave this option off(false), since you wont be able to use it anyway.
*/
$taws_server_config[ 'enable_stcfbs' ] = false;





?>