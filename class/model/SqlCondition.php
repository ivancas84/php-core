<?php

require_once("function/snake_case_to.php");
require_once("function/concat.php");
require_once("function/settypebool.php");

class SqlCondition { //Definir SQL

  public function main(){
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

  
}