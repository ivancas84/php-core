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
	|| ( strpos($var, "t") === 0 ) //t, true
  || ( strpos($var, "1") === 0 ) //1
  || ( strpos($var, "y") === 0 ) //y, yes, yeah
  || ( strpos($var, "s") === 0 ) //s, si, sí
  || ( strpos($var, "on") === 0 ) //on
  || ( strpos($var, "ok") === 0 ) //ok, okey
  || ( strpos($var, "ch") === 0 ) //check, checked, chequeado
  || ( strpos($var, "sel") === 0 ) ) //sel, selected, selection, seleccion, seleccinoado
		return true ;
	else 
		return false ;
		
}


?>
