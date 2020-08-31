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

    $values = $this->container->getValues($this->entityName)->_fromArray($data)->_reset();
    if(!$values->_check()) throw new Exception($values->_getLogs()->toString());
    
    $detail = [];
    /**
     * Solo se registrara el detalle de las actualizaciones
     * El cache debe actualizarse solo con las actualizaciones
     * Las inserciones no se encuentran cacheadas y seran posteriormente consultadas
     */
    
    //$row_ = $this->container->getDb()->unique($this->entityName, $data);
    //if (!empty($row_)){ $values->setId($row_["id"]);
    if(!Validation::is_empty($values->id())){
      $persist = $this->container->getSqlo($this->entityName)->update($values->_toArray());
      $detail = $persist["detail"];
    } else {
      $values->_setDefault();
      $persist = $this->container->getSqlo($this->entityName)->insert($values->_toArray());
    }

    $this->container->getDb()->multi_query_transaction_log($persist["sql"]);
    
    return ["id" => $persist["id"], "detail" => $detail];
  }
}



