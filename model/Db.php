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

  public function query($query, $resultmode = MYSQLI_STORE_RESULT): mysqli_result|bool {
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

  public function multi_query($query): bool{
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
  
  /**
   * @todo posteriormente reemplazar en php 8!!!
   */
  // public function fetch_all_columns($result, $fieldNumber) {
  //   if ($fieldNumber >= $result->field_count) return array();

  //   $column = array();
  //   while ($val = $result->fetch_column($fieldNumber)) array_push($column,$val);

  //   return $column;
  // }




}

