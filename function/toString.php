<?php

//metodo de transformacion a string para ser utilizado como array_walk
function toString(&$value){
  $value = (string)$value;
}
