<?php
  
  /**
  * A class to swicth between files
  */
  class LanguageSelect{
    private $selected_lang = null;
    
    /**
    * Constructor
    */
    function __construct( $lang ){ 
      $temp = strtolower( $lang );      
      require_once "./languages/$temp.php";        
      $this->selected_lang = $language;       
    }
    
    /**
    *
    */
    function getLanguage(){
      return $this->selected_lang;
    }
  
  }
  
  
  //var_dump( (new LanguageSelect( "english" ))->getLanguage() );
  
?>