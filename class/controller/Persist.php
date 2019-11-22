<?php


require_once("class/tools/Filter.php");
require_once("class/controller/Dba.php");

require_once("class/model/Sqlo.php");
require_once("function/stdclass_to_array.php");

abstract class Persist {
  /**
   * Comportamiento general de persistencia
   * El objetivo de este script es procesar un conjunto de entidades evitando multiples accesos a la base de datos
   * Recibe un array de objetos {entity:"entidad", row:objeto con valores} o {entity:"entidad", rows:Array de objetos con valores}
   * Retorna el id principal de las entidades procesadas
   * Tener en cuenta que el id persistido, no siempre puede ser el id retornado (por ejemplo para el caso que se utilicen logs en la base de datos)
   * Es importante el orden de procesamiento, una entidad a procesar puede requerir una entidad previamente procesada
   */
  protected $logs = [];

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

  final public static function getInstance() {
    $className = get_called_class();
    return new $className;
  }

  final public static function getInstanceRequire($entity) {
    require_once("class/controller/persist/" . snake_case_to("XxYy", $entity) . ".php");
    $className = snake_case_to("XxYy", $entity) . "Persist";
    return call_user_func("{$className}::getInstance");
  }
  
  public function delete($entity, array $ids, array $params = null){
    $ids = [];
    $sql = "";
    $detail = [];

    if(!empty($ids)) {
      $persist = EntitySqlo::getInstanceRequire($entity)->deleteAll($ids, $params);
      $ids = $persist["ids"];
      $sql = $persist["sql"];
      $detail = array_merge($detail, $persist["detail"]);
    }

    return ["action"=>"delete", "entity"=>$entity, "ids"=> $ids, "sql"=>$sql, "detail"=>$detail];
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
      if(!empty($params)) $idsActuales = Dba::ids($entity, [$params["name"], '=', $params["value"]]);

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

      $persist = Dba::persistAll($entity, $rows, $params);
      $this->delete($entity, $idsActuales);        
      array_push($this->logs, [
        "action"=>"persist", 
        "entity"=>$entity, 
        "ids"=>$persist["ids"], 
        "sql"=>$persist["sql"], 
        "detail"=>$persist["detail"]
      ]);
  }

  public function row($entity, $row) {
      /**
       * Persistir row
       * $row:
       *   Valores a persisitir
       */

      $id = null;
      $sql ="";
      $detail = [];
      
      if(!empty($row)) {
        $persist = Dba::persist($entity, $row);
        $id = $persist["id"];
        $sql = $persist["sql"];
        $detail = $persist["detail"];
      }

      array_push($this->logs, ["action"=>"persist",  "entity"=>$entity, "ids"=>[$id], "sql"=>$sql, "detail"=>$detail]);
      return $id;
  }

  public function main($data){
    foreach($data as $d) {
      $entity = $d["entity"]; //entidad
      $row = (isset($d["row"])) ? $d["row"]: null; //row a procesar
      $rows = (isset($d["rows"])) ? $d["rows"]: null; //rows a procesar
      $id = (isset($d["id"])) ? $d["id"] : null; //id a eliminar
      $ids =  (isset($d["ids"])) ? $d["ids"] : null; //ids a eliminar
      $params = (isset($d["params"])) ? $d["params"] : null; //campos relacionados para identificacion
      /**
       * $params["name"]: Nombre de la clave foranea
       * $params["value]: Valor de la clave foranea
       */
  
      if(isset($row)) $this->row($entity, $row);
      if(isset($rows)) $this->row($entity, $row, $params);
      if(isset($id)) $this->delete($entity, [$id], $params);
      if(isset($ids)) $this->delete($entity, $ids, $params);
    }
    return $this->logs;
  }
}




