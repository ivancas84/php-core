<?php

require_once("function/snake_case_to.php");
require_once("class/model/Render.php");
require_once("function/settypebool.php");
require_once("function/get_entity_rel.php");


class EntitySqlo { //2
  /**
   * SQL Object
   * Definir SQL para ser ejecutado directamente por el motor de base de datos
   */

  public $container;
  public $entityName;

  protected function mapping($fieldName){
     /**
     * Interpretar prefijo y obtener mapping
     */
    $f = explode("-",$fieldName);
    if(count($f) == 2) {
      $prefix = $f[0];
      $entityName = get_entity_rel($this->entityName)[$f[0]]["entity_name"];
      $mapping = $this->container->getMapping($entityName, $prefix);
      $fieldName = $f[1];
    } else { 
      $mapping = $this->container->getMapping($this->entityName);
    }

    return [$mapping,$fieldName];
  }
  
  protected function fieldsQuery(Render $render){
    $fields = array_merge($render->getGroup(), $render->getFields());

    $fieldsQuery_ = [];
    foreach($fields as $key => $fieldName){
      if(is_array($fieldName)){
        if(is_integer($key)) throw new Exception("Debe definirse un alias para la concatenacion (key must be string)");
        $map_ = [];
        foreach($fieldName as $fn){
          $map = $this->mapping($fn);
          array_push($map_, $map[0]->_($map[1]));
        } 
        $f = "CONCAT_WS(', ', " . implode(",",$map_) . ") AS " . $key;
      } else {
        $map = $this->mapping($fieldName);
        $alias = (is_integer($key)) ? $map[0]->_pf() . str_replace(".","_",$map[1]) : $key;
        $f = $map[0]->_($map[1]) . " AS " . $alias;
      }
      array_push($fieldsQuery_, $f);
    }

    return implode(', ', $fieldsQuery_);
  }

  protected function groupBy(Render $render){
    $fields = $render->getGroup();

    $group_ = [];
    foreach($fields as $key => $fieldName){
      if(is_array($fieldName)){
        if(is_integer($key)) throw new Exception("Debe definirse un alias para la concatenacion (key must be string)");
        $f = $key;
      } else {
        $map = $this->mapping($fieldName);
        $f = (is_integer($key)) ? $map[0]->_pf() . str_replace(".","_",$map[1]) : $key;
      }
      array_push($group_, $f);
    }

    return empty($group_) ? "" : "GROUP BY " . implode(", ", $group_) . "
";

  }


  public function select(Render $render) {
    $fieldsQuery = $this->fieldsQuery($render);
    $group = $this->groupBy($render);
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
     * @return string sql de actualizacion
     */
    if(empty($ids)) throw new Exception("No existen identificadores definidos");
    $ids_ = $this->container->getSql($this->entityName)->formatIds($ids);
    $r_ = $this->container->getValue($this->entityName)->_fromArray($row, "set")->_toArray("sql");
    return "
{$this->_update($r_)}
WHERE {$this->container->getEntity($this->entityName)->getPk()->getName()} IN ({$ids_});
";
  }

  public function delete(array $ids) { 
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
