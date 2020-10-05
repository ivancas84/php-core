<?php

function acronym($string, $delimiter = " "){
  $words = preg_split("/[\s,_-]+/", $string);
  $acronym = "";
  foreach ($words as $w) {
    if(strlen($w) == 1) continue;
    $acronym .= $w[0];
  }
  return $acronym;
}