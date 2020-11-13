<?php

function filter_file($name){
  $args = array("file" => array('filter'=> FILTER_DEFAULT,  'flags' => FILTER_REQUIRE_ARRAY));
  $files = filter_var_array($_FILES, $args);
  if(!isset($files[$name])) throw new Exception("Archivo " . $name . " sin definir");
  return $files[$name];
}