<?php

require_once("function/settypebool.php");
require_once("tools/Validation.php");


class Format {

  static function date($value, $format = null){
    if(Validation::is_undefined($value)) return null;
    if(Validation::is_empty($value)) return null;
    if(empty($format)) return $value;  
    return $value->format($format);
  }

  static function convertCase($value, $format = null){
    if(Validation::is_undefined($value)) return null;
    if(Validation::is_empty($value)) return null;
    if(empty($format)) return $value;  
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
            case strpos($format, "si") !== false:
            case strpos($format, "sí") !== false:
            case strpos($format, "no") !== false:   
                return (settypebool($value)) ? "sí" : "no";
            case strpos($format, "Si") !== false:
            case strpos($format, "Sí") !== false:
            case strpos($format, "No") !== false:   
                return (settypebool($value)) ? "Sí" : "No";
            case strpos($format, "SÍ") !== false:
                return (settypebool($value)) ? "SÍ" : "NO";
            case strpos($format, "SI") !== false:
            case strpos($format, "NO") !== false:   
                    return (settypebool($value)) ? "SI" : "NO";
            case strpos($format, "S") !== false:
            case strpos($format, "N") !== false:              
                return (settypebool($value)) ? "S" : "N";      
            case strpos($format, "s") !== false:
            case strpos($format, "n") !== false:              
                return (settypebool($value)) ? "s" : "n";
        default:         
            return $value;
    }
  }

  static function removeSpecialCharacters($value){
    $value = str_replace(
      array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
      array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
      $value 
    );
  
    $value = str_replace(
      array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
      array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
      $value 
    ); 
  
    $value = str_replace(
      array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
      array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
      $value 
    );
  
    $value = str_replace(
      array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
      array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
      $value 
    );
  
    $value = str_replace(
      array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
      array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
      $value 
    );
  
    $value = str_replace(
        array('ñ', 'Ñ', 'ç', 'Ç'),
        array('n', 'N', 'c', 'C'),
        $value
    );
    
    return $value;
  }

}