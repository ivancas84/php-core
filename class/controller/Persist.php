<?php


require_once("class/tools/Filter.php");
require_once("class/model/Ma.php");
require_once("class/controller/Transaction.php");

require_once("class/model/Sqlo.php");
require_once("function/stdclass_to_array.php");

class Persist {
  /**
   * Comportamiento general de persistencia
   */

  protected $entityName;

  final public static function getInstance() {
    $className = get_called_class();
    return new $className;
  }

  final public static function getInstanceRequire($entity) {
    $dir = "class/controller/persist/";
    $name = snake_case_to("XxYy", $entity) . ".php";
    $className = snake_case_to("XxYy", $entity) . "Persist";    
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_SRC."/".$dir.$name)) require_once($dir.$name);
    else{
      require_once($dir."_".$name);
      $className = "_".$className;    
    }
    return call_user_func("{$className}::getInstance");
  }

  public function main($data){
    if(empty($data)) throw new Exception("Se está intentando persistir un conjunto de datos vacío");

    $ma = Ma::open();
    $row_ = $ma->unique($this->entityName, $data);
    $values = EntityValues::getInstanceRequire($this->entityName)->_fromArray($data);
    if(!$values->_check()) throw new Exception($values->_getLogs()->toString());
        
    if (!empty($row_)){ 
      $values->setId($row_["id"]);
      $persist = EntitySqlo::getInstanceRequire($this->entityName)->update($values->_toArray());
    } else {
      $values->_setDefault();
      $persist = EntitySqlo::getInstanceRequire($this->entityName)->insert($values->_toArray());
    }

    $ma->multi_query_transaction($persist["sql"]);
    
    return ["id" => $persist["id"], "detail" => $persist["detail"]]
  }
}



