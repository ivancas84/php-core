<?php

require_once("function/settypebool.php");
require_once("class/Validation.php");


class Format {

  static function date(DateTime $value, $format = null){
    if(empty($format)) return $value;  
    if(Validation::is_empty($value)) return null;
    return $value->format($format);
  }

  static function convertCase($value, $format = null){
    if(empty($format)) return $value;  
    if(Validation::is_empty($value)) return null;
    switch($format){
      case "XxYy": return str_replace(" ", "", ucwords(mb_strtolower($value, "UTF-8")));
      case "xxyy": case "xy": case "x": return str_replace(" ", "", mb_strtolower($value, "UTF-8"));
      case "Xx Yy": return ucwords(mb_strtolower($value, "UTF-8"));
      case "Xx yy": case "X y": return ucfirst(mb_strtolower($value, "UTF-8"));
      case "xxYy": return str_replace(" ", "", lcfirst(ucwords(mb_strtolower($value, "UTF-8"))));
      case "xx-yy": case "x-y": return mb_strtolower(str_replace(" ", "-", $value), "UTF-8");
      case "XX YY": case "X Y": case "X": return mb_strtoupper($value, "UTF-8");
      case "XY": case "XXYY": return str_replace(" ", "", mb_strtoupper($value, "UTF-8"));
      case "xx yy": case "x y": return mb_strtolower($value, "UTF-8");

      default: return $value;
    }
  }

  static function boolean($value, $format = null){
    if(empty($format)) return $value;  
    if(Validation::is_undefined($value)) return null;
    switch($format){
      case strpos(mb_strtolower($format), "si") !== false:
      case strpos(mb_strtolower($format), "sí") !== false:
      case strpos(mb_strtolower($format), "no") !== false:   
        return (settypebool($value)) ? "Sí" : "No";
      case strpos(mb_strtolower($format), "s") !== false:
      case strpos(mb_strtolower($format), "n") !== false:              
        return (settypebool($value)) ? "S" : "N";
      default:         
        return $value;
    }
  }

}