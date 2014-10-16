<?php
/*
Setting for the sever.php and middleman to use
*/

$taws_server_config = array();

///////////////
// General SQL
///////////////

// What sql program will you like to use? 
// NOTE only mysql is supported now
$taws_server_config[ 'sql_program' ] = 'mysql';//may need to be remove


// The Database are seperated by language, set the language you want
// NOTE make sure its in an array
$taws_server_config[ 'sql_update_language' ] = [ 'english' ];

// when is it a good time to update when in automated update mode?
//$taws_server_config[ 'sql_update_time' ] = "23:00";

//////////////////
// Mysql Options
////////////////

// The server/host name or ip
$taws_server_config[ 'mysql_server' ] = 'localhost';

// Name of database
$taws_server_config[ 'mysql_db_name' ] = 'taws_data';

// User name for database
$taws_server_config[ 'mysql_db_user' ] = 'root';

// Password for database
$taws_server_config[ 'mysql_db_pass' ] = '';


//////////////////
// SQLite otions
//////////////////

//location of the sqlite file
//$taws_server_config[ 'sqlite_file_path' ] = './serverdata/masterv.sqlite';


//////////////////
// Sphinx otions
//////////////////
//NOTE if anyone knows how to get  sphinx working sqlite please help.

// The server/host name or ip
$taws_server_config[ 'sphinx_server' ] = 'localhost';

// The port
$taws_server_config[ 'sphinx_port' ] = 9312;

////////////////////
// Data Collection
////////////////////
// NOTE here are tools that will help taws learn, it will require you to send infomation to the remote server.

// Enable storing of search queries and selected results maps  
// For example, when a user makes a search for 'santa cluase' then selects a result, it'll be stored as a pair
$taws_server_config[ 'enable_dc_ubm' ] = true;


//////////////////////////////
// Search query optimizations 
//////////////////////////////

//This will allow addition of word synonyms to the search query
//$taws_server_config[ 'enable_synonym_optimization' ] = true; //depricated


?>