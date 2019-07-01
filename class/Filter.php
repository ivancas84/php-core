<?php

//Filtros de parametros
class Filter {

  public static function requestAll(){ return $_REQUEST; }
  public static function postAll(){ return $_POST; }
  public static function post($name){ return filter_input(INPUT_POST, $name); }
  public static function get($name){ return filter_input(INPUT_GET, $name); }

  public static function postAllRequired(){
    $r = self::postAll();
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

  public static function search(){
    $filter = array();
    $filter["entity"] = self::requestRequired("entity");
    $filter["search"] = self::request("search");
    $filter["filter"] = self::requestArray("filter");
    $filter["params"] = self::requestArray("params");
    $filter["page"] = self::request("page");
    $filter["size"] = self::request("size");
    $filter["order"] = self::requestArray("order");
    return $filter;
  }

  public static function requestData(){
    $f = self::requestRequired("data");
    $f_ =  json_decode($f);
    return stdclass_to_array($f_);
  }





}
