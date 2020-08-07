<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");
require_once("class/controller/DisplayRender.php");


class All {
  /**
   * Obtener todos los datos de una determinada entidad
   */

  public $entityName;
  
  final public static function getInstance() {
    $className = get_called_class();
    return new $className;
  }

  final public static function getInstanceRequire($entity) {
    $dir = "class/controller/all/";
    $name = snake_case_to("XxYy", $entity) . ".php";
    $className = snake_case_to("XxYy", $entity) . "All";    
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_SRC."/".$dir.$name)) require_once($dir.$name);
    else{
      require_once($dir."_".$name);
      $className = "_".$className;    
    }
    return call_user_func("{$className}::getInstance");
  }
  
  public function main($display) {
    $displayRender = DisplayRender::getInstanceRequire($this->entityName);
    $render = $displayRender->main($display);
    $ma = Ma::open();
    $rows = $ma->all($this->entityName, $render);
    $sqlo = EntitySqlo::getInstanceRequire($this->entityName);
    foreach($rows as &$row) $row = $sqlo->json($row);
    return $rows;
  }

}
