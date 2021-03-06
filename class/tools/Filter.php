<?php

require_once("function/stdclass_to_array.php");

class Filter {
  /**
   * Administracion de parametros
   */

  public static function requestAll(){ return $_REQUEST; }
  public static function postAll(){ return $_POST; }
  public static function getAll(){ return $_GET; }

  public static function post($name){ return filter_input(INPUT_POST, $name); }
  public static function get($name){ return filter_input(INPUT_GET, $name); }

  public static function postAllRequired(){
    $r = self::postAll();
    if(empty($r)) throw new Exception("No existen parametros");
    return $r;
  }

  public static function getAllRequired(){
    $r = self::getAll();
    if(empty($r)) throw new Exception("No existen parametros");
    return $r;
  }

  public static function requestAllRequired(){
    $request = self::requestAll();
    if(empty($request)) throw new Exception("No existen parametros");
    return $request;
  }

  public static function getRequired($name){
    $var = self::get($name);
    if(!isset($var)) throw new Exception($name . " sin definir");
    return $var;
  }

  public static function postRequired($name){
    $var = self::post($name);
    if(!isset($var)) throw new Exception($name . " sin definir");
    return $var;
  }

  public static function postArray($name){
    $var = filter_input(INPUT_POST, $name, FILTER_DEFAULT , FILTER_REQUIRE_ARRAY);
    return (isset($var)) ? $var : array();
  }

  public static function postArrayRequired($name){
    $var = self::postArray($name);
    if(!isset($var)) throw new Exception($name . " sin definir");
    return $var;
  }

  public static function requestArray($name){
    $varAux = filter_input(INPUT_POST, $name, FILTER_DEFAULT , FILTER_REQUIRE_ARRAY);
    $var = (isset($varAux)) ? $varAux : filter_input(INPUT_GET, $name, FILTER_DEFAULT , FILTER_REQUIRE_ARRAY);
    return (isset($var)) ? $var : array();
  }

  public static function requestArrayRequired($name){
    $var = self::requestArray($name);
    if(!isset($var)) throw new Exception($name . " sin definir");
    return $var;
  }

  public static function request($name){
    $varAux = filter_input(INPUT_POST, $name);
    return (isset($varAux)) ? $varAux : filter_input(INPUT_GET, $name);
  }

  public static function requestRequired($name){
    $var = self::request($name);
    if(!isset($var)) throw new Exception($name . " sin definir");
    return $var;
  }

  public static function file($name){
     $args = array($name => array('filter'=> FILTER_DEFAULT,  'flags' => FILTER_REQUIRE_ARRAY));
     $files = filter_var_array($_FILES, $args);
     return (isset($files[$name])) ? $files[$name] : null;
  }

  public static function fileRequired($name){
    $file = self::file($name);
    if(!isset($file)) throw new Exception("Archivo " . $name . " sin definir");
    return $file;
  }
    
  public static function jsonPost(){ 
    $data = file_get_contents("php://input");
    return stdclass_to_array(json_decode($data));
  }

  public static function jsonGet(){
    $data = [];
    foreach(self::getAll() as $key => $value) $data[$key] = json_decode($value);
    return stdclass_to_array($data);
  }
  
  public static function jsonPostRequired(){
    $r = self::jsonPost();
    if(empty($r)) throw new Exception("No existen parametros");
    return $r;
  }

  public static function jsonGetRequired(){
    $r = self::jsonGet();
    if(empty($r)) throw new Exception("No existen parametros");
    return $r;
  }

  public static function serverUrl(){
    $protocol = (filter_input(INPUT_SERVER, 'HTTPS', FILTER_SANITIZE_STRING)) ? "https":"http";  
    return $protocol . "://" . filter_input(INPUT_SERVER, 'SERVER_NAME', FILTER_SANITIZE_STRING);
  }

  public static function serverUrlSrc(){
    return self::serverUrl()."/".PATH_SRC;
  }



}
