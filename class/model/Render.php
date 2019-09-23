<?php

//Presentacion de datos
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

  public static function getInstanceArray(array $render = null){
    $r = new Render();
    
    if(!empty($display["size"])) $render->setSize($display["size"]);
    if(!empty($display["page"])) $render->setPage($display["page"]);
    if(!empty($display["order"])) $render->setOrder($display["order"]);
    if(!empty($display["search"])) $render->setSearch($display["search"]);
    if(!empty($display["condition"])) $render->setCondition($display["filters"]);
    if(!empty($display["params"])) $render->setParams($display["params"]);

    return $render;
  }



  public function setCondition (array $condition = null) { $this->condition = $condition; }

  public function addCondition ($condition = null) { if(!empty($condition)) array_push ( $this->condition, $condition ); }

  public function setParams (array $params = null) { foreach($params as $key => $value) array_push ( $this->condition, [$key, "=", $value] ); } //params es una forma corta de asignar filtros a traves de un array asociativo

  public function setGeneralCondition ($generalCondition = null) { $this->generalCondition = $generalCondition; }

  public function addGeneralCondition ($gc = null) { if(!empty($gc)) array_push ( $this->generalCondition, $gc ); }

  //Ordenamiento
  //@param array $order Ordenamiento
  //  array(
  //    nombre_field => asc | desc,
  //  )
  //@param array $orderDefault Ordenamiento por defecto.
  //  array(
  //    nombre_field => asc | desc,
  //  )
  //Dependiendo del motor de base de datos utilizado, puede requerirse que el campo utilizado en el ordenamiento sea incluido en los campos de la consulta
  public function setOrder (array $order) {
    $this->order = $order;
  }

  public function setPagination($size, $page) {
    $this->size = $size;
    $this->page = $page;
  }

  public function setSize($size) { $this->size = $size; }

  public function setPage($page) { $this->page = $page; }

  public function getSize(){ return $this->size; }

  public function getPage(){ return $this->page; }

  public function getCondition(){ return $this->condition; }

  public function getGeneralCondition(){ return $this->generalCondition; }

  public function getOrder(){ return $this->order; }

}
