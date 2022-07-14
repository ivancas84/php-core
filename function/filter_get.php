<?php

function filter_get($name, $errorEmpty ="sin valor"){
  $var = filter_input(INPUT_GET, $name);
  if(empty($var)) throw new Exception($name . " " . $errorEmpty);
  return $var;
}