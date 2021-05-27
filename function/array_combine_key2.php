<?php

function array_combine_key2(array $values, array $keys){
    /**
     * Crear nuevo array, usando $key para las llaves y $values para los valores
     * $key: Debe ser unica
     */

    $r = [];
    foreach($values as $v){
      $k = [];
      foreach($keys as $key){
         array_push($k, $v[$key]);
      }
      $r[implode(UNDEFINED, $k)] = $v;
    }
    return $r;
}