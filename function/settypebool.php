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
	|| ( strpos($var, "t") !== false ) //t, true
  || ( strpos($var, "1") !== false ) //1
  || ( strpos($var, "y") !== false ) //y, yes, yeah
  || ( strpos($var, "s") !== false ) //s, si, sí
  || ( strpos($var, "on") !== false ) //on
  || ( strpos($var, "ok") !== false ) //ok, okey
  || ( strpos($var, "ch") !== false ) //check, checked, chequeado
  || ( strpos($var, "sel") !== false ) ) //sel, selected, selection, seleccion, seleccinoado
		return true ;
	else 
		return false ;
		
}


?>
