<?php

require_once("function/stdclass_to_array.php");

class Filter {
  /**
   * Administracion de parametros
   */

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

    
  public static function jsonPost(){ 
    $data = file_get_contents("php://input");
    return strclass_to_array(json_decode($data));
  }
  
  public static function jsonPostRequired(){
    $r = self::jsonPost();
    if(empty($r)) throw new Exception("No existen parametros");
    return $r;
  }

  public static function display(array $params, $key = "display") {
    /**
     * Desde el cliente se recibe un Display, es una objeto similar a Render pero con algunas caracteristicas adicionales
     */
    $data = null;

    //data es utilizado debido a la facilidad de comunicacion entre el cliente y el servidor. Se coloca todo el json directamente en una variable data que es convertida en el servidor.
    if(isset($params[$key])) {
      $data = $params[$key];
      unset($params[$key]);
    }

    $f_ = json_decode($data);
    $display = stdclass_to_array($f_);
    if(empty($display["size"])) $display["size"] = 100;
    if(empty($display["page"])) $display["page"] = 1;
    if(!isset($display["order"])) $display["order"] = [];
    if(!isset($display["condition"])) $display["condition"] = [];

    foreach($params as $key => $value) {
      /**
       * Los parametros fuera de display, se priorizan y reasignan a Display
       * Si los atributos son desconocidos se agregan como filtros
       */
      switch($key) {
        case "size": case "page": case "search": //pueden redefinirse ciertos parametros la prioridad la tiene los que estan fuera del elemento data (parametros definidos directamente)
          $display[$key] = $value;
        break;
        case "order": //ejemplo http://localhost/programacion/curso/all?order={%22horario%22:%22asc%22}
          $f_ = json_decode($value);
          $display["order"] = stdclass_to_array($f_); //ordenamiento ascendente (se puede definir ordenamiento ascendente de un solo campo indicandolo en el parametro order, ejemplo order=campo)
        break;


        default: array_push($display["condition"], [$key,"=",$params[$key]]);
      }
    }

    return $display;
  }


}
