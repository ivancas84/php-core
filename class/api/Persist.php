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
    $value = $this->container->getValue($this->entityName)->_fromArray($data, "set")->_call("reset")->_call("_check");
    /*if($value->_getLogs()->isError()) throw new Exception($value->_getLogs()->toString());
    
    $detail = [];
    /**
     * Solo se registrara el detalle de las actualizaciones
     * El cache debe actualizarse solo con las actualizaciones
     * Las inserciones no se encuentran cacheadas y seran posteriormente consultadas
     */
    
    //$row_ = $this->container->getDb()->unique($this->entityName, $data);
    //if (!empty($row_)){ $value->setId($row_["id"]);
    /*if(!Validation::is_empty($value->id())){
      $sql = $this->container->getSqlo($this->entityName)->update($value->_toArray("sql"));
    } else {
      $value->_call("setDefault");
      $sql = $this->container->getSqlo($this->entityName)->insert($value->_toArray("sql"));
    }

    $this->container->getDb()->multi_query_transaction_log($sql);
    
    return ["id" => $value->id(), "detail" => $this->$entityName.$value->id()];*/
  }
}



