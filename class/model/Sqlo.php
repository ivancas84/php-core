<?php

require_once("function/snake_case_to.php");
require_once("class/model/Render.php");
require_once("function/settypebool.php");
require_once("function/get_entity_relations.php");


class EntitySqlo {
  /**
   * SQL Object
   * Definir SQL para ser ejecutado directamente por el motor de base de datos
   */

  public $container;
  public $entityName;


  protected function fieldsQuery(Render $render){
    $fields = array_merge($render->getGroup(), $render->getFields());

    $fieldsQuery_ = [];
    foreach($fields as $key => $fieldName){
      /**
     * Interpretar prefijo y obtener mapping
     */
      $f = explode("-",$fieldName);
      if(count($f) == 2) {
        $prefix = $f[0];
        $entityName = get_entity_relations($this->entityName)[$f[0]];
        $mapping = $this->container->getMapping($entityName, $prefix);
        $fieldName = $f[1];
      } else { 
        $mapping = $this->container->getMapping($this->entityName);
      }
      
      $alias = (is_integer($key)) ? $mapping->_pf() . str_replace(".","_",$fieldName) : $key;
      $f = $mapping->_($fieldName) . " AS " . $alias;

      array_push($fieldsQuery_, $f);
    }

    return implode(', ', $fieldsQuery_);
  }

  public function select(Render $render) {
    $fieldsQuery = $this->fieldsQuery($render);

    $group_ = [];
    if(!empty($render->getGroup())){
      foreach($render->getGroup() as $field){
        $f = $this->container->getRel($this->entityName)->mapping($field);
        array_push($group_, $f);
      }
    }

    $group = empty($group_) ? "" : "GROUP BY " . implode(", ", $group_) . "
";

    $having_ = $this->container->getSql($this->entityName)->having($render);
    $having = empty($having_) ? "" : "HAVING {$having_}
";

    $sql = "SELECT DISTINCT
{$fieldsQuery}
{$this->container->getSql($this->entityName)->fromSubSql($render)}
{$this->container->getRel($this->entityName)->join($render)}
" . concat($this->container->getSql($this->entityName)->condition($render), 'WHERE ') . "
{$group}
{$having}
{$this->container->getSql($this->entityName)->orderBy($render->getOrder())}
{$this->container->getSql($this->entityName)->limit($render->getPage(), $render->getSize())}
";

    return $sql;
  }

  public function update(array $row) { //sql de actualizacion
    return "
{$this->_update($row)}
WHERE {$this->container->getEntity($this->entityName)->getPk()->getName()} = {$row['id']};
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
    $ids_ = $this->container->getSql($this->entityName)->formatIds($ids);
    $r_ = $this->container->getValue($this->entityName)->_fromArray($row, "set")->_toArray("sql");
    return "
{$this->_update($r_)}
WHERE {$this->container->getEntity($this->entityName)->getPk()->getName()} IN ({$ids_});
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
    $ids_ = $this->container->getSql($this->entityName)->formatIds($ids);
    return "
DELETE FROM {$this->container->getEntity($this->entityName)->sn_()}
WHERE id IN ({$ids_});
";
  }


  public function insert(array $row){
    /**
     * El conjunto de valores debe estar previamente formateado
     */

    $fns = $this->container->getController("struct_tools")->getFieldNamesExclusiveAdmin($this->container->getEntity($this->entityName));
    $sql = "
  INSERT INTO " . $this->container->getEntity($this->entityName)->sn_() . " (";    
    $sql .= implode(", ", $fns);    
    $sql .= ")
VALUES ( ";
    foreach($fns as $fn) $sql .= $row[$fn] . ", " ;
    $sql = substr($sql, 0, -2); //eliminar ultima coma
    $sql .= ");
";

    return $sql;
  }

  public function _update(array $row){
    $sql = "
UPDATE " . $this->container->getEntity($this->entityName)->sn_() . " SET
";   
    $fns = $this->container->getController("struct_tools")->getFieldNamesExclusiveAdmin($this->container->getEntity($this->entityName));
    foreach($fns as $fn) { if (isset($row[$fn] )) $sql .= $fn . " = " . $row[$fn] . ", " ; }
    $sql = substr($sql, 0, -2); //eliminar ultima coma

    return $sql;
  }

}
