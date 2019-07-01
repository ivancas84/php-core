<?php

abstract class EntityValues { //manipulacion de valores de una entidad

  abstract public function setRow(array $row = NULL);

  public function __construct($row = NULL){
    $this->setRow($row);
  }

  public static function getInstanceFromString($entity, array $row = NULL) { //crear instancias de values
    //TODO: Implementar metodo setRow fuera del constructor
    $name = snake_case_to("XxYy", $entity) . "Values";
    $class = new $name;
    $class->setRow($row);
    return $class;
  }

  public function isEmpty($value) { //esta vacio
    return ($value === UNDEFINED || empty($value)) ? true : false;
  }

  protected function formatDate($value, $format = 'd/m/Y'){
    if(gettype($value) === "string") $value = SpanishDateTime::createFromFormat("Y-m-d", $value);
    return ($value) ? $value->format($format) : null;
  }

  protected function formatString($value, $format = null){
    switch($format){
      case "XxYy": return str_replace(" ", "", ucwords(str_replace("_", " ", mb_strtolower($value, "UTF-8"))));
      case "xxyy": case "xy": case "x": return mb_strtolower(str_replace("_", "", $value), "UTF-8");
      case "Xx Yy": return ucwords(str_replace("_", " ", mb_strtolower($value, "UTF-8")));
      case "Xx yy": case "X y": return ucfirst(str_replace("_", " ", mb_strtolower($value, "UTF-8")));
      case "xxYy": return str_replace(" ", "", lcfirst(ucwords(str_replace("_", " ", mb_strtolower($value, "UTF-8")))));
      case "xx-yy": case "x-y": return mb_strtolower(str_replace("_", "-", $value), "UTF-8");
      case "XX YY": case "X Y": case "X": return mb_strtoupper(str_replace("_", " ", $value), "UTF-8");
      case "XY": case "XXYY": return mb_strtoupper(str_replace("_", "", $value), "UTF-8");

      default: return $value;
    }
  }

}
