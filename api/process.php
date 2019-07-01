<?php

/**
 * Script de procesamiento
 * El objetivo de este script es procesar un conjunto de entidades evitando multiples accesos a la base de datos
 * Recibe un array de objetos {entity:"entidad", row:objeto con valores} o {entity:"entidad", rows:Array de objetos con valores}
 * Retorna el id principal de las entidades procesadas
 * Tener en cuenta que el id persistido, no siempre puede ser el id retornado (por ejemplo para el caso que se utilicen logs en la base de datos)
 * Es importante el orden de procesamiento, una entidad a procesar puede requerir una entidad previamente procesada
 */
require_once("class/Filter.php");
require_once("class/model/Dba.php");

require_once("config/structure.php");
require_once("config/modelClasses.php");
require_once("config/entityClasses.php");
require_once("config/valuesClasses.php");
require_once("class/model/Sqlo.php");
require_once("function/stdclass_to_array.php");

function rows($entity, array $rows = [], array $params = null){ //procesar un conjunto de rows
  /**
   * $rows:
   *   Valores a persisitir
   *
   * $options:
   *   Posee datos de identificacion para determinar los valores actuales y modificarlos o eliminarlos segun corresponda
   *   Habitualmente es un array asociativo con los siguientes elementos:
   *     name: Nombre de la clave foranea
   *     value: valor de la clave foranea
   *
   * Procedimiento:
   *   1) obtener $ids actuales en base a $rowId
   *   2) recorrer los datos a persistir $rows:
   *      a) Combinarlos con los parametros $rowId
   *      b) Comparar $row["id"] con $id, si es igual, eliminar $id del array
   */
  $ret = [ "ids" => [], "sql" => "", "detail" => [] ];

  $idsActuales = [];
  if(!empty($params)) $idsActuales = Dba::ids($entity, [$params["name"], '=', $params["value"]]);

  foreach($rows as $row){
    if(!empty($params["name"])) $row[$params["name"]] = $params["value"];

    if(!empty($row["id"])) { //eliminar id persistido del array de $ids previamente consultado
      $key = array_search($row["id"], $idsActuales);
      if($key !== false) unset($idsActuales[$key]);
      $idsActuales = array_values($idsActuales); //resetear indices
    }

    $persist = Dba::persist($entity, $row);
    $ret["sql"] .= $persist["sql"];
    array_push($ret["ids"], $persist["id"]);
    $ret["detail"] = array_merge($ret["detail"], $persist["detail"]);
  }

  if(!empty($idsActuales)) {
    $persist = EntitySqlo::getInstanceFromString($entity)->deleteRequiredAll($idsActuales, $params);

    /**
     * La eliminacion puede ser fisica, logica o simplemente puede nulificar ciertos campos
     * El tipo de eliminacion es definido por cada entidad
     * El parametro opcional $params, puede ser utilizado para indicar el comportamiento requerido a la entidad
     */
    $ret["sql"] .= $persist["sql"];
    $ret["detail"] = array_merge($ret["detail"], $persist["detail"]);
  }

  return $ret;

}

function row($entity, $row) { //persistir row
  /**
   * $row:
   *   Valores a persisitir
   */
  $ret = [ "id" => null, "sql" => "", "detail" => [] ];
  if(empty($row)) return $ret;
  return Dba::persist($entity, $row);
}

function delete($entity, array $ids = [], array $params = null){ //eliminar un conjunto de rows
  /**
   * $ids:
   *   Ids a eliminar
   *
   * $params:
   *   Posee datos de identificacion para determinar los valores actuales y modificarlos o eliminarlos segun corresponda
   *   Habitualmente es un array asociativo con los siguientes elementos:
   *     name: Nombre de la clave foranea
   *     value: valor de la clave foranea
   */
  $ret = [ "ids" => [], "sql" => "", "detail" => [] ];

  if(!empty($ids)) {
    $persist = EntitySqlo::getInstanceFromString($entity)->deleteRequiredAll($ids, $params);
    /**
     * La eliminacion puede ser fisica, logica o simplemente puede nulificar ciertos campos
     * El tipo de eliminacion es definido por cada entidad
     * El parametro opcional $params, puede ser utilizado para indicar el comportamiento requerido a la entidad
     */
    $ret["sql"] .= $persist["sql"];
    $ret["detail"] = array_merge($ret["detail"], $persist["detail"]);
    $ret["ids"] = $persist["ids"];
  }

  return $ret;
}

try {
  $f = Filter::requestRequired("data");
  $f_ =  json_decode($f);
  $data = stdclass_to_array($f_);

  $sql = "";
  $response = [];
  $detail = [];

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

    if(isset($row)) {
      $persist = row($entity, $row);
      $sql .= $persist["sql"];
      $detail = array_merge($detail, $persist["detail"]);
      if(!empty($persist["id"])) array_push($response, ["entity" => $entity, "id" => $persist["id"], "data"=>$detail]);
    }

    if(isset($rows)){
      $persist = rows($entity, $rows, $params);
      $sql .= $persist["sql"];
      $detail = array_merge($detail, $persist["detail"]);
      if(!empty($persist["ids"])) array_push($response, ["entity" => $entity, "ids" => $persist["ids"], "data" => $detail]);
    }

    if(isset($id)){
      $persist = delete($entity, [$id], $params);
      $sql .= $persist["sql"];
      $detail = array_merge($detail, $persist["detail"]);
      if(!empty($persist["ids"])) array_push($response, ["entity" => $entity, "id" => $persist["ids"][0], "data" => $detail]);
    }

    if(isset($ids)){
      $persist = delete($entity, $ids, $params);
      $sql .= $persist["sql"];
      $detail = array_merge($detail, $persist["detail"]);
      if(!empty($persist["ids"])) array_push($response, ["entity" => $entity, "ids" => $persist["ids"], "data" => $detail]);
    }
  }

  Transaction::begin();
  Transaction::update(["descripcion"=> $sql, "detalle" => implode(",",$detail)]);
  Transaction::commit();
  echo json_encode($response);

} catch (Exception $ex) {
  error_log($ex->getTraceAsString());
  http_response_code(500);
  echo $ex->getMessage();
}
