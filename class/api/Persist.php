<?php


require_once("class/tools/Filter.php");
require_once("class/model/Ma.php");

require_once("class/model/Sqlo.php");
require_once("class/tools/Validation.php");

require_once("function/stdclass_to_array.php");

class PersistApi {
  /**
   * Comportamiento general de persistencia
   */

  public $entityName;
  public $container;
  
  public function main(){
    $data = Filter::jsonPostRequired();

    if(empty($data)) throw new Exception("Se está intentando persistir un conjunto de datos vacío");
    
    $p = $this->container->getPersist();
    $persist = $p->id($this->entityName, $data);
    $this->container->getDb()->multi_query_transaction_log($persist["sql"]);
    return ["id" => $persist["id"], "detail" => [$this->entityName.$persist["id"]]];
  }
}



