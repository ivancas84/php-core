<?php

class Render {

  public $condition = array(); //array multiple cuya raiz es [field,option,value], 
  /**
   * ejemplo:  [
   *    ["nombre","=","unNombre"],
   *    [
   *      ["apellido","=","unApellido"],
   *      ["apellido","=","otroApellido","OR"]
   *    ]
   * ]
   */  
  public $generalCondition = array(); //condicion utilizada solo en la estructura general 
  public $order = array();
  public $page = 1;
  public $size = false;

  protected $aggregate = array(); //campos a los que se aplicara funciones de agregacion
  /**
   * Deben estar definidos en el método mapping field, se realizará la traducción correspondiente
   * Ej ["sum_horas_catedra", "avg_edad"]
   */

  protected $group = array(); //campos de agrupacion
  /**
   * Deben ser campos de consulta
   * Ej ["profesor", "cur_horario"]
   */

  protected $having = array(); //condicion avanzada de agrupamiento, similiar a condicion avanzadas
  /**
   * array multiple cuya raiz es [field,option,value], ejemplo: [["nombre","=","unNombre"],[["apellido","=","unApellido"],["apellido","=","otroApellido","OR"]]]
   */

  public static function getInstance($render = null){
    /**
     * @param String | Object | Array | Render En función del tipo de parámetro define el render
     * @return Render Clase de presentacion
     */
    if(gettype($render) == "object") return $render;

    $r = new Render();
    if(gettype($render) == "string") $r->setCondition(["_search","=~",$render]);
    elseif (gettype($render) == "array") $r->setCondition($render);
    return $r;
  }

  public static function getInstanceDisplay(array $display = null){
    $className = get_called_class();
    $render = new $className;

    if(!empty($display["size"])) $render->setSize($display["size"]);
    if(!empty($display["page"])) $render->setPage($display["page"]);
    if(!empty($display["order"])) $render->setOrder($display["order"]);
    if(!empty($display["condition"])) $render->setCondition($display["condition"]);
    if(!empty($display["params"])) $render->setParams($display["params"]);

    return $render;
  }

  public function setCondition (array $condition = null) { $this->condition = $condition; }
  public function addCondition ($condition = null) { if(!empty($condition)) array_push ( $this->condition, $condition ); }
  public function getCondition(){ return $this->condition; }
  public function setParams (array $params = null) { foreach($params as $key => $value) array_push ( $this->condition, [$key, "=", $value] ); } //params es una forma corta de asignar filtros a traves de un array asociativo

  public function setGeneralCondition ($generalCondition = null) { $this->generalCondition = $generalCondition; }
  public function addGeneralCondition ($gc = null) { if(!empty($gc)) array_push ( $this->generalCondition, $gc ); }
  public function getGeneralCondition(){ return $this->generalCondition; }

  public function setOrder (array $order) { $this->order = $order; }
  /**
   * Ordenamiento
   * @param array $order Ordenamiento
   *  array(
   *    nombre_field => asc | desc,
   *  )
   */
  public function getOrder(){ return $this->order; }

  public function setPagination($size, $page) {
    $this->size = $size;
    $this->page = $page;
  }

  public function setSize($size) { $this->size = $size; }
  public function getSize(){ return $this->size; }

  public function setPage($page) { $this->page = $page; }
  public function getPage(){ return $this->page; }
  
  public function setAggregate (array $aggregate = null) { $this->aggregate = $aggregate; }
  public function getAggregate () { return $this->aggregate; }

  public function setGroup (array $group = null) { $this->group = $group; }
  public function getGroup () { return $this->group; }

  public function setHaving (array $having = null) { $this->having = $having; }
  public function getHaving () { return $this->having; }

}
