<?php

/**
 * @todo Implementar render en el getall
 */

require_once("class/model/db/My.php");
require_once("class/model/db/Pg.php");

class Db extends mysqli {
  /**
   * Extiende la clase mysqli para implementar excepciones y metodos adicionales
   */
  public static $dbInstance = [];
  public static $dbCount = [];

  public static connect($host = DATA_HOST, $user = DATA_USER, $passwd = DATA_PASSWORD, $dbname = DATA_DBNAME){
    if (!key_exists($host.$dbname, self::$dbCount)) {
      self::$dbCount[$host.$dbname] = 0 
      self::$dbInstance[$host.$dbname] = $this->__construct($host, $user, $passwd, $dbname);
    }
    self::$dbCount[$host.$passwd]++;
    return self::$dbInstance[$host.$passwd];
  }

  public function __construct($host = DATA_HOST, $user = DATA_USER, $passwd = DATA_PASSWORD, $dbname = DATA_DBNAME){
    $this->host = $host;
    $this->dbname = $dbname;
    parent::__construct($host, $user, $password, $dbname);
    if($this->connect_error) throw new Exception($this->connect_error);
    $this->multi_query( "SET NAMES 'utf8'; SET lc_time_names = 'es_AR';");
  }

  public function __destruct(){
    if (key_exists($host.$dbname, self::$dbCount)) {
      self::$dbCount[$host.$dbname]--;
      if(self::$dbCount[$host.$dbname] <= 0) {
        self::$dbInstance[$host.$dbname]->close();
        unset($dbCount[$host.$dbname]);
        unset($dbInstance[$host.$dbname]);
      }
    } 
  }

  public function query($query, $resultmode = MYSQLI_STORE_RESULT){
    $result = parent::query($query, $resultmode);
    if($this->error) throw new Exception($this->error);
    return $result;
  }

  public function multi_query($query){
    $result = parent::multi_query($query);
    if($this->error) throw new Exception($this->error);
    return $result;
  }

  public function multi_query_last(){
    $result = $this->multi_query($query);
    while ($this->more_results()) $result = $this->next_result();
    return $result;
  }

  public function multi_query_transaction($query){
    $this->begin_transaction();
    try {
      $result = $this->multi_query($query);
    } catch (Exception $e) {
      $this->rollback();
      throw $e;
    } finally() {
      $this->commit();
    }
  }
   
  public function fetch_all_columns($result, $fieldNumber) {
    if ($fieldNumber >= $this->field_count($result)) return array();

    $column = array();
    while ($row = $this->fetch_row($result)) array_push($column,$row[$fieldNumber]);

    return $column;
  }

  public function num_rows($result){ return $result->num_rows; } //@override

  public function field_count($result){ return $result->field_count; }

  public function fetch_all($result) { return $result->fetch_all(MYSQLI_ASSOC); }

  public function fetch_assoc($result){ return $result->fetch_assoc(); }

  public function fetch_row($result){ return $result->fetch_row(); }

  public function fields_info ( $table ) {
  /**
   * Retornar array multiple con informacion de los fields de una tabla de la base de datos
   * @param string $table: nombre de la tabla
   * @return false|array
   * No esta contemplado en la consulta a la base de datos el caso de que la pk sea clave foranea.
   */

    $sql = "
SELECT
DISTINCT COLUMNS.COLUMN_NAME, COLUMNS.COLUMN_DEFAULT, COLUMNS.IS_NULLABLE, COLUMNS.DATA_TYPE, COLUMNS.COLUMN_TYPE, COLUMNS.CHARACTER_MAXIMUM_LENGTH, COLUMNS.NUMERIC_PRECISION, COLUMNS.NUMERIC_SCALE, COLUMNS.COLUMN_KEY, COLUMNS.EXTRA,
SUB.REFERENCED_TABLE_NAME, SUB.REFERENCED_COLUMN_NAME, COLUMNS.ORDINAL_POSITION
FROM INFORMATION_SCHEMA.COLUMNS
LEFT OUTER JOIN (
SELECT KEY_COLUMN_USAGE.COLUMN_NAME, KEY_COLUMN_USAGE.REFERENCED_TABLE_NAME, KEY_COLUMN_USAGE.REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE (CONSTRAINT_NAME != 'PRIMARY') AND (REFERENCED_TABLE_NAME IS NOT NULL) AND (REFERENCED_COLUMN_NAME IS NOT NULL)

AND (KEY_COLUMN_USAGE.TABLE_SCHEMA = '" .  $this->dbname . "') AND (KEY_COLUMN_USAGE.TABLE_NAME = '" . $table . "')
) AS SUB ON (COLUMNS.COLUMN_NAME = SUB.COLUMN_NAME)
WHERE (COLUMNS.TABLE_SCHEMA = '" .  $this->dbname . "') AND (COLUMNS.TABLE_NAME = '" . $table . "')
ORDER BY COLUMNS.ORDINAL_POSITION;";

    $result = $this->query($sql);
    $r_aux = $this-> fetch_all ( $result ) ;
    $r = array () ;

    foreach ($r_aux as $field_aux ) {
      $field = array ( ) ;
      $field["field_name"] = $field_aux["COLUMN_NAME"] ;
      $field["field_default"] = $field_aux["COLUMN_DEFAULT"] ;
      $field["data_type"] = $field_aux["DATA_TYPE"] ;
      $field["not_null"] = (!settypebool( $field_aux["IS_NULLABLE"] )) ? true : false;
      $field["primary_key"] = ($field_aux["COLUMN_KEY"] == "PRI" ) ? true : false;
      $field["unique"] = ($field_aux["COLUMN_KEY"] == "UNI" ) ? true : false;
      $field["foreign_key"] = (!empty($field_aux["REFERENCED_COLUMN_NAME"])) ? true : false;
      $field["referenced_table_name"] = $field_aux["REFERENCED_TABLE_NAME"] ;
      $field["referenced_field_name"] = $field_aux["REFERENCED_COLUMN_NAME"] ;

      if ( !empty( $field_aux["CHARACTER_MAXIMUM_LENGTH"] ) ) {
        $field["length"] = $field_aux["CHARACTER_MAXIMUM_LENGTH"] ;
      } elseif ( !empty( $field_aux["NUMERIC_PRECISION"] ) ) {
        $sub = substr($field_aux["COLUMN_TYPE"] , strpos($field_aux["COLUMN_TYPE"],"(")+strlen("("),strlen($field_aux["COLUMN_TYPE"]));
        $length = substr($sub,0,strpos($sub,")"));
        if(intval($field_aux["NUMERIC_PRECISION"]) <= intval($length)){
          $field["length"] = $field_aux["NUMERIC_PRECISION"];
        } else {
          $field["length"] = $length;
        }

        if ( (!empty ( $field_aux["NUMERIC_SCALE"])) && ( $field_aux["NUMERIC_SCALE"] != '0' ) ) {
          $field["length"] .= "," . $field_aux["NUMERIC_SCALE"] ;
        }
      } else {
        $field["length"] = false ;
      }

      array_push ( $r, $field);
    }

    return $r ;
  }

  function tables_name () { 
    /**
     * Retornar array con el nombre de las tablas de la base de datos
     */
    $sql = "SHOW TABLES FROM " . $this->dbname . ";";
    $result = $this->query($sql);
    return (!$result) ? false : fetch_all_columns ( $result , 0 );
  }

}

