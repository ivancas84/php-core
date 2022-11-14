<?php

require_once("function/snake_case_to.php");
require_once("function/concat.php");
require_once("function/settypebool.php");

/**
 * Controlador para definir condicion SQL
 * 
 * Debido a la complejidad en la definicion del SQL para una condicion, se 
 * separo en una condicion independiente.
 */
class SqlCondition {

  public $container;
  public $entityName;

  /**
   * Metodo principal
   * @param Cada elemento del array $condition es un array formado por
   *  [
   *    0 => "field"
   *    1 => "=", "!=", ">=", "<=", "<", ">", "=="
   *    2 => "value" array|string|int|boolean|date (si es null no se define busqueda, si es un array se definen tantas busquedas como elementos tenga el array)
   *    3 => "AND" | "OR" | null (opcional, por defecto AND)
   *  ]
   */
  public function main(array $condition){
     
    if(empty($condition)) return "";
    $conditionMode = $this->condition($condition);
    return $conditionMode["condition"];
  }

  /**
   * Metodo recursivo para definir condiciones avanzada (considera relaciones)
   * Para facilitar la definicion de condiciones, retorna un array con dos elementos:
   * "condition": SQL
   * "mode": Concatenacion de condiciones "AND" | "OR"
   */
  protected function condition(array $condition){
    /**
     * si en la posicion 0 es un string significa que es un campo a buscar, caso contrario es un nuevo conjunto (array) de campos que debe ser recorrido
     */
    if(is_array($condition[0])) return $this->conditionIterable($condition);
    
    $option = (empty($condition[1])) ? "=" : $condition[1]; //por defecto se define "="
    $value = (!isset($condition[2])) ? null : $condition[2]; //hay opciones de configuracion que pueden no definir valores
    /**
     * No usar empty, puede definirse el valor false
     */
    $mode = (empty($condition[3])) ? "AND" : $condition[3];  //el modo indica la concatenacion con la opcion precedente, se usa en un mismo conjunto (array) de opciones

    $condicion = $this->field($condition[0], $option, $value);
    /**
     * El campo de identificacion del array posicion 0 no debe repetirse en las condiciones no estructuradas y las condiciones estructuras
     * Se recomienda utilizar un sufijo por ejemplo "_" para distinguirlas mas facilmente
     */
    return ["condition" => $condicion, "mode" => $mode];
  }

  /**
   * metodo de iteracion para definir condiciones
   */
  protected function conditionIterable(array $conditionIterable) { 
    
    $conditionModes = array();

    for($i = 0; $i < count($conditionIterable); $i++){
      $conditionMode = $this->condition($conditionIterable[$i]);
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

  /**
   * Combinar parametros y definir SQL con la opcion
   */
  protected function field($field, $option, $value){    
    if(!is_array($value)) {      
      $condition = $this->conditionField($field, $option, $value);
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

      $condition_ = $this->field($field, $option, $v);
      $condition .= $condition_;
    }

    return "(".$condition.")";
  }

  /**
   * Traducir campo y definir SQL con la opcion
   */
  protected function conditionField($field, $option, $value){
    $f = explode("-",$field);

    if(count($f) == 2) {
      $prefix = $f[0];
      $entityName = $this->container->relations($this->entityName)[$f[0]]["entity_name"];
      $field = $f[1];
    } else{
      $prefix = null;
      $entityName = $this->entityName;
    } 

    if(strpos($value, "!") === 0) return $this->optionBetweenFields($field, $option, $value);

    return $this->container->condition($entityName, $prefix)->_($field, $option, $value);

  }

  protected function optionBetweenFields($fieldName1, $option, $fieldName2){
    $field1 = $this->container->mapping($this->entityName, $this->prefix)->_($fieldName);



  }

}