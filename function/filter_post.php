<?php

function filter_post($name){
  $var = filter_input(INPUT_POST, $name);
  if(empty($var)) throw new Exception($name . "esta vacía");
  return $var;
}