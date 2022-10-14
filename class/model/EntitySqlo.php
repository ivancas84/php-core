<?php

require_once("function/snake_case_to.php");
require_once("function/settypebool.php");

/**
 * SQL Object
 * Definir SQL para ser ejecutado directamente por el motor de base de datos
 */
class EntitySqlo {
  
  public $container;
  public $entityName;

  public function select(EntityRender $render) {
    $fieldsQuery = $this->fieldsQuery($render);
    $group = $this->groupBy($render);
    $having = $this->container->getControllerEntity("sql_condition", $this->entityName)->main($render->getHaving());    
    $condition = $this->container->getControllerEntity("sql_condition", $this->entityName)->main($render->condition);
    $order = $this->container->getControllerEntity("sql_order", $this->entityName)->main($render->getOrder());

    $sql = "SELECT DISTINCT
{$fieldsQuery}
{$this->from()}
{$this->join()}
" . concat($condition, 'WHERE ') . "
{$group}
" . concat($having, 'HAVING ') . "
{$order}
{$this->limit($render->getPage(), $render->getSize())}
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
    $ids_ = $this->formatIds($ids);
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
    $ids_ = $this->formatIds($ids);
    return "
DELETE FROM {$this->container->getEntity($this->entityName)->sn_()}
WHERE id IN ({$ids_});
";
  }

  public function insert(array $row){
    /**
     * El conjunto de valores debe estar previamente formateado
     */

    $fns = $this->container->getController("struct_tools")->getFieldNamesAdmin($this->container->getEntity($this->entityName));
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
    $fns = $this->container->getController("struct_tools")->getFieldNamesAdmin($this->container->getEntity($this->entityName));
    foreach($fns as $fn) { if (isset($row[$fn] )) $sql .= $fn . " = " . $row[$fn] . ", " ; }
    $sql = substr($sql, 0, -2); //eliminar ultima coma

    return $sql;
  }


  protected function formatIds(array $ids = []) {
    /**
     * Formato sql de ids
     */
    $ids_ = [];
    $value = $this->container->getValue($this->entityName);
    for($i = 0; $i < count($ids); $i++) {
      $value->_set("id",$ids[$i]);
      array_push($ids_, $value->_sql("id"));
    }
    return implode(', ', $ids_);
  }
  

  public function mapping($fieldName, $prefix = ""){
    /**
     * Traducir campo para ser interpretado correctamente por el SQL
     */
    $map = $this->_mapping($fieldName, $prefix);
    return $map[0]->_($map[1]);
  }

  protected function _mapping($fieldName, $prefix = ""){
     /**
     * Interpretar prefijo y obtener mapping
     */
    $f = explode("-",$fieldName);
    if(count($f) == 2) {
      $prefix_ = (empty($this->prefix)) ? $f[0] : $prefix . "_" . $f[0];
      $entityName = $this->container->getEntityRelations($this->entityName)[$f[0]]["entity_name"];
      $mapping = $this->container->getMapping($entityName, $prefix_);
      $fieldName = $f[1];
    } else { 
      $mapping = $this->container->getMapping($this->entityName, $prefix);
    }

    return [$mapping,$fieldName];
  }
  
  protected function fieldsQuery(EntityRender $render){
    $fields = array_merge($render->getGroup(), $render->getFields());

    $fieldsQuery_ = [];
    foreach($fields as $key => $fieldName){
      if(is_array($fieldName)){
        if(is_integer($key)) throw new Exception("Debe definirse un alias para la concatenacion (key must be string)");
        $map_ = [];
        foreach($fieldName as $fn){
          array_push($map_, $this->mapping($fn));
        } 
        $f = "CONCAT_WS(', ', " . implode(",",$map_) . ") AS " . $key;
      } else {
        $map = $this->_mapping($fieldName);
        $alias = (is_integer($key)) ? $map[0]->_pf() . str_replace(".","_",$map[1]) : $key;
        $f = $map[0]->_($map[1]) . " AS " . $alias;
      }
      array_push($fieldsQuery_, $f);
    }

    return implode(', ', $fieldsQuery_);
  }

  protected function groupBy(EntityRender $render){
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

  protected function join(){
    $sql = "";
    $tree = $this->container->getEntityTree($this->entityName);
    $this->joinFk($tree, "", $sql);
    return $sql;
  }

  protected function joinfk(array $tree, $tablePrefix, &$sql){
    if (empty ($tablePrefix)) $tablePrefix = $this->container->getEntity($this->entityName)->getAlias();

    foreach ($tree as $prefix => $value) {      
      $entitySn =  $this->container->getEntity($value["field_name"])->sn_();
      $sql .= $this->_join($entitySn, $value["field_name"], $tablePrefix, $prefix) . "
";

      if(!empty($value["children"])) $this->joinfk($value["children"], $prefix, $sql);
    }
  }

  protected function limit($page = 1, $size = false){
    if ($size) {
      return " LIMIT {$size} OFFSET " . ( ($page - 1) * $size ) . "
";
    }
    return "";
  }

  protected function from(){    
    return " FROM 

" . $this->container->getEntity($this->entityName)->sn_() . "

 AS {$this->container->getEntity($this->entityName)->getAlias()}
";
  }

  /**
   * Definir SQL de relacion 
   */
  protected function _join($entitySn, $field, $fromTable, $table){
    return "LEFT OUTER JOIN " . $entitySn . " AS $table ON ($fromTable.$field = $table.id)
";
  }

}
