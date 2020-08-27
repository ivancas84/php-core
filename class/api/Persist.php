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
  public $container;

  public $logs = [];
  /**
   * Cada elemento de logs es un array con la siguiente informacion 
   * sql
   * detail
   */

  public function getSql() {
    $sql = "";
    foreach($this->logs as $log) {
      if (!empty($log["sql"])) $sql .= $log["sql"];
    }
    return $sql;
  }

  public function getDetail() {
    $detail = [];
    foreach($this->logs as $log) {
      if (!empty($log["detail"])) $detail = array_merge($detail, $log["detail"]);
    }
    return $detail;
  }

  public function getLogsKeys($keys){
    $logs = [];
    foreach($this->logs as $log) {
      $l = [];
      foreach($keys as $key) $l[$key] = $log[$key];
      array_push($logs, $l);
    }
    return $logs;
  }

  public function insert($entity, $row) {
    /**
     * Persistir row
     * $row:
     *   Valores a persisitir
     */
    $id = null;
    $sql ="";
    $detail = [];
    
    if(!empty($row)) {
      $persist = $this->getContainer()->getSqlo($entity)->insert($row);
      $id = $persist["id"];
      $sql = $persist["sql"];
      $detail = $persist["detail"];
    }

    array_push($this->logs, ["sql"=>$sql, "detail"=>$detail]);
    return $id;
  }

  public function update($entity, $row) {
    $id = null;
    $sql ="";
    $detail = [];
    
    if(!empty($row)) {
      $persist = $this->getContainer()->getSqlo($entity)->update($row);
      $id = $persist["id"];
      $sql = $persist["sql"];
      $detail = $persist["detail"];
    }

    array_push($this->logs, ["sql"=>$sql, "detail"=>$detail]);
    return $id;
  }

  public function main(){
    $data = Filter::jsonPostRequired();

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

    $this->container->getDb()->multi_query_transaction_log($persist["sql"]);
    
    return ["id" => $persist["id"], "detail" => $persist["detail"]];
  }
}



