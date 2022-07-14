<?php

function mb_str_pad( $texto, $longitud, $relleno = ' ', $tipo_pad = STR_PAD_RIGHT, $codificacion = null  ){
    if(!strlen( $texto )) return str_pad($texto, $longitud, $relleno, $tipo_pad);
    $diff = empty( $codificacion ) ? 
      ( strlen( $texto ) - mb_strlen( $texto )) : 
      ( strlen( $texto ) - mb_strlen( $texto, $codificacion ) );

    $l = ($longitud + $diff);
    return str_pad( $texto, $l, $relleno, $tipo_pad ); 
}