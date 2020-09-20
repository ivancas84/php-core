<?php

require_once("function/snake_case_to.php");
require_once("class/model/Sql.php");
require_once("class/model/Render.php");
require_once("function/settypebool.php");

class EntitySqlo {
  /**
   * SQL Object
   * Definir SQL para ser ejecutado directamente por el motor de base de datos
   */

  public $entityName;
  /**
   * String con el nombre de la entidad (facilita la construccion)
   */

  public $container;

  public function json(array $row) { 
    return $this->container->getValue($this->entity->getName())->_fromArray($row, "set")->_toArray("json");
  }

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
    $row_[$this->entity->getName()] = $this->container->getValue($this->entity->getName())->_fromArray($row, "set");
    return $row_;
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
{$this->mapping->id()}
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

  public function insert(array $row) { throw new BadMethodCallException ("Metodo abstracto no implementado"); } //sql de insercion
  public function _update(array $row) { throw new BadMethodCallException ("Metodo abstracto no implementado"); } //sql de actualizacion

  public function update(array $row) { //sql de actualizacion
    return "
{$this->_update($r_)}
WHERE {$this->entity->getPk()->getName()} = {$row['id']};
";

  }

  public function updateAll($row, array $ids) { //sql de actualizacion para un conjunto de ids
    /**

     * La actualizacion solo tiene en cuenta los campos definidos, los que no estan definidos, no seran considerados manteniendo su valor previo.
     * este metodo define codigo que modifica la base de datos, debe utilizarse cuidadosamente
     * debe verificarse la existencia de ids correctos
     * No permite actualizar ids (no se me ocurre una razon valida por la que permitirlo)
     * @return array("id" => "identificador principal actualizado", "sql" => "sql de actualizacion", "detail" => "detalle de campos modificados")
     */
    if(empty($ids)) throw new Exception("No existen identificadores definidos");
    $ids_ = $this->sql->formatIds($ids);
    $r_ = $this->container->getValue($this->entity->getName())->_fromArray($row, "set")->_toArray("sql");
    return "
{$this->_update($r_)}
WHERE {$this->entity->getPk()->getName()} IN ({$ids_});
";
  }

  public function delete($id){
    /**
     * Eliminar un elemento
     */
    return $this->deleteAll([$id]);
  }

  public function deleteAll(array $ids) { 
    /**
     * Eliminar varios elementos
     * Este metodo define codigo que modifica la base de datos, debe utilizarse cuidadosamente
     * debe verificarse la existencia de ids correctos
     */
    if(empty($ids)) throw new Exception("No existen identificadores definidos");
    $ids_ = $this->sql->formatIds($ids);
    return "
DELETE FROM {$this->entity->sn_()}
WHERE id IN ({$ids_});
";
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
