<?php

//Compara strings para determinar si existe una coincidencia minima. 
//La palabra o una de las palabres del argumento $nombre1 debe existir o coincidir con una de las palabras de nombre2.
function nombres_parecidos($nombre1, $nombre2){

  $n1 = mb_strtoupper($nombre1, "UTF-8");
  $n2 = mb_strtoupper($nombre2, "UTF-8");
  
  $array1 = explode(' ', $n1);
  $array2 = explode(' ', $n2);
  
  foreach($array1 as $a1){
    foreach($array2 as $a2){
      $s = substr($a1, 0, 3);
      $pos = strpos($a2, $s);
      if($pos !== false) return true;
    }
  }
  
  return false;
}



