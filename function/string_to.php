<?php



function string_to($format, $string){
  switch($format){
    case "XxYy": return str_replace(" ", "", ucwords(strtolower($string)));
    case "xxyy": return strtolower(str_replace(" ", "", strtolower($string)));
    case "Xx Yy": return ucwords(str_replace("_", " ", strtolower($string)));
    case "xxYy": return str_replace(" ", "", lcfirst(ucwords(str_replace("_", " ", strtolower($string)))));
  }
}
