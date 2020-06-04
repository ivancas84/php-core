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
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_ROOT."/".$dir.$name)) require_once($dir.$name);
    else{
      require_once($dir."_".$name);
      $className = "_".$className;    
    }
    return call_user_func_array("{$className}::getInstance", [$values, $prefix]);
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

  public function delete($entity, $id, array $params = null){
    $persist = EntitySqlo::getInstanceRequire($entity)->delete($id);
    array_push($this->logs, ["sql"=>$persist["sql"], "detail"=>$persist["detail"]]);
  }

  public function deleteAll($entity, array $ids, array $params = null){
    if(!empty($ids)) {
      $persist = EntitySqlo::getInstanceRequire($entity)->deleteAll($ids, $params);
    }

    array_push($this->logs, ["sql"=>$persist["sql"], "detail"=>$persist["detail"]]);
  }


  public function save_($entity, array $rows = [], array $params = null) {
      /**
       * Procesar un conjunto de rows de una entidad
       * $rows:
       *   Valores a persisitir
       *
       * $params:
       *   Posee datos de identificacion para determinar los valores actuales y modificarlos o eliminarlos segun corresponda
       *   Habitualmente es un array asociativo con los siguientes elementos:
       *     name: Nombre de la clave foranea
       *     value: valor de la clave foranea
       *
       * Procedimiento:
       *   1) obtener $ids actuales en base a $params
       *   2) recorrer los datos a persistir $rows:
       *      a) Combinarlos con los parametros $rowId
       *      b) Comparar $row["id"] con $id, si es igual, eliminar $id del array
       */
      $idsActuales = [];
      if(!empty($params)) $idsActuales = Ma::ids($entity, [$params["name"], '=', $params["value"]]);

      foreach($rows as $row){
          if(!empty($params["name"])) $row[$params["name"]] = $params["value"];

          if(!empty($row["id"])) {
            /**
             * eliminar id persistido del array de $ids previamente consultado
             */
            $key = array_search($row["id"], $idsActuales);
            if($key !== false) unset($idsActuales[$key]);
            $idsActuales = array_values($idsActuales); //resetear indices
          }
      }

      $persist = Ma::persistAll($entity, $rows, $params);
      $this->delete($entity, $idsActuales);        
      array_push($this->logs, [
        "sql"=>$persist["sql"], 
        "detail"=>$persist["detail"]
      ]);
  }

  public function save($entity, $row) {
      /**
       * inserta o actualiza (persiste)
       * @param $row: Valores a persisitir
       */

      $id = null;
      $sql ="";
      $detail = [];
      
      if(!empty($row)) {
        $persist = Ma::persist($entity, $row);
        $id = $persist["id"];
        $sql = $persist["sql"];
        $detail = $persist["detail"];
      }

      array_push($this->logs, ["sql"=>$sql, "detail"=>$detail]);
      return $id;
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
    /**
     * Persistir row
     * $row:
     *   Valores a persisitir
     */
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
    return $this->save($this->entityName, $data);
  }

}




