<?php

define("UNDEFINED", "~"); //indica que el valor no esta definido
define("DEFAULT_VALUE", "^"); //indica que debe asignarse valor por defecto

//no hay operaciones en las condiciones, debe definirse un field nuevo y realizar al condicion con el field
// define("MUL", "*"); //operacion multiplicacion
// define("DIV", "/"); //operacion division
// define("ADD", "+"); //operacion suma
// define("SUB", "-"); //operacion resta
/**
 * @example Operacion resta
 * SomeEntityMapping {
 *   public function someNewField()]{
 *    return $this->mapping("field1") . " - " . $this->mapping("field1");
 *   }
 * }
 */

define("EQUAL", "="); //comparacion estrictamente igual
define("APPROX", "=~"); //comparacion aproximadamente igual
define("APPROX_L", "-=~"); //comparacion aproximadamente igual por izquierda (para strings, equivale a LIKE '%something')
define("APPROX_R", "=~-"); //comparacion aproximadamente igual por derecha (para strings, equivale a LIKE 'something%')
define("NONEQUAL", "!="); //comparacion distinto
define("LESS", "<"); //comparacion menor
define("LESS_EQ", "<="); //comparacion menor o igual
define("GREATER", ">"); //comparacion mayor
define("GREATER_EQ", ">="); //comparacion mayor o igual
define("FF", "!!"); //prefijo que indica field (utilizado para definir un valor como field)
define("OR_", "OR"); //prefijo que indica field (utilizado para definir un valor como field)
define("AND_", "AND"); //prefijo que indica field (utilizado para definir un valor como field)

/**
 * @example 
 * $container->query("entity")->cond(["field", EQUAL, "value"]); //se traduce a field = 'value'
 * $container->query("entity")->cond(["field", LESS_EQ, 123]); //se traduce a field <= 123
 * $container->query("entity")->cond(["field", EQUAL, FF."field2"]); //se traduce a field * 123
 * $container->query("entity")->cond([
 *   [
 *      ["field", EQUAL, FF."field2"]
 *      ["field", EQUAL, FF."field3", OR_]
 *   ], 
 *   ["field", APPROX, "value", AND_]
 * ]); //se traduce a ((field = field2 OR field = field3) AND field LIKE '%VALUE%)
 * 
 */