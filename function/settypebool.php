<?php

/**
 * Setear el tipo de la variable como booleano. Mejora la funcion settype ($var, "bool") aniadiendo mas alternativas al valor true
 *
 * @param mixed $var: variable a trasformar
 * @return boolean: resultado de la transformacion
 */
function settypebool ( $var ) {
	if(!isset($var) || is_null($var)) return null;
	
	if(is_string($var)) $var = mb_strtolower($var);
	
	if ( ( $var === true ) 
	|| ( $var === 1 )
	|| ( $var === 'true' )
	|| ( $var === '1' )
	|| ( $var === 't' )
	|| ( $var === 'on' ) 
  || ( $var === 'si' ) 
  || ( $var === 'sí' )
  || ( $var === 's' ) 
	|| ( $var === 'yes' )
	|| ( $var === 'ok' ) 
	|| ( $var === 'checked' )
	|| ( $var === 'selected' )) {
		return true ;
			
	} else {
		return false ;
		
	}
}


?>
