<?php

function filter_post($name, $errorEmpty ="sin valor"){
  $var = filter_input(INPUT_POST, $name);
  if(empty($var)) throw new Exception($name . " " . $errorEmpty);
  return $var;
}