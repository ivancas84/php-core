<?php

require_once("function/snake_case_to.php");
require_once("function/concat.php");
require_once("function/settypebool.php");

/**
 * Se define el prefijo _ para indicar que el metodo no define relaciones
 * Los métodos _  habitulamente utilizan el atributo prefix para poder relacionarse con otras entidades
 * Los métodos no _ habitualmente accede a otras entidades para definir codigo
 */
class EntitySql { //Definir SQL
  /**
   * Facilitar la definición de SQL
   * Definir una serie de metodos que son de uso comun para todas las consultas
   * Algunos métodos que requieren una conexion abierta a la base de datos, como por ejemplo "escapar caracteres"
   */

  public $prefix = '';
  /**
   * Prefijo de identificacion
   */

  public $container;
  public $entityName;
    
  public function prf(){ return (empty($this->prefix)) ?  ''  : $this->prefix . '_'; }   //prefijo fields
  public function prt(){ return (empty($this->prefix)) ?  $this->container->getEntity($this->entityName)->getAlias() : $this->prefix; } //prefijo tabla

  public function formatIds(array $ids = []) {
    /**
     * Formato sql de ids
     */
    $ids_ = [];
    for($i = 0; $i < count($ids); $i++) {
      $value = $this->container->getValue($this->entityName);
      $value->setId($ids[$i]);
      array_push($ids_, $value->sqlId());
    }
    return implode(', ', $ids_);
  }

  
  public function condition(Render $render) { 
    /**
     * busqueda avanzada considerando relaciones
     */
    $condition = array_merge($render->condition, $render->generalCondition);

    /**
     * Array $advanced:
     *    [
     *    0 => "field"
     *    1 => "=", "!=", ">=", "<=", "<", ">", "=="
     *    2 => "value" array|string|int|boolean|date (si es null no se define busqueda, si es un array se definen tantas busquedas como elementos tenga el array)
     *    3 => "AND" | "OR" | null (opcional, por defecto AND)
     *  ]
     *  Array(
     *    Array("field" => "field", "value" => array|string|int|boolean|date (si es null no se define busqueda, si es un array se definen tantas busquedas como elementos tenga el array) [, "option" => "="|"=~"|"!="|"<"|"<="|">"|">="|true (no nulos)|false (nulos)][, "mode" => "and"|"or"]
     *    ...
     *  )
     *  )
     */
    if(empty($condition)) return "";
    $conditionMode = $this->conditionRecursive($condition);
    return $conditionMode["condition"];
  }

  public function _condition(Render $render) {
    /**
     * Busqueda avanzada sin considerar relaciones
     * A diferencia del metodo que recorre relaciones, _condition no genera error si la condicion no existe
     */
    if(empty($render->getCondition())) return "";
    $conditionMode = $this->_conditionRecursive($render->getCondition());
    if (empty($conditionMode)) return "";
    return $conditionMode["condition"];
  }

  private function conditionRecursive(array $condition){
    /**
     * Metodo recursivo para definir condiciones avanzada (considera relaciones)
     * Para facilitar la definicion de condiciones, retorna un array con dos elementos:
     * "condition": SQL
     * "mode": Concatenacion de condiciones "AND" | "OR"
     */

    if(is_array($condition[0])) return $this->conditionIterable($condition);
    /**
     * si en la posicion 0 es un string significa que es un campo a buscar, caso contrario es un nuevo conjunto (array) de campos que debe ser recorrido
     */

    $option = (empty($condition[1])) ? "=" : $condition[1]; //por defecto se define "="
    $value = (!isset($condition[2])) ? null : $condition[2]; //hay opciones de configuracion que pueden no definir valores
    /**
     * No usar empty, puede definirse el valor false
     */
    $mode = (empty($condition[3])) ? "AND" : $condition[3];  //el modo indica la concatenacion con la opcion precedente, se usa en un mismo conjunto (array) de opciones

    $condicion = $this->conditionField($condition[0], $option, $value);
    /**
     * El campo de identificacion del array posicion 0 no debe repetirse en las condiciones no estructuradas y las condiciones estructuras
     * Se recomienda utilizar un sufijo por ejemplo "_" para distinguirlas mas facilmente
     */
    return ["condition" => $condicion, "mode" => $mode];
  }

  private function _conditionRecursive(array $advanced){
    /**
     * Metodo recursivo para definir condicines avanzadas (no considera relaciones)
     * Para facilitar la definicion de condiciones, retorna un array con dos elementos:
     * "condition": SQL
     * "mode": Concatenacion de condiciones "AND" | "OR"
     */
    if(is_array($advanced[0])) return $this->_conditionIterable($advanced);
    /**
     * si en la posicion 0 es un string significa que es un campo a buscar, caso contrario es un nuevo conjunto (array) de campos que debe ser recorrido
     */

    $option = (empty($advanced[1])) ? "=" : $advanced[1]; //por defecto se define "="
    $value = (!isset($advanced[2])) ? null : $advanced[2]; //hay opciones de configuracion que pueden no definir valores
    /**
     * No usar empty, puede definirse el valor false
     */
    $mode = (empty($advanced[3])) ? "AND" : $advanced[3];  //el modo indica la concatenacion con la opcion precedente, se usa en un mismo conjunto (array) de opciones

    $condicion = $this->_conditionField($advanced[0], $option, $value);
    /**
     * El campo de identificacion del array posicion 0 no debe repetirse en las condiciones no estructuradas y las condiciones estructuras
     * Se recomienda utilizar un sufijo por ejemplo "_" para distinguirlas mas facilmente
     */
    
    if(empty($condicion)) return "";
    return ["condition" => $condicion, "mode" => $mode];
  }

  private function conditionIterable(array $advanced) { 
    /**
     * metodo de iteracion para definir condiciones (considera relaciones)
     */
    $conditionModes = array();

    for($i = 0; $i < count($advanced); $i++){
      $conditionMode = $this->conditionRecursive($advanced[$i]);
      array_push($conditionModes, $conditionMode);
    }

    $modeReturn = $conditionModes[0]["mode"];
    $condition = "";

    foreach($conditionModes as $cm){
      $mode = $cm["mode"];
      if(!empty($condition)) $condition .= $mode . " ";
      $condition.= $cm["condition"];
    }

    return ["condition"=>"(".$condition.")", "mode"=>$modeReturn];
  }

  private function _conditionIterable(array $advanced) {
    /**
     * metodo de iteracion para definir condiciones avanzadas (no considera relaciones)
     */
    $conditionModes = array();

    for($i = 0; $i < count($advanced); $i++){
      $conditionMode = $this->_conditionRecursive($advanced[$i]);
      if(empty($conditionMode)) continue;
      array_push($conditionModes, $conditionMode);
    }

    if(empty($conditionModes)) return "";

    $condition = "";
    foreach($conditionModes as $cm){
      if(empty($cm)) continue;
      $modeReturn = $cm["mode"];
      break;
    }

    foreach($conditionModes as $cm){
      if(empty($cm)) continue;
      $mode = $cm["mode"];
      if(!empty($condition)) $condition .= $mode . " ";
      $condition.= $cm["condition"];
    }

    return ["condition"=>"(".$condition.")", "mode"=>$modeReturn];
  }

  protected function conditionField($field, $option, $value){
    /**
     * se verifica inicialmente la condicion auxiliar. 
     * las condiciones auxiliares no siguen la estructura definida de condicion
     */    
    $condition = $this->container->getRel($this->entityName)->conditionAux($field, $option, $value);
    if($condition) return $condition;
    
    if(!is_array($value)) {      
      $condition = $this->container->getRel($this->entityName)->condition($field, $option, $value);
      if(!$condition) throw new Exception("No pudo definirse el SQL de la condicion del campo: {$this->entityName}.{$field}");
      return $condition;
    }

    $condition = "";
    $cond = false;

    foreach($value as $v){
      if($cond) {
        if($option == "=") $condition .= " OR ";
        elseif($option == "!=") $condition .= " AND ";
        else throw new Exception("Error al definir opción");
      } else $cond = true;

      $condition_ = $this->conditionField($field, $option, $v);
      $condition .= $condition_;
    }

    return "(".$condition.")";
  }

  protected function _conditionField($field, $option, $value) {
    /**
     * se verifica inicialmente la condicion auxiliar
     * las condiciones auxiliares no siguen la estructura definida de condicion
     */
    $condition = $this->container->getConditionAux($this->entityName)->_eval($field, [$option, $value]);
    if($condition) return $condition;
    
    if(!is_array($value)) {
      $condition = $this->container->getCondition($this->entityName)->_eval($field, [$option, $value]);
      return $condition;
    }

    $condition = "";
    $cond = false;

    foreach($value as $v){
      if($cond) {
        if($option == "=") $condition .= " OR ";
        elseif($option == "!=") $condition .= " AND ";
        else throw new Exception("Error al definir opción");
      } else $cond = true;

      $condition_ = $this->container->getCondition($this->entityName)->_eval($field, [$option, $v]);
      if(!$condition_) return "";
      $condition .= $condition_;
    }

    if(empty($condition)) return "";
    return "(".$condition.")";
  }
  
  
  public function from(){
    return " FROM " . $this->container->getEntity($this->entityName)->sna_() . "
";
  }

  public function fromSubSql(Render $render){
    $t = $this->prt();    
    return " FROM 


" . $this->_subSql($render) . "


 AS {$t}
";
  }

  public function _from(){
    $t = $this->prt();    
    return " FROM " . $this->container->getEntity($this->entityName)->sn_() . " AS {$t}
";
  }

  public function limit($page = 1, $size = false){
    if ($size) {
      return " LIMIT {$size} OFFSET " . ( ($page - 1) * $size ) . "
";
    }
    return "";
  }

  public function conditionUniqueFields(array $params){
    /**
     * definir condicion para campos unicos
     * $params:
     *   array("nombre_field" => "valor_field", ...)
     * los campos unicos simples se definen a traves del atributo Field::$unique
     * los campos unicos multiples se definen a traves del meotodo Entity::getFieldsUniqueMultiple();
     */
    $uniqueFields = $this->container->getEntity($this->entityName)->getFieldsUnique();
    $uniqueFieldsMultiple = $this->container->getEntity($this->entityName)->getFieldsUniqueMultiple();

    $condition = array();

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

      if(!empty($conditionMultiple)) array_push($condition, $conditionMultiple);
    }

    $render = new Render();
    $render->setCondition($condition);
    return $this->condition($render);
  }

  public function _join($field, $fromTable, Render $render){
    /**
     * Definir relacion como subconsulta
     * En funcion del campo pasado como parametro define una relacion
     * Por defecto define una relacion simple utilizando LEFT JOIN
     * Este método puede ser sobrescrito para dar soporte a campos derivados
     */
    $t = $this->prt();
    return "LEFT OUTER JOIN 


      " . $this->_subSql($render) . "


 AS $t ON ($fromTable.$field = $t.{$this->container->getEntity($this->entityName)->getPk()->getName()})
";
  }

  protected function orderDefault(){
    /**
     * Ordenamiento por defecto
     * por defecto se definen los campos principales nf de la tabla principal
     * Si se incluyen campos de relaciones, asegurarse de incluir las relaciones
     */
    $e = $this->container->getEntity($this->entityName);
    if(!empty($of = $e->getOrderDefault())) return $of;
        
    $fieldsMain = $e->main;
    return array_fill_keys($fieldsMain, "asc");
  }

  public function orderBy(array $order = null){
    $order = $this->initOrder($order);
    return $this->order($order);
  }

  protected function initOrder(array $order) {
    $orderDefault = $this->orderDefault();
    foreach($order as $key => $value){
      if(array_key_exists($key, $orderDefault)){
        unset($orderDefault[$key]);
      }
    }

    return array_merge($order, $orderDefault);
  }

  protected function order(array $order = null){
    $sql = '';

    foreach($order as $key => $value){
      $value = ((strtolower($value) == "asc") || ($value === true)) ? "asc" : "desc";
      $sql_ = "{$this->container->getRel($this->entityName)->mapping($key)} IS NULL, {$this->container->getRel($this->entityName)->mapping($key)} {$value}";
      $sql .= concat($sql_, ', ', ' ORDER BY', $sql);
    }

    return $sql;
  }

  public function having($render) { //busqueda avanzada
    $condition = $render->getHaving();
    /**
     * Array $advanced:
     *  [
     *    0 => "field"
     *    1 => "=", "!=", ">=", "<=", "<", ">", "=="
     *    2 => "value" array|string|int|boolean|date (si es null no se define busqueda, si es un array se definen tantas busquedas como elementos tenga el array)
     *    3 => "AND" | "OR" | null (opcional, por defecto AND)
     *  ]
     *  Array(
     *    Array("field" => "field", "value" => array|string|int|boolean|date (si es null no se define busqueda, si es un array se definen tantas busquedas como elementos tenga el array) [, "option" => "="|"=~"|"!="|"<"|"<="|">"|">="|true (no nulos)|false (nulos)][, "mode" => "and"|"or"]
     *    ...
     *  )
     *  )
     */
    if(empty($condition)) return "";
    $conditionMode = $this->conditionRecursive($condition);
    return $conditionMode["condition"];
  }

  public function _having($render) { //busqueda avanzada
    $condition = $render->getHaving();
    /**
     * Array $advanced:
     *  [
     *    0 => "field"
     *    1 => "=", "!=", ">=", "<=", "<", ">", "=="
     *    2 => "value" array|string|int|boolean|date (si es null no se define busqueda, si es un array se definen tantas busquedas como elementos tenga el array)
     *    3 => "AND" | "OR" | null (opcional, por defecto AND)
     *  ]
     *  Array(
     *    Array("field" => "field", "value" => array|string|int|boolean|date (si es null no se define busqueda, si es un array se definen tantas busquedas como elementos tenga el array) [, "option" => "="|"=~"|"!="|"<"|"<="|">"|">="|true (no nulos)|false (nulos)][, "mode" => "and"|"or"]
     *    ...
     *  )
     *  )
     */
    if(empty($having)) return "";
    $conditionMode = $this->_conditionRecursive($condition);
    return $conditionMode["condition"];
  }


  public function _subSql(Render $render){
    return $this->container->getEntity($this->entityName)->sn_();
 
 /*
 $fieldNamesExclusive = StructTools::getFieldNamesExclusive();
 $fields = implode(",", $this->container->getFieldAlias($this->entityName, $this->prefix)->_toArrayFields($fieldNamesExclusive);
 return "( SELECT DISTINCT
{$fields}
{$this->_from($render)}
" . concat($this->_condition($render), 'WHERE ') . ")
";*/
  }
}