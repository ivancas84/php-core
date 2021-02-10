<?php

function php_input(){
  $data = file_get_contents("php://input");
  if(!$data) throw new Exception("Error al obtener datos de input");
  return json_decode($data, true);
}