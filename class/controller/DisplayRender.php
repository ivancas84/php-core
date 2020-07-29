<?php
require_once("class/model/Render.php");

class DisplayRender {
  /**
   * Transformar parametros (display) en presentacion (render)
   * Display es similar a Render pero se estructura a traves de arrays (construido mediante un json)
   * Display se obtiene principalmente a traves de parametros desde el cliente
   */

  public $entityName;

  final public static function getInstance() {
    $className = get_called_class();
    return new $className;
  }

  final public static function getInstanceRequire($entity) {
    $dir = "class/controller/displayRender/";
    $name = snake_case_to("XxYy", $entity) . ".php";
    $className = snake_case_to("XxYy", $entity) . "DisplayRender";    
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_SRC."/".$dir.$name)) require_once($dir.$name);
    else{
      require_once($dir."_".$name);
      $className = "_".$className;    
    }
    return call_user_func("{$className}::getInstance");
  }

  public function main($display) {
    return Render::getInstanceDisplay($display);
  }

}
