<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");

class Unique {
  /**
   * Comportamiento general de all
   */

  public $entityName;

  final public static function getInstance() {
    $className = get_called_class();
    return new $className;
  }

  final public static function getInstanceRequire($entity) {
    $dir = "class/controller/unique/";
    $name = snake_case_to("XxYy", $entity) . ".php";
    $className = snake_case_to("XxYy", $entity) . "Unique";    
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_ROOT."/".$dir.$name)) require_once($dir.$name);
    else{
      require_once($dir."_".$name);
      $className = "_".$className;    
    }
    return call_user_func_array("{$className}::getInstance", [$values, $prefix]);
  }

  public function main($params) {
    //$params = ["domicilio"=>"1543133270054093"];
    return Ma::unique($this->entityName, $params);
  }

}
