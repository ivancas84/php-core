<?php

require_once("function/str_replace_name.php");

function nombres_parecidos($nombre1, $nombre2, $longitud = null){
  /**
   * Compara strings para determinar si existe una coincidencia minima.
   * La palabra o una de las palabres del argumento $nombre1 debe existir o coincidir con una de las palabras de nombre2.
   * Se puede reducir la longitud si se desea comparar con una menor precision
   * por ejemplo si se considera que mirta es la misma persona que mirtha, se puede establecer una longitud de 4
   */

  $n1 = mb_strtoupper(
    str_replace_name($nombre1),
    "UTF-8"
  );
  $n2 = mb_strtoupper(
    str_replace_name($nombre2),
    "UTF-8"
  );
  
  $array1 = explode(' ', $n1);
  $array2 = explode(' ', $n2);
  
  foreach($array1 as $a1){
    if(!$longitud) $longitud = strlen($a1);
    foreach($array2 as $a2){
      $s = substr($a1, 0, $longitud);
      $pos = strpos($a2, $s);
      if($pos !== false) return true;
    }
  }
  
  return false;
}



