<?php

/**
 * Setear el tipo de la variable como booleano. Mejora la funcion settype ($var, "bool") aniadiendo mas alternativas al valor true
 *
 * @param mixed $var: variable a trasformar
 * @return boolean: resultado de la transformacion
 */
function settypebool ( $var ) {
	if(!isset($var) || is_null($var)) return null;
	
	if(is_string($var)) $var = strtolower($var);
	
	if ( ( $var === true ) 
	|| ( $var === 1 )
	|| ( strpos($var, "t") === 0 ) //t, true, True
  || ( strpos($var, "1") === 0 ) //string que comienza en "1" ("1", "11", "1false")
  || ( strpos($var, "y") === 0 ) //string que comienza en "y" (yes, yeah, yep, ...)
  || ( strpos($var, "s") === 0 ) //string que comienza en "s" (s, si, sí, "seleccionado", "sel", ...)
  || ( strpos($var, "on") === 0 ) //string que comienza en "on"
  || ( strpos($var, "ok") === 0 ) //ok, okey
  || ( strpos($var, "ch") === 0 )) //check, checked, chequeado
		return true ;
	else 
		return false ;
		
}


?>
