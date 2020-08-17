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

  public function __construct() {
    $this->db = Db::open();
  }

  public static function getInstance() {
    /**
     * singleton
     */
    if(is_null(self::$instance)) self::$instance = new SqlFormat();
    return self::$instance;
  }

  public function isNull($value){
    /**
     * Implementacion local del metodo is_null
     * Se verifica que el valor no sea igual al string null
     */
    return (is_null($value) || (strtolower($value) == 'null'));
  }

  protected function conditionIsNull($field, $option, $value) {
    if(empty($value)) {
      switch($option){
        case "=": return "({$field} IS NULL) ";
        case "!=": return "({$field} IS NOT NULL) "; 
      }
      throw new Exception("La combinacion field-option-value no está permitida");
    }

    if($value === true) {
      switch($option){
        case "!=": return "({$field} IS NULL) ";
        case "=": return "({$field} IS NOT NULL) ";       
      }
      throw new Exception("La combinacion field-option-value no está permitida");
    }
  }

  public function conditionText($field, $value, $option = "="){
    if($c = $this->conditionIsNull($field, $option, $value)) return $c;
    if($option == "=~") return "(lower({$field}) LIKE lower('%{$value}%'))";
    if($option == "!=~") return "(lower({$field}) NOT LIKE lower('%{$value}%'))";
    return "(lower({$field}) {$option} lower('{$value}')) ";
  }

  public function conditionTimestamp($field, $value, $option = "="){
    if($c = $this->conditionIsNull($field, $option, $value)) return $c;

    switch($option){
      case "=~": case "!=~":
        /**
         * No se recomienda utilizar datepicker para definir condiciones aproximadas,
         * ya que utilizan el formato JSON y la condicion no matchea
         */
        $o = ($option == "!=~") ? "NOT " : "";
        return "(CAST(DATE_FORMAT({$field}, '%Y-%m-%d %H:%i:%s') AS CHAR) {$o}LIKE '%{$value}%' )";
      break;

      case "=":
        if($value === false) return "({$field} IS NULL) ";
        if($value === true) return "({$field} IS NOT NULL) ";

      case "!=":
        if($value === true) return "({$field} IS NULL) ";
        if($value === false) return "({$field} IS NOT NULL) ";

      default:
        $value = $this->timestamp($value);
        return "({$field} {$option} {$value})";
    }  
  }

  public function conditionYear($field, $value, $option = "="){
    if($c = $this->conditionIsNull($field, $option, $value)) return $c;

    switch($option){
      case "=~": case "!=~":
        /**
         * No se recomienda utilizar datepicker para definir condiciones aproximadas,
         * ya que utilizan el formato JSON y la condicion no matchea
         */
        $o = ($option == "!=~") ? "NOT " : "";
        return "(CAST(DATE_FORMAT({$field}, '%Y') AS CHAR) {$o}LIKE '%{$value}%' )";
      break;

      case "=":
        if($value === false) return "({$field} IS NULL) ";
        if($value === true) return "({$field} IS NOT NULL) ";

      case "!=":
        if($value === true) return "({$field} IS NULL) ";
        if($value === false) return "({$field} IS NOT NULL) ";

      default:
        $value = $this->year($value);
        return "({$field} {$option} {$value})";  
    }
  }

  public function conditionDate($field, $value, $option = "="){
    if($c = $this->conditionIsNull($field, $option, $value)) return $c;

    switch($option){
      case "=~": case "!=~":
        /**
         * No se recomienda utilizar datepicker para definir condiciones aproximadas,
         * ya que utilizan el formato JSON y la condicion no matchea
         */
        $o = ($option == "!=~") ? "NOT " : "";
        return "(CAST(DATE_FORMAT({$field}, '%Y-%m-%d') AS CHAR) {$o}LIKE '%{$value}%' )";
      break;

      case "=":
        if($value === false) return "({$field} IS NULL) ";
        if($value === true) return "({$field} IS NOT NULL) ";

      case "!=":
        if($value === true) return "({$field} IS NULL) ";
        if($value === false) return "({$field} IS NOT NULL) ";

      default:
        $value = $this->date($value);
        return "({$field} {$option} {$value})";
    }
  }

  public function conditionTime($field, $value, $option = "="){
    if($c = $this->conditionIsNull($field, $option, $value)) return $c;

    switch($option){
      case "=~": case "!=~":
        /**
         * No se recomienda utilizar datepicker para definir condiciones aproximadas,
         * ya que utilizan el formato JSON y la condicion no matchea
         */
        $o = ($option == "!=~") ? "NOT " : "";
        return "(CAST(DATE_FORMAT({$field}, '%H:%i:%s') AS CHAR) {$o}LIKE '%{$value}%' )";
      break;

      case "=":
        if($value === false) return "({$field} IS NULL) ";
        if($value === true) return "({$field} IS NOT NULL) ";

      case "!=":
        if($value === true) return "({$field} IS NULL) ";
        if($value === false) return "({$field} IS NOT NULL) ";

      default:
        $value = $this->date($value);
        return "({$field} {$option} {$value})";  
    }
  }

  public function conditionBoolean($field, $value = NULL){
    $v = (settypebool($value)) ? "true" : "false";
    return "({$field} = " . $v . ") ";
  }
  
  public function conditionNumber($field, $value, $option = "="){
    if($c = $this->conditionIsNull($field, $option, $value)) return $c;

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

  public function date($value){
    if($this->isNull($value)) return 'null';

    $datetime = (is_object($value) && get_class($value) == "DateTime") ?
      $value : new DateTime($value);

    if ( !$datetime ) throw new Exception('Valor fecha incorrecto: ' . $value);
    $datetime->setTimeZone(new DateTimeZone(date_default_timezone_get()));
    return "'" . $datetime->format('Y-m-d') . "'";
  }

  public function time($value){
    if($this->isNull($value)) return 'null';

    $datetime = (is_object($value) && get_class($value) == "DateTime") ?
      $value : new DateTime($value);

    if ( !$datetime ) throw new Exception('Valor fecha incorrecto: ' . $value);
    $datetime->setTimeZone(new DateTimeZone(date_default_timezone_get()));
    return "'" . $datetime->format('H:i:s') . "'";
  }

  public function timestamp($value){
    if($this->isNull($value)) return 'null';

    $datetime = (is_object($value) && get_class($value) == "DateTime") ?
      $value : new DateTime($value);

    if ( !$datetime ) throw new Exception('Valor fecha incorrecto: ' . $value);
    $datetime->setTimeZone(new DateTimeZone(date_default_timezone_get()));
    return "'" . $datetime->format('Y-m-d H:i:s') . "'";
  }

  public function year($value){
    /**
     * Metodo similar a datetime pero se agrega un chequeo adicional para crear
     */
    if($this->isNull($value)) return 'null';

    if(is_object($value) && (get_class($value) == "DateTime" || get_class($value) == "SpanishDateTime")){
      $datetime = $value;
    } else {
      $datetime = DateTime::createFromFormat('Y', $value);
      if(!$datetime) $datetime = new DateTime($value);
    }

    if ( !$datetime ) throw new Exception('Valor año incorrecto: ' . $value);
    $datetime->setTimeZone(new DateTimeZone(date_default_timezone_get()));
    return "'" . $datetime->format('Y') . "'";
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