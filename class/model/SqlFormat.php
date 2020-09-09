<?php

class SqlFormat {
  /**
   * Formato SQL
   * Para simplificar las clases del modelo, los metodos de formato sql basicos se reunen en esta clase
   */

  public $db;
  /**
   * Conexión con la base de datos
   * Para definir el sql es necesaria la existencia de una clase de acceso abierta, ya que ciertos metodos, como por ejemplo "escapar caracteres" lo requieren.
   * Ademas, ciertos metodos requieren determinar el motor de base de datos para definir la sintaxis SQL adecuada
   */

  private static $instance; //singleton

  public function isNull($value){
    /**
     * Implementacion local del metodo is_null
     * Se verifica que el valor no sea igual al string null
     */
    return (is_null($value) || (is_string($value) && (strtolower($value) == 'null')));
  }

  protected function conditionExists($field, $option, $value) {
    if(empty($value) || $value == "true" || $value == "false" || is_bool($value) ) {
      if (($option != "=") && ($option != "!=")) throw new Exception("La combinacion field-option-value no está permitida");

      switch(settypebool($value)){
        case true:
          return ($option == "=") ? "({$field} IS NOT NULL) " : "({$field} IS NULL) ";
        default:
          return ($option == "=") ? "({$field} IS NULL) " : "({$field} IS NOT NULL) ";
      }
    }
  }

  public function conditionIsSet($field, $value, $option = "="){
    /**
     * Valor especial de condicion para verificar la existencia
     */
    return $this->conditionExists($field, $option, settypebool($value));
  }

  public function conditionText($field, $value, $option = "="){
    if($c = $this->conditionExists($field, $option, $value)) return $c;
    if($option == "=~") return "(lower({$field}) LIKE lower('%{$value}%'))";
    if($option == "!=~") return "(lower({$field}) NOT LIKE lower('%{$value}%'))";
    return "(lower({$field}) {$option} lower('{$value}')) ";
  }

  public function conditionDateTime($field, $value, $option, $format){
    if($c = $this->conditionExists($field, $option, $value)) return $c;

    switch($option){
      case "=~": case "!=~":
        /**
         * No se recomienda utilizar datepicker para definir condiciones aproximadas,
         * ya que utilizan el formato JSON y la condicion no matchea
         */
        $o = ($option == "!=~") ? "NOT " : "";
        return "(CAST({$field}) AS CHAR) {$o}LIKE '%{$value}%' )";
      break;

      case "=":
        if($value === false) return "({$field} IS NULL) ";
        if($value === true) return "({$field} IS NOT NULL) ";

      case "!=":
        if($value === true) return "({$field} IS NULL) ";
        if($value === false) return "({$field} IS NOT NULL) ";

      default:
        $value = $this->datetime($value, $format);
        return "({$field} {$option} {$value})";
    }
  }

  public function conditionDateTimeAux($field, $value, $option, $format){
    if($c = $this->conditionExists($field, $option, $value)) return $c;

    switch($option){
      case "=~": case "!=~":
        /**
         * No se recomienda utilizar datepicker para definir condiciones aproximadas,
         * ya que utilizan el formato JSON y la condicion no matchea
         */
        $o = ($option == "!=~") ? "NOT " : "";
        return "(CAST({$field}) AS CHAR) {$o}LIKE '%{$value}%' )";
      break;

      case "=":
        if($value === false) return "({$field} IS NULL) ";
        if($value === true) return "({$field} IS NOT NULL) ";

      case "!=":
        if($value === true) return "({$field} IS NULL) ";
        if($value === false) return "({$field} IS NOT NULL) ";

      default:
        $value = $this->datetimeAux($value, $format);
        return "({$field} {$option} {$value})";
    }
  }

  public function conditionBoolean($field, $value = NULL){
    $v = (settypebool($value)) ? "true" : "false";
    return "({$field} = " . $v . ") ";
  }
  
  public function conditionNumber($field, $value, $option = "="){
    if($c = $this->conditionExists($field, $option, $value)) return $c;

    switch($option) {
      case "=~": 
        return "(CAST(" . $field . " AS CHAR) LIKE '%" . $value . "%' )";
      default: return "(" . $field . " " . $option . " " . $value . ") ";
    }
  }

  public function numeric($value){
    if($this->isNull($value)) return 'null';
    if ( !is_numeric($value) ) throw new Exception('Valor numerico incorrecto: ' . $value);
    else return $value;
  }

  public function positiveIntegerWithoutZerofill($value){
    if($this->isNull($value)) return 'null';
    if ((!is_numeric($value)) && (!intval($value) > 0)) throw new Exception('Valor entero positivo sin ceros incorrecto: ' . $value);
    return $value;
  }

  public function datetime($value, $format){
    if($this->isNull($value)) return 'null';
    if(is_null($format)) $format = "Y-m-d";

    $datetime = (is_object($value) && ($value instanceof DateTime)) ?
      $value : new DateTime($value);

    if ( !$datetime ) throw new Exception('Valor fecha incorrecto: ' . $value);
    $datetime->setTimeZone(new DateTimeZone(date_default_timezone_get()));
    return "'" . $datetime->format($format) . "'";
  }

  public function datetimeAux($value, $format){
    /**
     * Metodo similar a datetime pero se agrega un chequeo adicional para crear
     */
    if($this->isNull($value)) return 'null';
    if(is_null($format)) $format = "Y-m-d";

    if(is_object($value) && ($value instanceof DateTime)){
      $datetime = $value;
    } else {
      $datetime = DateTime::createFromFormat($format, $value);
      if(!$datetime) $datetime = new DateTime($value);
    }

    if ( !$datetime ) throw new Exception('Valor fecha incorrecto: ' . $value);
    $datetime->setTimeZone(new DateTimeZone(date_default_timezone_get()));
    return "'" . $datetime->format($format) . "'";
  }


  public function boolean($value){
    if($this->isNull($value)) return 'null';
    return ( settypebool($value) ) ? 'true' : 'false';
  }

  public function string($value){
    if($this->isNull($value)) return 'null';

    $v = (is_numeric($value)) ? strval($value) : $value;
    if (!is_string($v)) throw new Exception('Valor de caracteres incorrecto: ' . $v);
    else $escapedString = $this->db->escape_string($v);
    return "'" . $escapedString . "'";
  }

}