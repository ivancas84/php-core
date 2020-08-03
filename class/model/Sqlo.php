<?php

require_once("function/snake_case_to.php");
require_once("class/model/Sql.php");
require_once("class/model/Render.php");
require_once("function/settypebool.php");



abstract class EntitySqlo {
  /**
   * SQL Object
   * Definir SQL para ser ejecutado directamente por el motor de base de datos
   */

  public $entity; //Entity. Configuracion de la entidad
  public $db;     //Para definir el sql es necesaria la existencia de una clase de acceso abierta, ya que ciertos metodos, como por ejemplo "escapar caracteres" lo requieren. Puede requerirse adicionalmente determinar el motor de base de datos para definir la sintaxis adecuada
  public $sql;    //EntitySql. Atributo auxiliar para facilitar la definicion de consultas sql
  protected static $instances = [];

  function __destruct() {
    Dba::dbClose();
  } 

  final public static function getInstance() {
    $className = get_called_class();
    if (!isset(self::$instances[$className])) {
      $c = new $className;
      self::$instances[$className] = $c;
    }
    return self::$instances[$className];
  }

  final public static function getInstanceRequire($entity) {
    $dir = "class/model/sqlo/";
    $name = snake_case_to("XxYy", $entity) . ".php";
    $prefix = "";
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_SRC."/".$dir.$name)) require_once($dir.$name);
    else{
      $prefix = "_";
      require_once($dir.$prefix.$name);
    }
    
    $className = $prefix.snake_case_to("XxYy", $entity) . "Sqlo";
    return call_user_func("{$className}::getInstance");
  }

  final public function __clone() { trigger_error('Clone is not allowed.', E_USER_ERROR); } //singleton

  final public function __wakeup(){ trigger_error('Unserializing is not allowed.', E_USER_ERROR); } //singleton

  public function nextPk(){ return $this->db->uniqId(); } //siguiente identificador unico
  
  public function jsonAll(array $rows){ foreach($rows as &$row) $row = $this->json($row); return $rows; }
  
  public function json(array $row) { return $this->sql->_json($row); }

  public function valuesAll(array $rows){ foreach($rows as &$row) $row = $this->values($row); return $rows; }

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
    $row_[$this->entity->getName()] = EntityValues::getInstanceRequire($this->entity->getName(), $json);
  }

  public function all($render = NULL) {
    $r = Render::getInstance($render);
    $sql = "SELECT DISTINCT
{$this->sql->fields()}
{$this->sql->fromSubSql($r)}
{$this->sql->join($r)}
" . concat($this->sql->condition($r), 'WHERE ') . "
{$this->sql->orderBy($r->getOrder())}
{$this->sql->limit($r->getPage(), $r->getSize())}
";

    return $sql;
  }

  public function getAll(array $ids, $render = NULL) {
    $r = Render::getInstance($render);
    //Para dar soporte a distintos tipos de id, se define la condicion de ids a traves del metodo conditionAdvanced en vez de utilizar IN como se hacia habitualmente
    $advanced = [];
    for($i = 0; $i < count($ids); $i++) {
      $connect = ($i == 0) ? "AND" : "OR";
      array_push($advanced, ["id", "=", $ids[$i], $connect]);
    }
    if(!count($advanced)) return null;

    $r->addCondition($advanced);

    return $this->all($r);
  }

  public function ids($render = NULL) {
    $r = Render::getInstance($render);
    $sql = "SELECT DISTINCT
{$this->sql->fieldId()}
{$this->sql->fromSubSql($r)}
{$this->sql->join($r)}
" . concat($this->sql->condition($r), 'WHERE ') . "
{$this->sql->orderBy($r->getOrder())}
{$this->sql->limit($r->getPage(), $r->getSize())}
";

    return $sql;
  }

  public function advanced(Render $render) { //consulta avanzada
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

    $having_ = $this->sql->having($render);
    $having = empty($having_) ? "" : "HAVING {$having_}
";

    $sql = "SELECT DISTINCT
{$fieldsQuery}
{$this->sql->fromSubSql($render)}
{$this->sql->join($render)}
" . concat($this->sql->condition($render), 'WHERE ') . "
{$group}
{$having}
{$this->sql->order($render->getOrder())}
{$this->sql->limit($render->getPage(), $render->getSize())}
";

    return $sql;
  }

  protected function _insert(array $row) { throw new BadMethodCallException ("Metodo abstracto no implementado"); } //sql de insercion
  protected function _update(array $row) { throw new BadMethodCallException ("Metodo abstracto no implementado"); } //sql de actualizacion

  public function insert(array $row) { //Formatear valores y definir sql de insercion
    /**
     * La insercion tiene en cuenta todos los campos correspondientes a la tabla, si no estan definidos, les asigna "null" o valor por defecto
     * Puede incluirse un id en el array de parametro, si no esta definido se definira uno automaticamente
     * @return array("id" => "identificador principal actualizado", "sql" => "sql de actualizacion", "detail" => "detalle de campos modificados")
     */
    $r_ = $this->sql->format($row);
    $sql = $this->_insert($r_);

    return array("id" => $row["id"], "sql" => $sql, "detail"=>[$this->entity->getName().$row["id"]]);
  }

  public function update(array $row) { //sql de actualizacion
    $r_ = $this->sql->format($row);
    $sql = "
{$this->_update($r_)}
WHERE {$this->entity->getPk()->getName()} = {$r_['id']};
";

    return array("id" => $r["id"], "sql" => $sql, "detail"=>[$this->entity->getName().$r["id"]]);
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

  public function unique(array $params, $render = NULL){
    /**
     * filtrar campos unicos
     * $params:
     *   array("nombre_field" => "valor_field", ...)
     * los campos unicos simples se definen a traves del atributo Field::$unique
     * los campos unicos multiples se definen a traves del meotodo Entity::getFieldsUniqueMultiple();
     */
    $r = Render::getInstance($render);

    $conditionUniqueFields = $this->sql->conditionUniqueFields($params);
    if(empty($conditionUniqueFields)) return null;

    return "SELECT DISTINCT
{$this->sql->fields()}
{$this->sql->fromSubSql($r)}
{$this->sql->join($r)}
WHERE
{$conditionUniqueFields}
" . concat($this->sql->condition($r), 'AND ') . "
";


  }
  

}
