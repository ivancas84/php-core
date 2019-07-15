<?php

abstract class EntityValues { //manipulacion de valores de una entidad

  public $_warnings = [];
  public $_errors = [];
  
  public function addWarning($warning) { array_push($this->_warnings, $warning); }
  public function addError($error) { array_push($this->_errors, $error); }

  abstract public function fromArray(array $row = NULL);

  //abstract public function setDefault();

  public static function getInstanceFromArray(array $row = NULL) { //crear instancias de values
    $className = get_called_class();
    $v = new $className;
    if($row) $v->fromArray($row);
    return $v;   
  }

  public static function getInstanceFromString($entity, array $row = NULL) { //crear instancias de values
    $name = snake_case_to("XxYy", $entity) . "Values";
    $class = new $name;
    if($row) $class->fromArray($row);
    return $class;
  }

  public function isEmpty($value) { //esta vacio
    return ($value === UNDEFINED || empty($value)) ? true : false;
  }

  protected function formatDate($value, $format = 'd/m/Y'){
    if($this->isEmpty($value)) return null;
    if(gettype($value) === "string") $value = SpanishDateTime::createFromFormat("Y-m-d", $value);
    return ($value) ? $value->format($format) : null;
  }

  protected function formatString($value, $format = null){
    if($this->isEmpty($value)) return null;
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
