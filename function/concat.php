<?php

function concat($value, $connectNoEmpty, $connectEmpty = NULL, $connectCond = NULL){
  /**
   * Concatena valores en funcion de ciertas condiciones
   */
  
  if(empty($value)) {
    return '';
  }

  if (isset($connectEmpty)) {
    $connect = (empty($connectCond)) ? $connectEmpty : $connectNoEmpty;    
  } else {
    $connect = $connectNoEmpty;
  }  

  return $connect . " " . $value;
}