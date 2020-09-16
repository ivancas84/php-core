<?php

require_once("class/model/entityOptions/Condition.php");

class ConditionAuxEntityOptions extends ConditionEntityOptions {
  /**
   * Las condiciones auxiliares no siguen una estructura habitual de definicion,
   * deben analizarse de forma independiente al definir el sql.
   * 
   * Debido a la estructura particular de las condiciones estructurales,
   * las condiciones auxiliares se verifican en primer instancia,
   * si no hay condicion auxiliar, se realiza la verificación de condicion estructural.
   * 
   * La identificación unica de campos respeta ambas condiciones, 
   * es decir, no puede repetirse el mismo campo para condicion auxiliar y estructural, 
   * si esta definido en uno, no puede estar definido en el otro.
   */

  public function compare($option, $value) {
    $f1 = $this->mapping->eval($value[0]);
    $f2 = $this->mapping->eval($value[1]);
    return "({$f1} {$option} {$f2})";
  }
  

}