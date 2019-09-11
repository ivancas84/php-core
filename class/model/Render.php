<?php

//Presentacion de datos
class Render {

  public $condition; //array multiple cuya raiz es [field,option,value], 
  /**
   * ejemplo:  [
   *    ["nombre","=","unNombre"],
   *    [
   *      ["apellido","=","unApellido"],
   *      ["apellido","=","otroApellido","OR"]
   *    ]
   * ]
   */  
  public $generalCondition; //condicion utilizada solo en la estructura general 
  public $order;
  public $page;
  public $size;

  public function __construct() {
    $this->condition = array();
    $this->generalCondition = array();
    $this->order = array();
    $this->page = 1;
    $this->size = false; //si es false o 0 se incluyen todas las paginas, no se define tamanio
  }

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
