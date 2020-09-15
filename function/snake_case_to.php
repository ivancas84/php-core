<?php

function snake_case_to($format, $string){
  /**
   * Debe trabajar con strings utf8 sin acentos ni caracteres especiales
   * Esta destinado a formatear nombres de metodos y elementos relacionados
   */
  switch($format){
    case "XxYy": return str_replace(" ", "", ucwords(trim(str_replace("_", " ", strtolower($string)))));
    case "xxyy": return strtolower(str_replace("_", "", $string));
    case "Xx Yy": return ucwords(trim(str_replace("_", " ", strtolower($string))));
    case "xxYy": return str_replace(" ", "", lcfirst(ucwords(trim(str_replace("_", " ", strtolower($string))))));
  }
}
