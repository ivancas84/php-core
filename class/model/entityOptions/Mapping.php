<?php

require_once("class/model/entityOptions/EntityOptions.php");

class MappingEntityOptions extends EntityOptions {

  public function count(){ return "COUNT(*)"; }
  
  public function identifier(){ 
    if(empty($this->entity->getIdentifier())) throw new Exception ("Identificador no definido en la entidad ". $this->entity->getName()); 
    $identifier = [];
    foreach($this->entity->getIdentifier() as $id) array_push($identifier, $this->id());
    return "CONCAT_WS(\"". UNDEFINED . "\"," . implode(",", $identifier) . ")
";
  }

  public function label(){
    return $this->container->getMappingLabel($this->entityName, $this->prefix)->main();
  }

  public function search(){
    $fields = $this->container->getEntity($this->entityName)->nf;
    array_walk($fields, function(&$field) { $field = $this->container->getMapping($this->entityName, $this->prefix)->_($field); });
    return "CONCAT_WS(' ', " . implode(",", $fields). ")";
  }

  public function _($fieldName, array $params = []){
    /**
     * @example 
     *   _("nombre")
     *   _("fecha_alta.max");
     *   _("edad.avg")
     */
    $m = snake_case_to("xxYy", $fieldName);
    if(method_exists($this, $m)) return call_user_func_array(array($this, $m), $params);

    $p = explode(".",$fieldName);
    $m = (count($p) == 1) ? "_default" : "_".$p[1];
    return call_user_func_array(array($this, $m), [$p[0]]); 
  }

  public function _default($field){ return $this->_pt() . "." . $field; }
  public function _date($field) { return "CAST({$this->_pt()}.{$field} AS DATE)"; }
  public function _ym($field) { return "DATE_FORMAT({$this->_pt()}.{$field}, '%Y-%m')"; }
  public function _y($field) { return "DATE_FORMAT({$this->_pt()}.{$field}, '%Y')"; }
  public function _avg($field) { return "AVG({$this->_pt()}.{$field})"; }
  public function _min($field) { return "MIN({$this->_pt()}.{$field})"; }
  public function _max($field) { return "MAX({$this->_pt()}.{$field})"; }
  public function _sum($field) { return "SUM({$this->_pt()}.{$field})"; }
  public function _count($field) { return "COUNT({$this->_pt()}.{$field})"; }
  public function _exists($field) { return $this->_default($field); }

 

}


