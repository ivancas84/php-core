<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");
require_once("class/controller/DisplayRender.php");


abstract class Base {
  /**
   * Controlador base
   * Todos los controladores que no se adaptan a las estructuras existentes pueden definirse con Base
   * El usuario puede retornar el valor que desee 
   * Data esta pensado para ser llamado a traves de una api
   * En el caso de que no se utilice una api, conviene utilizar directamente ModelTools.
   **/
  
  public $entityName; 

  final public static function getInstance() {
    $className = get_called_class();
    return new $className;
  }

  final public static function getInstanceRequire($entity) {
    $dir = "class/controller/base/";
    $name = snake_case_to("XxYy", $entity) . ".php";
    $className = snake_case_to("XxYy", $entity) . "Base";    
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_SRC."/".$dir.$name)) require_once($dir.$name);
    else{
      require_once($dir."_".$name);
      $className = "_".$className;    
    }
    return call_user_func("{$className}::getInstance");
  }

  abstract public function main($params);

}
