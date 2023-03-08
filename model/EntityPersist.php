<?php

require_once("function/snake_case_to.php");
require_once("function/settypebool.php");

/**
 * SQL Object
 * Definir SQL para ser ejecutado directamente por el motor de base de datos
 */
class EntityPersist {
  
  public $container;
  public $entity_name;

 
  public function update(array $row) { //sql de actualizacio
    return "
{$this->_update($row)}
WHERE {$this->container->entity($this->entity_name)->getPk()->getName()} = {$row['id']};
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
    $r_ = $this->container->value($this->entity_name)->_fromArray($row, "set")->_toArray("sql");
    return "
{$this->_update($r_)}
WHERE {$this->container->entity($this->entity_name)->getPk()->getName()} IN ({$ids_});
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
DELETE FROM {$this->container->entity($this->entity_name)->sn_()}
WHERE id IN ({$ids_});
";
  }

  public function insert(array $row){
    /**
     * El conjunto de valores debe estar previamente formateado
     */

    $fns = $this->getFieldNamesAdmin();
    $sql = "
  INSERT INTO " . $this->container->entity($this->entity_name)->sn_() . " (";    
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
    $sn_ = $this->container->entity($this->entityName)->sn_();
    $sql = "
UPDATE " . $sn_ . " SET
";   
    $fns = $this->getFieldNamesAdmin();
    foreach($fns as $fn) { if (isset($row[$fn] )) $sql .= $fn . " = " . $row[$fn] . ", " ; }
    $sql = substr($sql, 0, -2); //eliminar ultima coma

    return $sql;
  }


  protected function formatIds(array $ids = []) {
    /**
     * Formato sql de ids
     */
    $ids_ = [];
    $value = $this->container->value($this->entity_name);
    for($i = 0; $i < count($ids); $i++) {
      $value->_set("id",$ids[$i]);
      array_push($ids_, $value->_sql("id"));
    }
    return implode(', ', $ids_);
  }
  

  protected function getFieldNamesAdmin(){ //pk, nf, fk
    $names = [];
    $entity = $this->container->entity($this->entity_name);
    foreach($entity->getFields() as $field) {
      if($field->isAdmin()) { 
        array_push($names, $field->getName());
      }
    }
    return $names;
  }

  
  
}
