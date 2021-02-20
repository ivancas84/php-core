<?php

class Render {

  public $container;
  public $entityName; //entidad principal a la que esta destinada la consulta
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
  public $size = 100;

  protected $fields = array(); //campos
  /**
   * Deben estar definidos en el mapping field, se realizará la traducción correspondiente
   * . indica aplicacion de funcion de agregacion
   * - indica que pertenece a una relacion
   * Ej ["nombres", "horas_catedra.sum", "edad.avg", "com_cur-horas_catedra]
   */

  protected $groupConcat = array(); //campos a los cuales se aplica group_concat
  /**
   * Deben estar definidos en el mapping field, se realizará la traducción correspondiente
   * . indica aplicacion de funcion de agregacion
   * - indica que pertenece a una relacion
   * Ej ["telefono", "nombres"], se traduce en  GROUP_CONCAT(DISTINCT telefono SEPARATOR ', ' ) AS telefono
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
    /**
     * Instanciar render a partir de un display
     * Importante: Define las condiciones y parametros como condiciones generales
     */
    $render = new Render;
    if(isset($display["size"])) $render->setSize($display["size"]);
    /**
     * puede ser 0 o false para indicar todas las filas
     */
    if(!empty($display["page"])) $render->setPage($display["page"]);
    if(!empty($display["order"])) $render->setOrder($display["order"]);
    if(!empty($display["condition"])) $render->setGeneralCondition($display["condition"]);
    if(!empty($display["params"])) $render->setGeneralParams($display["params"]);
    if(!empty($display["fields"])) $render->setFields($display["fields"]);
    if(!empty($display["group"])) $render->setGroup($display["group"]);
    if(!empty($display["having"])) $render->setHaving($display["having"]);

    return $render;
  }

  public static function getInstanceParams(array $params = null){
    $render = new Render;
    if(!empty($params)) $render->setParams($params);
    return $render;
  }

  public function setCondition (array $condition = null) { $this->condition = $condition; }
  public function addCondition ($condition = null) { 
    if(!empty($condition)) {
      array_push ( $this->condition, $condition );
    }
  }
  public function getCondition(){ return $this->condition; }
  
  public function setParams (array $params = []) {
    foreach($params as $key => $value) {
      $this->addCondition([$key, "=", $value]); 
    }
  } 

  
  public function setGeneralParams (array $params = []) {
    foreach($params as $key => $value) {
      $this->addGeneralCondition([$key, "=", $value]); 
    }
  } 
  
  //public function addParam ($key, $value) { $this->addCondition([$key, "=", $value]); }
  /**
   * este metodo permite ahorrar 5 caracteres, no se si es conveniente, setParams es valido porque facilita el envio de parametros
   * params es una forma corta de asignar condiciones a traves de un array asociativo
   * solo define campo y valor, siempre toma la opcion como "="
   */

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
  
  public function setFields (array $fields = null) { $this->fields = $fields; }
  public function addFields (array $fields = null) { $this->fields = array_unique(array_merge($this->fields, $fields)); }
  public function getFields () { return $this->fields; }

  public function setGroup (array $group = null) { $this->group = $group; }
  public function getGroup () { return $this->group; }

  public function setHaving (array $having = null) { $this->having = $having; }
  public function getHaving () { return $this->having; }

  public function setEntityName (array $entityName = null) { $this->entityName = $entityName; }
  public function getEntityName () { return $this->entityName; }

  public function addPrefixRecursive(array &$condition, $prefix){
    if(!key_exists(0, $condition)) return;
    if(is_array($condition[0])) {
      foreach($condition as &$value) $this->addPrefixRecursive($value,$prefix);  
    } else {
        $condition[0] = $prefix.$condition[0];
    }
  }

  public function addPrefix($prefix){
    $this->addPrefixRecursive($this->condition, $prefix);
    $this->addPrefixRecursive($this->generalCondition, $prefix);
    
    foreach($this->order as $k=>$v){
      $this->order[$prefix.$k] = $v;
      unset($this->order[$k]);
    }
  }

  public function removePrefixRecursive(array &$condition, $prefix){
    if(!key_exists(0, $condition)) return;
    if(is_array($condition[0])) {
      foreach($condition as &$value) $this->removePrefixRecursive($value,$prefix);  
    } else {
      $count = 1;
      $condition[0] = str_replace($prefix, '', $condition[0], $count);
    }
  }

  public function removePrefix($prefix){
    $this->removePrefixRecursive($this->condition, $prefix);
    $this->removePrefixRecursive($this->generalCondition, $prefix);
    
    foreach($this->order as $k=>$v){
      $count = 1;
      $newk = str_replace($prefix, '', $k, $count);
      $this->order[$newk] = $v;
      unset($this->order[$k]);
    }
  }

  public function setConditionUniqueFields(array $params){
    /**
     * definir condicion para campos unicos
     * $params:
     *   array("nombre_field" => "valor_field", ...)
     * los campos unicos simples se definen a traves del atributo Entity::$unique
     * los campos unicos multiples se definen a traves del atributo Entity::$uniqueMultiple
     */
    $uniqueFields = $this->container->getEntity($this->entityName)->getFieldsUnique();
    $uniqueFieldsMultiple = $this->container->getEntity($this->entityName)->getFieldsUniqueMultiple();

    $condition = array();
    $ret = false;

    foreach($uniqueFields as $field){
      foreach($params as $key => $value){
        if($key == "id" && empty($value)) continue; //para el id no se permiten valores nulos
        if($key == $field->getName()) {
          array_push($condition, [$key, "=", $value, "or"]);
        }
      }
    }

    if($uniqueFieldsMultiple) {
      $conditionMultiple = [];
      $first = true;
      foreach($uniqueFieldsMultiple as $field){
        foreach($params as $key => $value){
          if($key == $field->getName()) {
            if($first) {
              $con = "or";
              $first = false;
            } else {
              $con = "and";
            }
            array_push($conditionMultiple, [$key, "=", $value, $con]);
          }
        }
      }

      if(!empty($conditionMultiple)) {
        array_push($condition, $conditionMultiple);
        $ret = true;
      }
    }

    $this->addCondition($condition);
    return $ret;
  }
}
