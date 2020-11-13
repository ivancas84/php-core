<?php

require_once("class/tools/Validation.php");

class SqlTools {

  public $container;

  public function exists($field, $option, $value) {
    if(empty($value) || $value == "true" || $value == "false" || is_bool($value) ) {
      if (($option != "=") && ($option != "!=")) throw new Exception("La combinacion field-option-value no está permitida para definir existencia: " . $field. " " . $option . " " . $value, 404);

      switch(settypebool($value)){
        case true:
          return ($option == "=") ? "({$field} IS NOT NULL) " : "({$field} IS NULL) ";
        default:
          return ($option == "=") ? "({$field} IS NULL) " : "({$field} IS NOT NULL) ";
      }
    }
  }

  public function approxCast($field, $option, $value) {
    if($option == "=~") return "(CAST({$field} AS CHAR) LIKE '%{$value}%' )";
    if($option == "!=~") return "(CAST({$field} AS CHAR) NOT LIKE '%{$value}%' )";
  }

  public function approx($field, $option, $value) {
    if($option == "=~") return "(lower({$field}) LIKE lower('%{$value}%'))";
    if($option == "!=~") return "(lower({$field}) NOT LIKE lower('%{$value}%'))";
  }


  public function dateTime($value, $format){
    if(Validation::is_undefined($value)) return UNDEFINED;
    if(Validation::is_empty($value)) return 'null';
    return "'" . $value->format($format) . "'";
  }

  public function boolean($value){
    if(Validation::is_undefined($value)) return UNDEFINED;
    return ( $value ) ? 'true' : 'false';
  }

  public function string($value){
    if(Validation::is_undefined($value)) return UNDEFINED;
    if(Validation::is_empty($value)) return 'null';
    return "'" . $this->container->getDb()->escape_string($value) . "'";
  }

  public function number($value){
    if(Validation::is_undefined($value)) return UNDEFINED;
    if(Validation::is_empty($value)) return 'null';
    return $value;
  }
}