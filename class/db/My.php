<?php

require_once ( "function/settypebool.php" );
require_once("class/db/Interface.php");

class DbSqlMy extends mysqli implements DbInterface {

  protected $host;
  protected $user;
  protected $password;
  protected $dbname;
  protected $schema;

  public function __construct($host, $user, $password, $dbname, $schema) {
    $this->host = $host;
    $this->user = $user;
    $this->password = $password;
    $this->dbname = $dbname;
    $this->schema = $schema;
    parent::__construct($host, $user, $password, $dbname);
    if($this->connect_error) throw new Exception($this->connect_error);
    $this->multiQuery( "SET NAMES 'utf8'; SET lc_time_names = 'es_AR';");

  }

  public function getSchema(){ return $this->schema; } //@override

  public function getSchemaDot(){ //@override
    if (!empty($this->schema)) return $this->schema . ".";
    return "";
  }

  public function getDbms(){ return "mysql"; } //@override

  public function query($query, $resultmode = NULL){ //@override
    $result = parent::query($query);

    if(!$result) throw new Exception($this->error);
    return $result;
  }

  public function multiQuery($query){
    $result = $this->multi_query($query);
    if(!$result) throw new Exception($this->error);

    $i = 0;
    $errors = [];
    while ($this->more_results()) {
      $i++;
      $result = $this->next_result();
      if(!$result) array_push($errors, "sentencia " . $i);
    }

    if(count($errors)) throw new Exception($this->error . ": " . implode(" ", $errors));

    return $result;
  }


  public function multiQueryTransaction($query){

    try {
      $this->multiQuery("BEGIN; " . $query);
      $this->query("COMMIT;");
    }

    catch (Exception $ex) {
      $this->query("ROLLBACK;");
      throw $ex;
    }
  }

  public function numRows($result){ return $result->num_rows; } //@override

  public function numFields($result){ return $result->field_count; }

  public function fetchAll($result) {
    return $result->fetch_all(MYSQLI_ASSOC);
    /*
    $rows = array();
    while ($row = $result->fetch_assoc()) {
      array_push($rows,$row);
    }

    return $rows;*/
  }

  public function fetchAssoc($result){ return $result->fetch_assoc(); }

  public function fetchRow($result){ return $result->fetch_row(); }


  public function fetchAllColumns($result, $fieldNumber) {
    if ($fieldNumber >= $this->numFields($result)) return array();

    $column = array();
    while ($row = $this->fetchRow($result)) array_push($column,$row[$fieldNumber]);

    return $column;
  }






  /**
   * Retornar array multiple con informacion de los fields de una tabla de la base de datos
   * @param string $table: nombre de la tabla
   * @return boolean|array
   * @note No esta contemplado en la consulta a la base de datos el caso de que la pk sea clave foranea.
   */
  function fieldsInfo ( $table ) {
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

            $r_aux = $this-> fetchAll ( $result ) ;

            $r = array () ;

            //redefinir valores del resultado de la consulta
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

  function tablesName () { //Retornar array con el nombre de las tablas de la base de datos
    $sql = "SHOW TABLES FROM " . $this->dbname . ";";
    $result = $this->query($sql);

    if (!$result) {
        return false;

    } else {
        return $this-> fetchAllColumns ( $result , 0 );
    }
  }

  public function begin() { return $this->begin_transaction(); }

  public function escapeString($string){ return $this->escape_string($string); }



    /**
     * @override
     *
    public function connect(){
      if ($this->isConnected()) {
        throw new Exception("Error al conectar. Ya existe una conexion abierta. Solo puede existir una conexion a la base de datos abierta a la vez");
      }

      $this->connection = mysqli_connect ($this->host, $this->user, $this->pass, $this->dbname);
      $this->query( "SET NAMES 'utf8';");

      if (mysqli_connect_error()){
          $this->connection = null;
          throw new Exception(mysqli_connect_error());
      }
    }

    /**
     * @override
     *
    public function autocommit($mode){
       if ($this->transaction) throw new Exception("Ya existe una transaccion iniciada");
      $res = mysqli_autocommit($this->connection, $mode);
      if (!$res) throw new Exception(mysqli_error($this->connection));
      return $res;
    }



    /**
     * @override
     *
    public function commit(){
      if (!$this->isTransaction()) throw new Exception("No existe una transaccion iniciada");
      $res = mysqli_commit($this->connection);
      $this->setTransaction(false);
      if (!$res) throw new Exception(mysqli_error($this->connection));
      return true;
    }



    /**
     * @override
     *
    public function isConnected() {
        if (isset($this->connection)) {
            if ((is_object($this->connection)) && (get_class($this->connection) == "mysqli") && (mysqli_ping($this->connection))){
                return true;
            }
        }

        return false;
    }

    /**
     * @override
     *
    public function rollback(){
      if (!$this->isTransaction()) throw new Exception("No existe una transaccion iniciada");
      $res =  mysqli_rollback($this->connection);
      if (!$res) throw new Exception(mysqli_error($this->connection));
      $this->setTransaction(false);
      return true;
    }





    /**
     * @override
     *
    public function close(){
      if(!$this->isConnected()) throw new Exception("No se puede cerrar conexion, no existe conexion abierta");
      if ($this->transaction) throw new Exception("No se puede cerrar conexion, hay una transaccion iniciada");
      $res = mysqli_close($this->connection);
      if (!$res) throw new Exception(mysqli_error($this->connection));
      $this->connection = NULL;
      return true;
    }




    /**
     * @override
     *
    function error(){
        return mysqli_error($this->connection);
    }

    /**
     * @override
     *
    function numRows($result){
        return mysqli_num_rows($result);
    }

    /**
     * @override
     *
    function numFields($result){
        return mysqli_num_fields($result);
    }

    /**
     * @override
     *
    function fieldName($result,$field_number){
        return mysqli_field_name($result,$field_number);
    }

    /**
     * @override
     *
    public function fetchRow($result,$row_number = 0){
        if (mysqli_num_rows($result) > $row_number ) {
            mysqli_data_seek  ($result , $row_number);
            return mysqli_fetch_row($result);
        }else{
            return false;
        }
    }

    /**
     * @override
     *
    public function fetchAssoc($result,$row_number = 0){
        if (mysqli_data_seek($result, $row_number)){
            return mysqli_fetch_assoc($result);
        }else{
            return false;
        }
    }

    /**
     * @override
     *
    function freeResult($result){
            return mysqli_free_result($result);
    }

    /**
     * @override
     *
    function fetchAll($result){
        $a = array();
        for ($i = 0; $i < $this->numRows($result); $i++){
                array_push($a,$this->fetchAssoc($result,$i));
        }
        return $a;
    }

    /**
     * @override
     *
    function fetchAllColumns($result,$field_number){
        if ($field_number < $this->numFields($result)){
            $a = array();
            for($i = 0; $i < $this->numRows($result); $i++){
                $row = $this->fetchRow($result,$i);
                array_push($a,$row[$field_number]);
            }
            return $a;
        }else{
            return false;
        }
    }


    /**
     * @override
     *
    protected function escapeStringAux($string){
        return mysqli_real_escape_string($this->connection, $string);
    }

    /**
     * @override
     *
    public function nextVal($table, $field){
        $sql = "SELECT MAX(".$field.") + 1 AS next_id FROM ". $this->getSchemaDot() . $table.";";
        $result = $this->query ( $sql );
        if ( !$result ) throw new Exception ( "Error al definir next val" );
        if (!( $this->numRows ($result) )) {
            return 1;
        }

        $res = $this->fetchRow ( $result , 0);
        $this->freeResult ( $result );
        if ( is_null($res [0]) ){
            return 1;
        } else {
            return $res [ 0 ];
        }
    }*/

  public function uniqId(){  //generar id unico
    usleep(1);
    return hexdec(uniqid());
  }
}
