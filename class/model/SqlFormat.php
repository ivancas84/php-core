<?php


class SqlFormat { //Formato SQL
  /**
   * Para simplificar las clases del modelo, los metodos de formato sql basicos se reunen en esta clase
   */

  public $db; //DB. Conexion con la bse de
  /**
   * Para definir el sql es necesaria la existencia de una clase de acceso abierta, ya que ciertos metodos, como por ejemplo "escapar caracteres" lo requieren.
   * Ademas, ciertos metodos requieren determinar el motor de base de datos para definir la sintaxis SQL adecuada
   */

   private static $instance; //singleton

  public function __construct() {
    $this->db = Dba::dbInstance();
  }

  public static function getInstance() { //singleton sqlFormat
    if(is_null(self::$instance)) self::$instance = new SqlFormat();
    return self::$instance;
  }


  public function _conditionTextApprox($field, $option, $value) {
    $option = ($option == "=~") ? "LIKE" : "NOT LIKE";
    return "(lower({$field}) {$option} lower('%{$value}%'))";
  }

  public function _conditionDateApprox($field, $value){
    if($this->db->getDbms() == "mysql") return "(CAST(DATE_FORMAT(" . $field . ", '%d/%m/%Y') AS CHAR) LIKE '%" . $value . "%' )";
    else return "(TO_CHAR(" . $field . ", 'DD/MM/YYYY') LIKE '%" . $value . "%' )";
  }

  public function _conditionNumberApprox($field, $value){
    if($this->db->getDbms() == "mysql") return "(CAST(" . $field . " AS CHAR) LIKE '%" . $value . "%' )";
    else return "(trim(both ' ' from to_char(" . $field . ", '99999999999999999999')) LIKE '%" . $value . "%' ) ";
  }

  public function _conditionTimestampApprox($field, $value){
    if($this->db->getDbms() == "mysql") return "(CAST(DATE_FORMAT(" . $field . ", '%d/%m/%Y %H:%i') AS CHAR) LIKE '%" . $value . "%' )";
    else return "(TO_CHAR(" . $field . ", 'DD/MM/YYYY HH:MI') LIKE '%" . $value . "%' )";
  }

  public function _conditionYearApprox($field, $value){
    if($this->db->getDbms() == "mysql") return "(CAST(DATE_FORMAT(" . $field . ", '%Y') AS CHAR) LIKE '%" . $value . "%' )";
    else return "(TO_CHAR(" . $field . ", 'YYYY') LIKE '%" . $value . "%' )";
  }

  public function conditionText($field, $value, $option = "="){
    if($value === false) return "(" . $field . " IS NULL) ";
    if($value === true) return "(" . $field . " IS NOT NULL) ";
    if($option == "=~") return "(lower({$field}) LIKE lower('%{$value}%'))";
    if($option == "!=~") return "(lower({$field}) NOT LIKE lower('%{$value}%'))";
    return "(lower({$field}) {$option} lower('{$value}')) ";
  }

  public function conditionDate($field, $value, $option = "="){
    if($value === false) return "(" . $field . " IS NULL) ";
    if($value === true) return "(" . $field . " IS NOT NULL) ";

    if($this->db->getDbms() == "mysql") return "(" . $field . " " . $option. " '" . $value . "')";
    else return "(" . $field . " " . $option. " TO_DATE('" . $value . "', 'YYYY-MM-DD') )";;
  }

  public function conditionTimestamp($field, $value, $option = "="){
    if($value === false) return "(" . $field . " IS NULL) ";
    if($value === true) return "(" . $field . " IS NOT NULL) ";

    if($this->db->getDbms() == "mysql") return "(" . $field . " " . $option. " '" . $value . "')";
    else return "(" . $field . " " . $option. " TO_TIMESTAMP('" . $value . "', 'YYYY-MM-DD HH24:MI:SS') )";
  }

  public function conditionNumber($field, $value, $option = "="){
    if($value === false) return "(" . $field . " IS NULL) ";
    if($value === true) return "(" . $field . " IS NOT NULL) ";
    if($option === true) return "(" . $field . " IS NULL) ";
    if($option === false) return "(" . $field . " IS NOT NULL) ";
    return "(" . $field . " " . $option . " " . $value . ") ";
  }

  public function conditionBoolean($field, $value = NULL){ //definir condicion de busqueda de booleano
    $v = (settypebool($value)) ? "true" : "false";
    return "(" . $field . " = " . $v . ") ";
  }

  public function numeric($value){
    if(is_null($value) || ($value === 'null')) return 'null';

    if ( !is_numeric($value) ) throw new Exception('Valor numerico incorrecto: ' . $value);
    else return $value;
  }

  public function positiveIntegerWithoutZerofill($value){
    if(is_null($value) || ($value === 'null')) return 'null';
    if ((!is_numeric($value)) && (!intval($value) > 0)) throw new Exception('Valor entero positivo sin ceros incorrecto: ' . $value);
    return $value;
  }

  public function timestamp($value){
    if($value == 'null') return 'null';

    if(is_object($value) && get_class($value) == "DateTime"){
      $datetime = $value;
    } else {
      $datetime = DateTime::createFromFormat('Y-m-d H:i:s', $value);
    }

    if ( !$datetime ) throw new Exception('Valor fecha y hora incorrecto: ' . $value);
    else return "'" . $datetime->format('Y-m-d H:i:s') . "'";
  }

  public function date($value){
    if($value == 'null') return 'null';

    if(is_object($value) && get_class($value) == "DateTime"){
      $datetime = $value;
    } else {
      $datetime = DateTime::createFromFormat('Y-m-d', $value);
    }

    if ( !$datetime ) throw new Exception('Valor fecha incorrecto: ' . $value);
    else return "'" . $datetime->format('Y-m-d') . "'";
  }

  public function time($value){
    if($value == 'null') return 'null';

    if(is_object($value) && get_class($value) == "DateTime"){
      $datetime = $value;
    } else {
      $datetime = DateTime::createFromFormat('H:i', $value);
      if(!$datetime) $datetime = DateTime::createFromFormat('H:i:s', $value);
    }

    if ( !$datetime ) throw new Exception('Valor fecha incorrecto: ' . $value);
    else return "'" . $datetime->format('H:i') . "'";
  }

  public function year($value){
    if($value == 'null') return 'null';

    if(is_object($value) && get_class($value) == "DateTime"){
      $datetime = $value;
    } else {
      $datetime = DateTime::createFromFormat('Y', $value);
    }

    if ( !$datetime ) throw new Exception('Valor año incorrecto: ' . $value);
    else return "'" . $datetime->format('Y') . "'";
  }

  public function boolean($value){
    if(is_null($value) || ($value === 'null')) return 'null';

    return ( settypebool($value) ) ? 'true' : 'false';
  }

  public function string($value){
    if(is_null($value) || ($value === 'null')) return 'null';

    if (!is_string($value)) throw new Exception('Valor de caracteres incorrecto: ' . $value);
    else return "'" . $value . "'";
  }

  public function escapeString($value){
    if($value == 'null') return 'null';

    $v = (is_numeric($value)) ? strval($value) : $value;
    if (!is_string($v)) throw new Exception('Valor de caracteres incorrecto: ' . $v);
    else $escapedString = $this->db->escapeString($v);
    return "'" . $escapedString . "'";
  }

}
