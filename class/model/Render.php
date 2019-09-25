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

  public static function getInstanceDisplay(array $display = null){
    $className = get_called_class();
    $render = new $className;

    if(!empty($display["size"])) $render->setSize($display["size"]);
    if(!empty($display["page"])) $render->setPage($display["page"]);
    if(!empty($display["order"])) $render->setOrder($display["order"]);
    if(!empty($display["search"])) $render->setSearch($display["search"]);
    if(!empty($display["condition"])) $render->setCondition($display["filters"]);
    if(!empty($display["params"])) $render->setParams($display["params"]);

    return $render;
  }

  public static function getInstanceParams(array $params, $key = "display"){
    $data = null;

    //data es utilizado debido a la facilidad de comunicacion entre el cliente y el servidor. Se coloca todo el json directamente en una variable data que es convertida en el servidor.
    if(isset($params[$key])) {
      $data = $params[$key];
      unset($params[$key]);
    }

    $f_ = json_decode($data);
    $display = stdclass_to_array($f_);
    if(empty($display["size"])) $display["size"] = 100;
    if(empty($display["page"])) $display["page"] = 1;
    if(!isset($display["order"])) $display["order"] = [];
    if(!isset($display["condition"])) $display["condition"] = [];

    foreach($params as $key => $value) {
      /**
       * Los parametros fuera de display, se priorizan y reasignan a Display
       * Si los atributos son desconocidos se agregan como filtros
       */
      switch($key) {
        case "size": case "page": case "search": //pueden redefinirse ciertos parametros la prioridad la tiene los que estan fuera del elemento data (parametros definidos directamente)
          $display[$key] = $value;
        break;
        case "order": //ejemplo http://localhost/programacion/curso/all?order={%22horario%22:%22asc%22}
          $f_ = json_decode($value);
          $display["order"] = stdclass_to_array($f_); //ordenamiento ascendente (se puede definir ordenamiento ascendente de un solo campo indicandolo en el parametro order, ejemplo order=campo)
        break;


        default: array_push($display["condition"], [$key,"=",$params[$key]]);
      }
    }

    return self::getInstanceDisplay($display);
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
