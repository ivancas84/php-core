<?php

require_once("model/entityOptions/EntityOptions.php");

class MappingEntityOptions extends EntityOptions {
  /**
   * Ejemplo redefinicion: Ruta mapping/Comision.php
   * 
   * require_once("model/entityOptions/Mapping.php");
   * 
   * class ComisionMapping extends MappingEntityOptions{
   *   public function numero() {
   *     return "CONCAT({$this->_pf()}sed.numero, {$this->_pt()}.division)
   * ";
   *   }
   * }
   */

  public function count(){ return "COUNT(*)"; }
  
  public function identifier(){
    /**
     * Concatenacion de campos que permiten identificar univocamente a la en-
     * tidad.
     * 
     * Pueden ser campos de relaciones.
     */
    $entity = $this->container->entity($this->entity_name);  
    if(empty($entity->getIdentifier())) throw new Exception ("Identificador no definido en la entidad ". $this->container->entity($this->entity_name)->getName()); 
    $identifier = [];
    foreach($entity->getIdentifier() as $identifierElement) {
      $f = $this->container->explode_field($this->entity_name, $identifierElement);
      array_push($identifier, $this->container->mapping($f["entity_name"], $f["field_id"])->map($f["field_name"]));
    }
    return "CONCAT_WS(\"". UNDEFINED . "\"," . implode(",", $identifier) . ")
";
  }

  public function label(){
    $fieldsLabel = [];

    $entity = $this->container->entity($this->entity_name);

    $tree = $this->container->tree($this->entity_name);

    foreach($entity->getFieldsNf() as $field){
      if($field->isMain()) array_push($fieldsLabel, $field->getName());
    }      

    foreach($tree as $fieldId => $subtree){
      if($this->container->field_by_id($this->entity_name, $fieldId)->isMain()) $this->recursiveLabel($fieldId, $subtree, $fieldsLabel);
    }
        
    array_walk($fieldsLabel, function(&$field) { 
      $f = $this->container->explode_field($this->entity_name, $field);
      $field = $this->container->mapping($f["entity_name"], $f["field_id"])->_($f["field_name"]);
    });

    return "CONCAT_WS(' ', " . implode(",", $fieldsLabel). ")";
  }

  protected function recursiveLabel(string $key, array $tree, array &$fieldsLabel){
    $entity = $this->container->entity($tree["entity_name"]);
    
    foreach($entity->getFieldsNf() as $field){
      if($field->isMain()) array_push($fieldsLabel, $key."-".$field->getName());
    }      
    
    foreach($tree["children"] as $fieldId => $subtree){
      if($this->container->field_by_id($entity->getName(), $fieldId)->isMain()) $this->recursiveLabel($fieldId, $subtree, $fieldsLabel);
    }

  }

  public function search(){
    $fields = $this->container->entity($this->entity_name)->nf;
    array_walk($fields, function(&$field) { $field = $this->container->mapping($this->entity_name, $this->prefix)->map($field); });
    return "CONCAT_WS(' ', " . implode(",", $fields). ")";
  }

    /**
     * @deprecated
     * @todo Modificar uso de "_" por "map"
     */
    public function _($field_name, array $params = []){
        return $this->map($field_name, $params);
    }

  public function map($field_name, array $params = []){
    /**
     * Metodo principal de mapping
     * 
     * Verifica la existencia de un metodo eclusivo, si no exite, busca metodo
     * predefinido.
     * 
     * Permite la aplicacion de varios mapping utilizando el caracter "." como
     * separador
     *  
     * @example 
     *   _("nombre")
     *   _("fecha_alta.max.y"); //aplicar max y dar formato y
     *   _("edad.avg")
     */    
    $m = str_replace(".","_",$field_name);
    if(method_exists($this, $m)) return call_user_func_array(array($this, $m), $params);

    $p = explode(".",$field_name);
    $m = (count($p) == 1) ? "_default" : "_". $p[1];
    return call_user_func_array(array($this, $m), [$p[0]]); 
  }

  public function _default($field){ return $this->_pt() . "." . $field; }
  public function _date($field) { return "CAST(" . $this->_($field) . " AS DATE)"; }
  public function _ym($field) { return "DATE_FORMAT(" . $this->_($field) . ", '%Y-%m')"; }
  public function _y($field) { return "DATE_FORMAT(" . $this->_($field) . ", '%Y')"; }
  public function _avg($field) { return "AVG(" . $this->_($field) . ")"; }
  public function _min($field) { return "MIN(" . $this->_($field) . ")"; }
  public function _max($field) { return "MAX(" . $this->_($field) . ")"; }
  public function _sum($field) { return "SUM(" . $this->_($field) . ")"; }
  public function _count($field) { return "COUNT(DISTINCT " . $this->_($field) . ")"; }
  public function _exists($field) { return $this->_default($field); }
  public function _is_set($field) { return $this->_exists($field); }
  public function _str_agg($field) { return "GROUP_CONCAT(DISTINCT " . $this->_($field) . " SEPARATOR ', ')"; }

}

