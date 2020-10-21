<?php

require_once("class/tools/EntityRecursiveFk.php");

class MappingLabelEntityOptions extends EntityRecursiveFk {

  public $entityName;
  public $container;
  public $prefix = "";
  protected $fields = [];
  public function _pf(){ return (empty($this->prefix)) ?  ''  : $this->prefix . '_'; } 
  /**
   * prefijo fields
   */
  
  public function _pt(){ return (empty($this->prefix)) ?  $this->container->getEntity($this->entityName)->getAlias() : $this->prefix; }
  /**
   * prefijo tabla
   */


  public function main(){
    $entity = $this->container->getEntity($this->entityName);
    $this->body($entity);
    $this->recursive($entity);
    array_walk($this->fields, function(&$field) { 
      $field =  $this->container->getMapping($this->entityName, $this->prefix)->_($field); });

    return "CONCAT_WS(' ', " . implode(",", $this->fields). ")";
  }

  protected function body(Entity $entity, $prefix = ""){
    if(!empty($prefix)) $prefix += "-";
    foreach($entity->getFields() as $field){
      if($field->isMain()) array_push($this->fields, $prefix.$field->getName());
    }      
  }

  public function fk(Entity $entity, array $tablesVisited, $prefix){
    $fk = $entity->getFieldsFkNotReferenced($tablesVisited);
    $prf = (empty($prefix)) ? "" : $prefix . "_";
    array_push($tablesVisited, $entity->getName());

    foreach($fk as $field){
      if($field->isMain()) $this->recursive($field->getEntityRef(), $tablesVisited, $prf . $field->getAlias());
    }
  }


}
