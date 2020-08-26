<?php

/**
 * @todo Implementar render en el getall
 */

require_once("class/model/Db.php");
require_once("class/model/SqlFormat.php");
require_once("class/model/Sqlo.php");
require_once("class/model/Entity.php");
require_once("class/model/Render.php");
require_once("class/controller/Transaction.php");

require_once("function/snake_case_to.php");
require_once("function/stdclass_to_array.php");
require_once("function/array_combine_concat.php");
require_once("function/toString.php");

class Ma extends Db {
  /**
   * Model access (Acceso rapido al modelo)
   * Interfaz opcional entre el modelo y la base de datos para ser utilizada con los métodos de uso general
   * Prefijos y sufijos en el nombre de metodos:
   *   get: Utiliza id como parametro principal de busqueda
   *   all: Se refiere a un conjunto de valores
   *   one: Debe retornar un unico valor
   *   OrNull: Puede retornar valores nulos
   */

  public static $dbInstanceMa = [];

  public static function open($host = DATA_HOST, $user = DATA_USER, $passwd = DATA_PASS, $dbname = DATA_DBNAME){
    if (!key_exists($host.$dbname, self::$dbInstanceMa)) {
      self::$dbInstanceMa[$host.$dbname] = new self($host, $user, $passwd, $dbname);
    } 
    return self::$dbInstanceMa[$host.$dbname];
  }

  public function __destruct(){
    if (key_exists($this->host.$this->dbname, self::$dbInstanceMa) && ($this->thread_id == self::$dbInstanceMa[$this->host.$this->dbname]->thread_id)) {
      unset(self::$dbInstanceMa[$this->host.$this->dbname]);
    }
    parent::close();
  }

  
  public function count($entity, $render = null){
    /**
     * cantidad
     */
    $r = Render::getInstance($render);
    $r->setSize(false);
    $r->setPage(1);
    $r->setOrder([]);

    if(!in_array("_count", $r->getAggregate())) $r->setAggregate(["_count"]);
    
    $sql = EntitySqlo::getInstanceRequire($entity)->advanced($r);
    $result = $this->query($sql);
    $row = $result->fetch_assoc();
    $result->free();
    return intval($row["_count"]);
  }

  public function advanced($entity, Render $render){
    /**
     * consulta avanzada
     */
    $sql = EntitySqlo::getInstanceRequire($entity)->advanced($render);
    $result = $this->query($sql);
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    return $rows;    
  }

  public function unique($entity, array $params, $render = null){ //busqueda por campos unicos
    /**
     * $params
     *   array("nombre_field" => "valor_field", ...)
     */
    $sql = EntitySqlo::getInstanceRequire($entity)->unique($params);
    if(empty($sql)) return null;

    $result = $this->query($sql);
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    if(count($rows) > 1) throw new Exception("La busqueda por campos unicos de {$entity} retorno mas de un resultado");
    if(count($rows) == 1) return $rows[0];
    return null;
  }

  public function ids($entity, $render = null){
    $sql = EntitySqlo::getInstanceRequire($entity)->ids($render);
    $result = $this->query($sql);
    $ids = $this->fetch_all_columns($result, 0);
    $result->free();
    array_walk($ids, "toString"); 
    /**
     * los ids son tratados como string para evitar un error que se genera en Angular (se resta un numero en los enteros largos)
     */
    return $ids;
  }

  public function id($entity, $render = null) {
    /**
     * id
     */
    $ids = $this->ids($entity, $render);
    if(count($ids) > 1 ) throw new Exception("La consulta retorno mas de un resultado");
    elseif(count($ids) == 1) return (string)$ids[0];
    /**
     * los ids son tratados como string para evitar un error que se genera en Angular (se resta un numero en los enteros largos)
     */
    else throw new Exception("La consulta no arrojó resultados");
  }

  public function idOrNull($entity, $render = null) {
    /**
     * id o null
     */
    $ids = $this->ids($entity, $render);
    if(count($ids) > 1 ) throw new Exception("La consulta retorno mas de un resultado");
    elseif(count($ids) == 1) return (string)$ids[0];
    /**
     * los ids son tratados siempre como string para evitar un error que se genera en Angular (se resta un numero en los enteros largos)
     */
    else return null;
  }

  public function all(string $entity, $render = null){
    /**
     * todos los valores
     */
    $sql = EntitySqlo::getInstanceRequire($entity)->all($render);
    $result = $this->query($sql);
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    return $rows;
  }

  public function get(string $entity, $id, $render = null) { //busqueda por id
    if(!$id) throw new Exception("No se encuentra definido el id");
    $rows = $this->getAll($entity, [$id], $render);
    if (!count($rows)) throw new Exception("La búsqueda por id no arrojó ningun resultado");
    return $rows[0];
  }

  public function getOrNull($entity, $id, $render = null){ //busqueda por id o null
    if(empty($id)) return null;
    $rows = $this->getAll($entity, [$id], $render);
    return (!count($rows)) ? null : $rows[0];
  }

  public function getAll($entity, $ids, $render = null){ //busqueda por ids
    if(empty($ids)) return [];
    if(!is_array($ids)) $ids = [$ids];
    $sqlo = EntitySqlo::getInstanceRequire($entity)->getAll($ids, $render);
    $result = $this->query($sql);
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    return $rows;
  }

  public function one($entity, $render = null) { //un solo valor
    $rows = $this->all($entity, $render);
    if(count($rows) > 1 ) throw new Exception("La consulta retorno mas de un resultado");
    elseif(count($rows) == 1) return $rows[0];
    else throw new Exception("La consulta no arrojó resultados");
  }

  public function oneOrNull($entity, $render = null) { //un solo valor o null
    $rows = $this->all($entity, $render);
    if(count($rows) > 1 ) throw new Exception("La consulta retorno mas de un resultado");
    elseif(count($rows) == 1) return $rows[0];
    else return null;
  }

  public function identifier($entity, $identifier){
    $render = new Render();
    $render->setGeneralCondition(["_identifier","=",$identifier]);
    $sql = EntitySqlo::getInstanceRequire($entity)->all($render); 
    $sqlo = EntitySqlo::getInstanceRequire($entity)->getAll($ids, $render);
    $result = $this->query($sql);
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    return $rows;
  }

  public function persist($entity, array $row){
    /**
     * Persistencia directa (no realiza chequeo de valores ni log)
     */
    $row_ = $this->unique($entity, $row); 
    
    if (!empty($row_)){ 
      $row["id"] = $row_["id"];
      return $this->update($entity, $row);
    }

    else { return $this->insert($entity, $row); }
  }

  public function insert($entity, array $row){ 
    /**
     * Insercion directa (no realiza chequeo de valores ni log)
     */
    $insert = EntitySqlo::getInstanceRequire($entity)->insert($row);
    $result = $this->query($insert["sql"]);
    return $insert;
  }

  public function update($entity, array $row){
    /**
     * Actualizacion directa (no realiza chequeo de valores ni log)
     */ 
    $update = EntitySql::getInstanceRequire($entity)->update($row);
    $result = $this->query($update["sql"]);
    return $udpate;
  }
}
