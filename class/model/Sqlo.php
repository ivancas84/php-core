<?php

require_once("function/snake_case_to.php");
require_once("class/model/Sql.php");
require_once("class/db/Interface.php");
require_once("function/settypebool.php");
require_once("class/model/Render.php");
require_once("class/model/RenderAux.php");


abstract class EntitySqlo { //SQL object
  /**
   * Definir SQL para ser ejecutado directamente por el motor de base de datos
   */

  public $entity; //Entity. Configuracion de la entidad
  public $db;     //Para definir el sql es necesaria la existencia de una clase de acceso abierta, ya que ciertos metodos, como por ejemplo "escapar caracteres" lo requieren. Puede requerirse adicionalmente determinar el motor de base de datos para definir la sintaxis adecuada
  public $sql;    //EntitySql. Atributo auxiliar para facilitar la definicion de consultas sql
  protected static $instances = [];

  public function nextPk(){ return $this->db->uniqId(); } //siguiente identificador unico
  protected function _insert(array $row) { throw new BadMethodCallException ("Metodo abstracto no implementado"); } //sql de insercion
  protected function _update(array $row) { throw new BadMethodCallException ("Metodo abstracto no implementado"); } //sql de actualizacion
  public function json(array $row) { return $this->sql->_json($row); }

  public function values(array $row){ //retornar instancias de EntityValues
    /**
     * Recorre la cadena de relaciones del resultado de una consulta y retorna instancias de EntityValues
     * El resultado es almacenado en un array asociativo.
     * Las claves del array son nombres representativo de la entidad que contiene
     * Las claves se forman a partir del nombre de la clave foranea
     * Se asigna un numero incremental a la clave en el caso de que se repita
     * Este metodo debe sobrescribirse en el caso de que existan relaciones
     */
    $row_ = [];

    $json = ($row && !is_null($row['id'])) ? $this->sql->_json($row) : null;
    $row_["toma"] = EntityValues::getInstanceFromString("toma", $json);
  }


  public function jsonAll(array $rows){
    foreach($rows as &$row) $row = $this->json($row);
    return $rows;
  }

  public function valuesAll(array $rows){
    foreach($rows as &$row) $row = $this->values($row);
    return $rows;
  }

  final public static function getInstance() {
    $className = get_called_class();
    if (!isset(self::$instances[$className])) {
      $c = new $className;
      self::$instances[$className] = $c;
    }
    return self::$instances[$className];
  }

  final public static function getInstanceFromString($entity) {
    $className = snake_case_to("XxYy", $entity) . "Sqlo";
    return call_user_func("{$className}::getInstance");
  }

  final public function __clone() { trigger_error('Clone is not allowed.', E_USER_ERROR); } //singleton

  final public function __wakeup(){ trigger_error('Unserializing is not allowed.', E_USER_ERROR); } //singleton

  protected function render($render = null){ //Definir clase de presentacion
    /**
     * @param String | Object | Array | Render En función del tipo de parámetro define el render
     * @return Render Clase de presentacion
     */
    if(gettype($render) == "object") return $render;

    $r = new Render();
    if(gettype($render) == "string") $r->setSearch($render);
    elseif (gettype($render) == "array") $r->setAdvanced($render);
    return $r;
  }

  public function insert(array $row) { //Formatear valores y definir sql de insercion
    /**
     * La insercion tiene en cuenta todos los campos correspondientes a la tabla, si no estan definidos, les asigna "null" o valor por defecto
     * Puede incluirse un id en el array de parametro, si no esta definido se definira uno automaticamente
     * @return array("id" => "identificador principal actualizado", "sql" => "sql de actualizacion", "detail" => "detalle de campos modificados")
     */
    $r = $this->sql->initializeInsert($row);
    $r_ = $this->sql->format($r);
    $sql = $this->_insert($r_);

    return array("id" => $r["id"], "sql" => $sql, "detail"=>[$this->entity->getName().$r["id"]]);
  }

  public function update(array $row) { //sql de actualizacion
    $r = $this->sql->initializeUpdate($row);
    $r_ = $this->sql->format($r);
    $sql = "
{$this->_update($r_)}
WHERE {$this->entity->getPk()->getName()} = {$row['id']};
";

    return array("id" => $r_["id"], "sql" => $sql, "detail"=>[$this->entity->getName().$r["id"]]);
  }

  public function updateAll($row, array $ids) { //sql de actualizacion para un conjunto de ids
    /**
     * Formatear valores y definir sql de actualizacion para un conjunto de ids
     * La actualizacion solo tiene en cuenta los campos definidos, los que no estan definidos, no seran considerados manteniendo su valor previo.
     * este metodo define codigo que modifica la base de datos, debe utilizarse cuidadosamente
     * debe verificarse la existencia de ids correctos
     * No permite actualizar ids (no se me ocurre una razon valida por la que permitirlo)
     * @return array("id" => "identificador principal actualizado", "sql" => "sql de actualizacion", "detail" => "detalle de campos modificados")
     */
    if(empty($ids)) throw new Exception("No existen identificadores definidos");
    $ids_ = $this->sql->formatIds($ids);
    $r = $this->sql->initializeUpdate($row);
    $r_ = $this->sql->format($r);
    $sql = "
{$this->_update($r_)}
WHERE {$this->entity->getPk()->getName()} IN ({$ids_});
";
    $detail = $ids;
    array_walk($detail, function(&$item) { $item = $this->entity->getName().$item; });
    return ["ids"=>$ids, "sql"=>$sql, "detail"=>$detail];
  }

  public function delete($id){ //eliminar
    $delete = $this->deleteAll([$id]);
    return ["id"=>$delete["ids"][0], "sql"=>$delete["sql"], "detail"=>$delete["detail"]];
  }

  public function deleteAll(array $ids) { //eliminar
    /**
     * Este metodo define codigo que modifica la base de datos, debe utilizarse cuidadosamente
     * debe verificarse la existencia de ids correctos
     */
    if(empty($ids)) throw new Exception("No existen identificadores definidos");
    $ids_ = $this->sql->formatIds($ids);
    $sql = "
DELETE FROM {$this->entity->sn_()}
WHERE id IN ({$ids_});
";

    $detail = $ids;
    array_walk($detail, function(&$item) { $item = $this->entity->getName().$item; });
    return ["ids"=>$ids, "sql"=>$sql, "detail"=>$detail];
  }

  public function deleteRequiredAll($ids, $params){ //eliminacion relacionada a clave foranea
    /**
     * En su caso mas general, este metodo es similar a deleteAll
     * Esta pensado para facilitar la reimplementacion en el caso de que se lo requiera
     * En muchos casos, una entidad A puede requerir la eliminacion de otra entidad B,
     * dependiendo de las restricciones de B puede ser necesario una eliminacion o nulificacion
     */
    return $this->deleteAll($ids);
  }

  public function count($render = NULL) { //sql cantidad de valores
    $r = $this->render($render);

    return "
SELECT count(DISTINCT " . $this->sql->fieldId() . ") AS \"num_rows\"
{$this->sql->from()}
{$this->sql->join()}
{$this->sql->joinAux()}
{$this->sql->conditionAll($r)}
";
  }

  public function all($render = NULL) {

    $r = $this->render($render);

    $sql = "SELECT DISTINCT
{$this->sql->fieldsAll()}
{$this->sql->from()}
{$this->sql->join()}
{$this->sql->joinAux()}
{$this->sql->conditionAll($r)}
{$this->sql->orderBy($r->getOrder())}
{$this->sql->limit($r->getPage(), $r->getSize())}
";

    return $sql;
  }

  public function ids($render = NULL) { //sql para obtener ids
    /**
     * No admite ordenamiento
     */
    $r = $this->render($render);

    $sql = "SELECT DISTINCT {$this->sql->fieldId()}
{$this->sql->from()}
{$this->sql->join()}
{$this->sql->joinAux()}
{$this->sql->conditionAll($r)}
{$this->sql->limit($r->getPage(), $r->getSize())}
";

    return $sql;
  }

  public function getAll(array $ids, $render = NULL) {
    $r = $this->render($render);
    //Para dar soporte a distintos tipos de id, se define la condicion de ids a traves del metodo conditionAdvanced en vez de utilizar IN como se hacia habitualmente
    $advanced = [];
    for($i = 0; $i < count($ids); $i++) {
      $connect = ($i == 0) ? "AND" : "OR";
      array_push($advanced, ["id", "=", $ids[$i], $connect]);
    }
    if(!count($advanced)) return null;

    $r->addAdvanced($advanced);

    return $this->all($r);
  }


  public function _unique(array $row){ //busqueda auxiliar por campos unicos
    /**
     * Unique puede restringir el acceso a datos dependiendo del rol y la condicion auxiliar
     */
    $conditionUniqueFields = $this->sql->conditionUniqueFields($row);
    if(empty($conditionUniqueFields)) return null;

    return "SELECT DISTINCT
{$this->sql->fieldsAll()}
{$this->sql->from()}
{$this->sql->join()}
{$this->sql->joinAux()}
WHERE
{$conditionUniqueFields}
";
  }

  public function unique(array $row, $render = NULL){ //busqueda por campos unicos
    $r = $this->render($render);

    $conditionUniqueFields = $this->sql->conditionUniqueFields($row);
    if(empty($conditionUniqueFields)) return null;

    return "SELECT DISTINCT
{$this->sql->fieldsAll()}
{$this->sql->from()}
{$this->sql->join()}
{$this->sql->joinAux()}
WHERE
{$conditionUniqueFields}
{$this->sql->conditionAll($r, 'AND')}
";
  }



  /* DEPRECATED
  //TODO refactorizar metodo
  public function uploadCsv($handle) {
    //***** definir headers *****
    $headersAux = fgetcsv($handle, 0, ';'); if(is_null($headersAux)) throw new Exception("Archivo CSV no válido");
    $headers = array_map('trim', $headersAux);

    //***** recorrer archivo csv y definir sql *****
    $sql = ""; //se define todo el sql y se ejecuta conjuntamente
    $rowCount = 0; //se utiliza un contador para indicar el numero de fila correspondiente al error, si es que existe alguno
    $errores = ""; //se almacenan en un string los errores

    while (($dataCsv = fgetcsv($handle, 0, ';')) !== FALSE) {
      $rowCount++;

      $dataAux = array_map('trim', $dataCsv);
      $data = array_combine($headers, $dataAux);
      try{
        $persist = $this->persistSql($data);
        $sql .= $persist["sql"];
      }
      catch (Exception $ex){
        $errores .= $ex->getMessage() . " (fila " . $rowCount . ")
";
      }

    }

    //persistir datos
    $this->db->multiQueryTransaction($sql);
  }*/


  public function select($select, $render = NULL) { //select definido por el usuario
    $r = $this->render($render);

    $sql = "SELECT {$select}
{$this->sql->from()}
{$this->sql->join()}
{$this->sql->joinAux()}
{$this->sql->conditionAll($r)}
{$this->sql->orderBy($r->getOrder())}
{$this->sql->limit($r->getPage(), $r->getSize())}
";

    return $sql;
  }


  public function advanced(RenderAux $render) { //consulta avanzada
    $fields = array_merge($render->getGroup(), $render->getAggregate());

    $fieldsQuery_ = [];
    foreach($fields as $field){
      $f = $this->sql->mappingField($field);
      array_push($fieldsQuery_, "{$f} AS {$field}");
    }

    $fieldsQuery = implode(', ', $fieldsQuery_);

    $group_ = $render->getGroup();
    $group = empty($group_) ? "" : "GROUP BY " . implode(", ", $group_) . "
";

    $having_ = $this->sql->having($render->getHaving());
    $having = empty($having_) ? "" : "HAVING {$having_}
";

    $sql = "SELECT DISTINCT
{$fieldsQuery}
{$this->sql->from()}
{$this->sql->join()}
{$this->sql->joinAux()}
{$this->sql->conditionAll($render)}
{$group}
{$having}
{$this->sql->order($render->getOrder())}
{$this->sql->limit($render->getPage(), $render->getSize())}
";

    return $sql;
  }

}
