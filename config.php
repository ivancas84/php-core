<?php

define("UNDEFINED", "~"); //indica que el valor no esta definido
define("DEFAULT_VALUE", "^"); //indica que debe asignarse valor por defecto
define("EQUAL", "="); //comparacion estrictamente igual
define("APPROX", "=~"); //comparacion aproximadamente igual
define("APPROX_LEFT", "-=~"); //comparacion aproximadamente igual por izquierda (para strings, equivale a LIKE '%something')
define("APPROX_RIGHT", "=~-"); //comparacion aproximadamente igual por derecha (para strings, equivale a LIKE 'something%')
define("NONEQUAL", "!="); //comparacion distinto
define("LESS", "<"); //comparacion menor
define("LESS_EQUAL", "<="); //comparacion menor o igual
define("GREATER", ">"); //comparacion mayor
define("GREATER_EQUAL", ">="); //comparacion mayor o igual
define("FF", "°°"); //prefijo que indica field (utilizado ocasionalmente para definir un valor como field)
define("OR_", "OR"); //prefijo que indica field (utilizado para indicar concatenacion OR en condiciones)
define("AND_", "AND"); //prefijo que indica field (utilizado para indicar concatenacion AND en condiciones)

/**
 * @example 
 * $container->query("entity")->cond(["field", EQUAL, "value"]); //se traduce a field = 'value'
 * $container->query("entity")->cond(["field", LESS_EQUAL, 123]); //se traduce a field <= 123
 * $container->query("entity")->cond(["field", EQUAL, FF."field2"]); //se traduce a field = field2
 * $container->query("entity")->cond([
 *   [
 *      ["field", EQUAL, FF."field2"],
 *      ["field", EQUAL, FF."field3", OR_]
 *   ], 
 *   ["field", APPROX, "value", AND_] //no es necesario agregar AND_ ya que es el valor por defecto
 * ]); //se traduce a (((field = field2) OR (field = field3)) AND (field LIKE '%VALUE%))
 * 
 */

 //******** OPERACIONES MATEMATICAS ********/
/**
 * No se definen condiciones para las operaciones matematicas.
 * Deben definirse en un nuevo field.
 * El sistema asume que si utilizas una operacion, existe una probabilidad de que la vuelvas a usar en otro lado.
 * 
 * @todo Analizar bien como mappear los fields y redefinir el ejemplo a continuacion
 * 
 * @example Operacion resta
 * SomeEntityMapping {
 *   public function someNewField()]{
 *    return $this->mapping("field1") . " - " . $this->mapping("field1");
 *   }
 * }
 */
