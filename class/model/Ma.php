<?php

/**
 * @todo Implementar render en el getall
 */

require_once("class/model/db/My.php");
require_once("class/model/db/Pg.php");
require_once("class/model/SqlFormat.php");
require_once("class/model/Sqlo.php");
require_once("class/model/Entity.php");
require_once("class/model/Render.php");
require_once("class/controller/Transaction.php");

require_once("function/snake_case_to.php");
require_once("function/stdclass_to_array.php");
require_once("function/array_combine_concat.php");
require_once("function/toString.php");

class Ma {
  /**
   * Model access (Acceso rapido al modelo)
   * Interfaz opcional entre el modelo y la base de datos para ser utilizada con los métodos de uso general
   * Prefijos y sufijos en el nombre de metodos:
   *   get: Utiliza id como parametro principal de busqueda
   *   all: Se refiere a un conjunto de valores
   *   one: Debe retornar un unico valor
   *   OrNull: Puede retornar valores nulos
   */

  public static function nextId($entity) { //siguiente identificador
    return Dba::uniqId(); //uniq id

    //postgresql
    /**
     * $sql = "select nextval('" . self::entity($entity)->sn_() . "_id_seq')";
     * $row = Dba::fetchRow($sql);
     * return $row[0];
     */
  }

  public static function count($entity, $render = null){
    /**
     * cantidad
     */
    $r = Render::getInstance($render);
    $r->setSize(false);
    $r->setPage(1);
    $r->setOrder([]);

    if(!in_array("_count", $r->getAggregate())) $r->setAggregate(["_count"]);
    
    $sql = EntitySqlo::getInstanceRequire($entity)->advanced($r);
    $row = Dba::fetchAssoc($sql);
    return intval($row["_count"]);
  }

  public static function advanced($entity, Render $render){
    /**
     * consulta avanzada
     */
    $sql = EntitySqlo::getInstanceRequire($entity)->advanced($render);
    return Dba::fetchAll($sql);    
  }

  public static function unique($entity, array $params, $render = null){ //busqueda por campos unicos
    /**
     * $params
     *   array("nombre_field" => "valor_field", ...)
     */
    $sql = EntitySqlo::getInstanceRequire($entity)->unique($params);

    if(empty($sql)) return null;

    $rows = Dba::fetchAll($sql);
    if(count($rows) > 1) throw new Exception("La busqueda por campos unicos de {$entity} retorno mas de un resultado");
    if(count($rows) == 1) return $rows[0];
    return null;
  }

  public static function ids($entity, $render = null){
    $sql = EntitySqlo::getInstanceRequire($entity)->ids($render);
    $ids = Dba::fetchAllColumns($sql, 0);
    array_walk($ids, "toString"); 
    /**
     * los ids son tratados como string para evitar un error que se genera en Angular (se resta un numero en los enteros largos)
     */
    return $ids;
  }

  public static function id($entity, $render = null) {
    /**
     * id
     */
    $ids = self::ids($entity, $render);
    if(count($ids) > 1 ) throw new Exception("La consulta retorno mas de un resultado");
    elseif(count($ids) == 1) return (string)$ids[0];
    /**
     * los ids son tratados como string para evitar un error que se genera en Angular (se resta un numero en los enteros largos)
     */
    else throw new Exception("La consulta no arrojó resultados");
  }

  public static function idOrNull($entity, $render = null) {
    /**
     * id o null
     */
    $ids = self::ids($entity, $render);
    if(count($ids) > 1 ) throw new Exception("La consulta retorno mas de un resultado");
    elseif(count($ids) == 1) return (string)$ids[0];
    /**
     * los ids son tratados siempre como string para evitar un error que se genera en Angular (se resta un numero en los enteros largos)
     */
    else return null;
  }

  public static function all(string $entity, $render = null){
    /**
     * todos los valores
     */
    $sql = EntitySqlo::getInstanceRequire($entity)->all($render);
    return Dba::fetchAll($sql);
  }

  public static function get(string $entity, $id, $render = null) { //busqueda por id
    if(!$id) throw new Exception("No se encuentra definido el id");
    $rows = self::getAll($entity, [$id], $render);
    if (!count($rows)) throw new Exception("La búsqueda por id no arrojó ningun resultado");
    return $rows[0];
  }

  public static function getOrNull($entity, $id, $render = null){ //busqueda por id o null
    if(empty($id)) return null;
    $rows = self::getAll($entity, [$id], $render);
    return (!count($rows)) ? null : $rows[0];
  }

  public static function getAll($entity, $ids, $render = null){ //busqueda por ids
    if(empty($ids)) return [];
    if(!is_array($ids)) $ids = [$ids];
    $sqlo = EntitySqlo::getInstanceRequire($entity);
    $sql = $sqlo->getAll($ids, $render);
    return Dba::fetchAll($sql);
  }

  public static function one($entity, $render = null) { //un solo valor
    $rows = self::all($entity, $render);
    if(count($rows) > 1 ) throw new Exception("La consulta retorno mas de un resultado");
    elseif(count($rows) == 1) return $rows[0];
    else throw new Exception("La consulta no arrojó resultados");
  }

  public static function oneOrNull($entity, $render = null) { //un solo valor o null
    $rows = self::all($entity, $render);
    if(count($rows) > 1 ) throw new Exception("La consulta retorno mas de un resultado");
    elseif(count($rows) == 1) return $rows[0];
    else return null;
  }

  public static function identifier($entity, $identifier){
    $render = new Render();
    $render->setGeneralCondition(["_identifier","=",$identifier]);
    $sql = EntitySqlo::getInstanceRequire($entity)->all($render); 
    return Dba::fetchAll($sql);
  }

  public static function persist($entity, array $row){
    /**
     * Persistencia directa (no realiza chequeo de valores ni log)
     */
    $row_ = self::unique($entity, $row); 
    
    if (!empty($row_)){ 
      $row["id"] = $row_["id"];
      return self::update($entity, $row);
    }

    else { return self::insert($entity, $row); }
  }

  public static function insert($entity, array $row){ 
    /**
     * Insercion directa (no realiza chequeo de valores ni log)
     */
    $insert = EntitySql::getInstanceRequire($entity)->insert($row);
    Dba::multiQueryTransaction($insert["sql"]);
    return $insert;
  }

  public static function update($entity, array $row){
    /**
     * Actualizacion directa (no realiza chequeo de valores ni log)
     */ 
    $update = EntitySql::getInstanceRequire($entity)->update($row);
    Dba::multiQueryTransaction($insert["sql"]);
    return $udpate;
  }
}
