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

  protected $logs = [];
  /**
   * Cada elemento de logs es un array con la siguiente informacion 
   * sql
   * detail
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
      $persist = EntitySqlo::getInstanceRequire($entity)->insert($row);
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
      $persist = EntitySqlo::getInstanceRequire($entity)->update($row);
      $id = $persist["id"];
      $sql = $persist["sql"];
      $detail = $persist["detail"];
    }

    array_push($this->logs, ["sql"=>$sql, "detail"=>$detail]);
    return $id;
  }

  public function main($data){
    if(empty($data)) return;

    $ma = Ma::open();
    $row_ = $ma->unique($this->entityName, $data);
    $values = EntityValues::getInstanceRequire($this->entityName)->_fromArray($data);
    if(!$values->_check()) throw new Exception($values->_getLogs()->toString());
        
    if (!empty($row_)){ 
      $values->setId($row_["id"]);
      $id = $this->update($this->entityName, $values->_toArray());
    } else {
      $values->setId(uniqid());
      $values->_setDefault();
      $id = $this->insert($this->entityName, $values->_toArray());
    }

    $db = Db::open();
    $db->multi_query_transaction($this->getSql());
    
    return $id;
  }
}



