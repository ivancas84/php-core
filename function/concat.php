<?php

function concat($value, $connectNoEmpty, $connectEmpty = NULL, $connectCond = NULL){
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