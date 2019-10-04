<?php

/**
 * @todo Implementar render en el getall
 */

require_once("function/snake_case_to.php");
require_once("class/db/My.php");
require_once("class/db/Pg.php");
require_once("class/model/Transaction.php");
require_once("class/model/SqlFormat.php");
require_once("class/model/Sqlo.php");
require_once("class/model/Entity.php");
require_once("class/model/RenderAux.php");

require_once("function/stdclass_to_array.php");
require_once("function/array_combine_concat.php");
require_once("function/toString.php");

class Dba { //Facilita el acceso a la base de datos
  /**
   * Prefijos y sufijos en el nombre de metodos:
   *   get: Utiliza id como parametro principal de busqueda
   *   all: Se refiere a un conjunto de valores
   *   one: Debe retornar un unico valor
   *   OrNull: Puede retornar valores nulos
   */
  public static $dbInstance = NULL; //conexion con una determinada db
  public static $dbCount = 0;

  public static function dbInstance() { //singleton db
    /**
     * Cuando se abren varios recursos de db instance se incrementa un contador, al cerrarse recursos se decrementa. Si el contador llega a 0 se cierra la instancia de la base
     */
    if (!self::$dbCount) {
      (DATA_DBMS == "pg") ?
        self::$dbInstance = new DbSqlPg(DATA_HOST, DATA_USER, DATA_PASS, DATA_DBNAME, DATA_SCHEMA) :
        self::$dbInstance = new DbSqlMy(DATA_HOST, DATA_USER, DATA_PASS, DATA_DBNAME, DATA_SCHEMA);
    }
    self::$dbCount++;
    return self::$dbInstance;
  }

  public static function dbClose() { //cerrar conexiones a la base de datos
    self::$dbCount--;
    if(!self::$dbCount) self::$dbInstance->close(); //cuando todos los recursos liberan la base de datos se cierra
    return self::$dbInstance;
  }

  public static function uniqId(){ //identificador unico
    //usleep(1); //con esto se evita que los procesadores generen el mismo id
    //if(isset($_SESSION["uniqid"])) $_SESSION["uniqid"]++;
    //else $_SESSION["uniqid"] = intval(date("Ymdhis"));
    //return $_SESSION["uniqid"];
    return uniqid();
    return hexdec(uniqid());

    //sleep(1);
    //return strtotime("now");
  }

  public static function nextId($entity) { //siguiente identificador
    return self::uniqId(); //uniq id

    //postgresql
    /**
     * $sql = "select nextval('" . self::entity($entity)->sn_() . "_id_seq')";
     * $row = self::fetchRow($sql);
     * return $row[0];
     */
  }

  public static function isPersistible($entity, array $row){ //es persistible?
    $row_ = self::_unique($entity, $row); //1) Consultar valores a partir de los datos
    $sqlo = EntitySqlo::getInstanceRequire($entity);

    if (count($row_)){
      $row["id"] = $row_["id"];
      return $sqlo->sql->isUpdatable($row);  //2) Si 1 dio resultado, verificar si es actualizable
    }

    return $sqlo->sql->isInsertable($row); //3) Si 1 no dio resultado, verificar si es insertable
  }

  public static function count($entity, $render = null){
    /**
     * cantidad
     */
    if(!$render) $render = new RenderAux();
    $render->setAggregate(["_count"]);
    $sql = EntitySqlo::getInstanceRequire($entity)->advanced($render);
    $row = self::fetchAssoc($sql);
    return intval($row["_count"]);
  }

  public static function _unique($entity, array $params, $render = null){ //busqueda estricta por campos unicos
    /**
     * $params
     *   array("nombre_field" => "valor_field", ...)
     */
    $sql = EntitySqlo::getInstanceRequire($entity)->_unique($params, $render);
    if(!$sql) return null;
    $rows = self::fetchAll($sql);

    if(count($rows) > 1) throw new Exception("La busqueda estricta por campos unicos de {$entity} retorno mas de un resultado");
    if(count($rows) == 1) return EntitySqlo::getInstanceRequire($entity)->json($rows[0]);
    return null;
  }

  public static function unique($entity, array $params, $render = null){ //busqueda por campos unicos
    /**
     * $params
     *   array("nombre_field" => "valor_field", ...)
     */
    $sql = EntitySqlo::getInstanceRequire($entity)->unique($params);
    if(empty($sql)) return null;

    $rows = self::fetchAll($sql);
    if(count($rows) > 1) throw new Exception("La busqueda por campos unicos de {$entity} retorno mas de un resultado");
    if(count($rows) == 1) return EntitySqlo::getInstanceRequire($entity)->json($rows[0]);
    return null;
  }

  public static function ids($entity, $render = null){ //devolver ids
    $sql = EntitySqlo::getInstanceRequire($entity)->all($render);
    $ids = self::fetchAllColumns($sql, 0);
    array_walk($ids, "toString"); //los ids son tratados como string para evitar un error que se genera en Angular (se resta un numero en los enteros largos)
    return $ids;
  }

  public static function id($render = null) { //devolver id
    $ids = self::ids($render);
    if(count($ids) > 1 ) throw new Exception("La consulta retorno mas de un resultado");
    elseif(count($ids) == 1) return (string)$ids[0];//los ids son tratados como string para evitar un error que se genera en Angular (se resta un numero en los enteros largos)
    else throw new Exception("La consulta no arrojó resultados");
  }

  public static function idOrNull($render = null) { //devolver id o null
    $ids = self::ids($render);
    if(count($ids) > 1 ) throw new Exception("La consulta retorno mas de un resultado");
    elseif(count($ids) == 1) return (string)$ids[0]; //los ids son tratados como string para evitar un error que se genera en Angular (se resta un numero en los enteros largos)
    else return null;
  }

  public static function all($entity, $render = null){ //devolver todos los valores
    $sqlo = EntitySqlo::getInstanceRequire($entity);
    $sql = $sqlo->all($render);
    return self::fetchAll($sql);
  }

  public static function get($entity, $id, $render = null) { //busqueda por id
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
    return self::fetchAll($sql);
  }

  public static function one($entity, $render = null) { //un solo valor
    $rows = self::all($entity, $render);
    if(count($rows) > 1 ) throw new Exception("La consulta retorno mas de un resultado");
    elseif(count($rows) == 1) return EntitySqlo::getInstanceRequire($entity)->json($rows[0]);
    else throw new Exception("La consulta no arrojó resultados");
  }

  public static function oneOrNull($entity, $render = null) { //un solo valor o null
    $rows = self::all($entity, $render);
    if(count($rows) > 1 ) throw new Exception("La consulta retorno mas de un resultado");
    elseif(count($rows) == 1) return $rows[0];
    else return null;
  }

  public static function isDeletable($entity, array $ids){ //es eliminable?
    if(!count($ids)) return "El identificador está vacío";

    $entities = [];

    for($i = 0; $i < count($ids); $i++){
      if(empty($ids[$i])) return "El identificador está vacío";

      foreach(Entity::getInstanceRequire($entity)->getFieldsRef() as $field) {
        if(self::count($field->getEntity()->getName(), [$field->getName(), "=", $ids[$i]])) array_push($entities, $field->getEntity()->getName());
      }
    }

    //print_r($entities);
    if(!count($entities)) return true;
    return "Esta asociado a " . implode(', ', array_unique($entities)) . ".";
  }

  public static function persist($entity, array $row){ //generar sql de persistencia para la entidad
    /**
     * Procedimiento:
     *   1) consultar valores a partir de los datos (CUIDADO UTILIZAR _unique en vez de unique para no restringir datos con condiciones auxiliares)
     *   2) Si 1 dio resultado, actualizar
     *   3) Si 1 no dio resultado, definir pk e insertar
     *
     * Retorno:
     *   array("id" => "id del campo persistido", "sql"=>"sql de persistencia", "detail"=>"detalle de los campos persistidos")
     *     "id": Dependiendo de la implementacion, el id del campo persistido puede no coincidir con el enviado
     *     "detail": array de elementos, cada elemento es un string concatenado de la forma entidadId, ejemplo "persona1234567890"
     */
    $sqlo = EntitySqlo::getInstanceRequire($entity);
    $row_ = self::_unique($entity, $row); //1

    if (!empty($row_)){ //2
      $row["id"] = $row_["id"];
      return $sqlo->update($row);
    }

    else { return $sqlo->insert($row); } //3
  }

  /*
  public static function field($entity, $field, $render = null){ //devuelve un array correspondiente al field
    //DEPRECATED
    $sqlo = EntitySqlo::getInstanceRequire($entity);
    $sql = $sqlo->all($render);
    $rows = self::fetchAll($sql);
    return array_values(array_unique(array_column ($rows ,$field)));
  }*/

  public static function fetchRow($sql){
    $db = self::dbInstance();
    try {
      $result = $db->query($sql);
      return $db->fetchRow($result);
    } finally { self::dbClose(); }
  }

  public static function fetchAssoc($sql){
    $db = self::dbInstance();
    try {
      $result = $db->query($sql);
      try { return $db->fetchAssoc($result); }
      finally { $result->close(); }
    } finally { self::dbClose(); }
  }

  public static function fetchAll($sql){
    $db = self::dbInstance();
    try {
      $result = $db->query($sql);

      try { return $db->fetchAll($result); }
      finally { $result->close(); }
    } finally { self::dbClose(); }
  }

  public static function fetchAllTimeAr($sql){
    $db = self::dbInstance();
    try {
      $db->query("SET lc_time_names = 'es_AR';");
      $result = $db->query($sql);
      try { return $db->fetchAll($result); }
      finally { $result->close(); }
    } finally { self::dbClose(); }
  }

  public static function fetchAllColumns($sql, $column = 0){ //query and fetch result
    $db = self::dbInstance();
    try {
      $result = $db->query($sql);
      try { return $db->fetchAllColumns($result, $column); }
      finally { $result->close(); }
    } finally { self::dbClose(); }
  }

  public static function identifier($entity, $identifier){
    $render = new Render();
    $render->setGeneralCondition(["_identifier","=",$identifier]);
    $sql = EntitySqlo::getInstanceRequire($entity)->all($render); 
    return Dba::fetchAll($sql);
  }


}
