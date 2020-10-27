<?php

//metodo de transformacion a string para ser utilizado como array_walk
function to_string(&$value){
  $value = (string)$value;
}
