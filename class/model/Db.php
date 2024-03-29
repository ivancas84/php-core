<?php

require_once("function/settypebool.php");

class Db extends mysqli {
  /**
   * Extiende la clase mysqli para implementar excepciones y metodos adicionales
   */

  public static $connections = 0; //Uso opcional en contenedor

  public function __construct($host = DATA_HOST, $user = DATA_USER, $passwd = DATA_PASS, $dbname = DATA_DBNAME){
    $this->host = $host;
    $this->dbname = $dbname;
    parent::__construct($host, $user, $passwd, $dbname);
    if($this->connect_error) throw new Exception($this->connect_error);
    $result = $this->multi_query_last( "SET NAMES 'utf8'; SET lc_time_names = 'es_AR';");
    if($this->error) throw new Exception($this->error);
  }

  public function query($query, $resultmode = MYSQLI_STORE_RESULT){
    $result = parent::query($query, $resultmode);
    if(!$result) throw new Exception($this->error);
    return $result;
  }

  public function close(){
    self::$connections--;  //si hay multiples conexiones abiertas, no se cierra se reduce la cantidad
    if(self::$connections <= 0){
      if(!parent::close()) throw new Exception($this->error);
      self::$connections = 0;
    }
    return true;
  }

  public function multi_query($query){
    /**
     * cuidado, siempre espera que se recorran los resultados.
     * Se recomienda utilizar multi_query_last si se quiere evitar procesamiento adicional
     */
    if(!$result = parent::multi_query($query)) throw new Exception($this->error);
    return $result;
  }

  public function multi_query_last($query){
    /**
     * si corresponde,  devuelve el ultimo resultado si existe, sino devuelve false
     */
    $r = $this->multi_query($query);

    $result = $this->store_result();

    $i = 0;
    $errors = [];
    while ($this->more_results()) {
      $result = $this->store_result();
      $r = $this->next_result();
      if(!$r) array_push($errors, "sentencia " . $i);
    }

    if(count($errors)) throw new Exception($this->error . ": " . implode(" ", $errors));
    return $result;
  }

  public function multi_query_transaction($query){
    $this->begin_transaction();
    try {
      $result = $this->multi_query_last($query);
    } catch (Exception $e) {
      $this->rollback();
      throw $e;
    } finally {
      $this->commit();
    }
  }
   
  public function fetch_all_columns($result, $fieldNumber) {
    if ($fieldNumber >= $result->field_count) return array();

    $column = array();
    while ($row = $result->fetch_row()) array_push($column,$row[$fieldNumber]);

    return $column;
  }

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
    $r_aux =  $result->fetch_all(MYSQLI_ASSOC) ;
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
    return (!$result) ? false : $this->fetch_all_columns ( $result , 0 );
  }

}

