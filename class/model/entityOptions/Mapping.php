<?php

require_once("class/model/entityOptions/EntityOptions.php");

class MappingEntityOptions extends EntityOptions {
  /**
   * Ejemplo redefinicion: Ruta class/mapping/Comision.php
   * 
   * require_once("class/model/entityOptions/Mapping.php");
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
    $entity = $this->container->getEntity($this->entityName);  
    if(empty($entity->getIdentifier())) throw new Exception ("Identificador no definido en la entidad ". $this->container->getEntity($this->entityName)->getName()); 
    $identifier = [];
    foreach($entity->getIdentifier() as $id) array_push($identifier, $this->container->getRel($this->entityName, $this->prefix)->mapping($id));
    return "CONCAT_WS(\"". UNDEFINED . "\"," . implode(",", $identifier) . ")
";
  }

  public function label(){
    return $this->container->getControllerEntity("mapping_label", $this->entityName)->main($this->prefix);
  }

  public function search(){
    $fields = $this->container->getEntity($this->entityName)->nf;
    array_walk($fields, function(&$field) { $field = $this->container->getMapping($this->entityName, $this->prefix)->_($field); });
    return "CONCAT_WS(' ', " . implode(",", $fields). ")";
  }

  public function _($fieldName, array $params = []){
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
    $m = snake_case_to("xxYy", str_replace(".","_",$fieldName));
    if(method_exists($this, $m)) return call_user_func_array(array($this, $m), $params);

    $p = explode(".",$fieldName);
    $m = (count($p) == 1) ? "_default" : "_".snake_case_to("xxYy", $p[1]);
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
  public function _isSet($field) { return $this->_exists($field); }
  public function _groupConcat($field) { return "GROUP_CONCAT(DISTINCT " . $this->_($field) . " SEPARATOR ', ')"; }

}

