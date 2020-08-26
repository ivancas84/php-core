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

  public $entityName;
  public $db;

  public function main($data){
    if(empty($data)) throw new Exception("Se está intentando persistir un conjunto de datos vacío");

    $row_ = $this->container->getDb()->unique($this->entityName, $data);
    $values = $this->container->getValues($this->entityName)->_fromArray($data);
    if(!$values->_check()) throw new Exception($values->_getLogs()->toString());
        
    if (!empty($row_)){ 
      $values->setId($row_["id"]);
      $persist = $this->container->getSqlo($this->entityName)->update($values->_toArray());
    } else {
      $values->_setDefault();
      $persist = $this->container->getSqlo($this->entityName)->insert($values->_toArray());
    }

    $this->db->multi_query_transaction_log($persist["sql"]);
    
    return ["id" => $persist["id"], "detail" => $persist["detail"]];
  }
}



