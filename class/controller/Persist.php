<?php


require_once("class/tools/Filter.php");
require_once("class/model/Ma.php");
require_once("class/controller/Transaction.php");

require_once("class/model/Sqlo.php");
require_once("function/stdclass_to_array.php");

class Persist {
  /**
   * Comportamiento general de persistencia
   * El objetivo de este script es procesar un conjunto de entidades evitando multiples accesos a la base de datos
   * Recibe un array de objetos {entity:"entidad", row:objeto con valores} o {entity:"entidad", rows:Array de objetos con valores}
   * Retorna el id principal de las entidades procesadas
   * Tener en cuenta que el id persistido, no siempre puede ser el id retornado (por ejemplo para el caso que se utilicen logs en la base de datos)
   * Es importante el orden de procesamiento, una entidad a procesar puede requerir una entidad previamente procesada
   */

  protected $logs = [];
  /**
   * Cada elemento de logs es un array con la siguiente informacion
   * action
   * entity
   * ids
   * detail
   */

  final public static function getInstance() {
    $className = get_called_class();
    return new $className;
  }

  final public static function getInstanceString($entity) {
    $className = snake_case_to("XxYy", $entity) . "Persist";
    return call_user_func("{$className}::getInstance");
  }

  final public static function getInstanceRequire($entity){
    require_once("class/controller/persist/" . snake_case_to("XxYy", $entity) . ".php");
    return self::getInstanceString($entity);
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
    array_push($this->logs, ["action"=>"delete", "entity"=>$entity, "ids"=>[$persist["id"]], "sql"=>$persist["sql"], "detail"=>$persist["detail"]]);
  }

  public function deleteAll($entity, array $ids, array $params = null){
    if(!empty($ids)) {
      $persist = EntitySqlo::getInstanceRequire($entity)->deleteAll($ids, $params);
    }

    array_push($this->logs, ["action"=>"delete", "entity"=>$entity, "ids"=>$persist["ids"], "sql"=>$persist["sql"], "detail"=>$persist["detail"]]);
  }

  public function rows($entity, array $rows = [], array $params = null) {
      /**
       * Procesar un conjunto de rows
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
        "action"=>"persist", 
        "entity"=>$entity, 
        "ids"=>$persist["ids"], 
        "sql"=>$persist["sql"], 
        "detail"=>$persist["detail"]
      ]);
  }

  public function persist($entity, $row) {
      /**
       * Persistir row
       * $row:
       *   Valores a persisitir
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

      array_push($this->logs, ["action"=>"persist",  "entity"=>$entity, "ids"=>[$id], "sql"=>$sql, "detail"=>$detail]);
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

    array_push($this->logs, ["action"=>"insert",  "entity"=>$entity, "ids"=>[$id], "sql"=>$sql, "detail"=>$detail]);
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

    array_push($this->logs, ["action"=>"update",  "entity"=>$entity, "ids"=>[$id], "sql"=>$sql, "detail"=>$detail]);
    return $id;
  }

  public function main($data){
    $this->persist($entity, $row);
  }

}




