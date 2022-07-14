<?php

function ordinal(int $number){
  $ordinales = array('primero','segundo','tercero','cuarto');

  if ($number<=count ($ordinales)){
      return $ordinales[$number-1];
  }
  
  return $number.'-esimo';
}