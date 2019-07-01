<?php

require_once("class/db/Interface.php");

class DbSqlPg implements DbInterface {
	
  protected $host;
  protected $user;
  protected $password;
  protected $dbname;
  protected $schema;
  
  protected $connection;
  
  
  public function __construct($host, $user, $password, $dbname, $schema) {
    $this->host = $host;
    $this->user = $user;
    $this->password = $password;
    $this->dbname = $dbname;
    $this->schema = $schema;
    $this->connection  = pg_connect("host=".$this->host." user=".$this->user." password=".$this->password." dbname=".$this->dbname);
    if ($this->connection  == false) throw new Exception("Error al conectar con la base de datos " . pg_last_error($this->connection));
  }
  
  //***** @override *****
  public function getSchema(){
    return $this->schema;
  }
	
  //***** @override *****
  public function getSchemaDot(){
    if (!empty($this->schema)) return $this->schema . ".";
    return "";
  }
  
  //***** @override *****
  public function getDbms(){
    return "postgresql";
  }
  
  
  //***** @override *****
  function query($sql){
    $result = pg_query($sql);
    if (!$result) throw new Exception(pg_last_error($this->connection));
    return $result;
  }
  
  //***** @override *****
  public function multiQuery($query){
    return $this->query($query);
  }
  
  public function multiQueryTransaction($query){        
    try { 
      $this->multiQuery("BEGIN; " . $query);
      return $this->query("COMMIT;");      
    } 
    
    catch (Exception $ex) {
      $this->query("ROLLBACK;");
      throw $ex;
    }
  }
  
  //***** @override *****
  function numRows($result){
    return pg_num_rows($result);
  }
  
  //***** @override *****
  public function numFields($result){
    return pg_num_fields($result);
  }
  
  //***** @override *****
  public function fetchAll($result) {
    if (!$this->numRows($result)) return array();
    return pg_fetch_all ($result);
  }
  
  public function fetchAssoc($result, $row_number = 0){
    return ($this->numRows($result) > 0) ? pg_fetch_assoc($result, $row_number) : null;
  }
  
  function fetchRow($result, $row_number = 0){
    return pg_fetch_row($result,$row_number);
  }
  
  
  function fetchAllColumns($result,$field_number){
    return pg_fetch_all_columns ($result,$field_number);		
  }
  
  function begin() {
    $sql = "BEGIN;";
    return $this->query($sql);
  }
  
  function commit(){
    $sql = "COMMIT;";
    return $this->query($sql);
  }
  
  function rollback(){
    $sql = "ROLLBACK;";
    return $this->query($sql);
  }
    
  function close(){
    $res = pg_close($this->connection);
    if (!$res) throw new Exception(pg_last_error($this->connection));

  }
  
  public function escapeString($string){
    return pg_escape_string($this->connection, $string);
  }

  /**
   
  function autocommit($mode){
      if($mode != false) throw new Exception("Error al ejecutar DbSql->autocommit: " . $this->error());
      $sql = "BEGIN;";
      $res =  $this->query($sql);
      if (!$res) throw new Exception("Error al ejecutar DbSql->autocommit: " . $this->error());
      return $res;
  }

    
	

    
	

    public function isConnected() {
        if (isset($this->connection)) {
            if ((is_resource($this->connection)) && (get_resource_type($this->connection) == "pgsql link") && (pg_ping($this->connection))){
                return true;
            }
        }
        return false;
    }

    

    
				
    

    function error(){
        return pg_last_error($this->$connection);
    }	
	

    function fieldName($result,$field_number){
        return pg_field_name($result,$field_number);
    }


    function freeResult($result){
        return pg_free_result($result);
    }


   
	
   
    function nextVal($table, $field){
        $sql = "SELECT nextval('" . $this->getSchemaDot() . $table . "_" . $field ."_seq');";
        $result = $this->query($sql);
			
        if ( !$result ) throw new Exception("Error al definir next val" );
        if (!( $this -> numRows ($result) )) {
            return 1 ;
        }

        $res = $this->fetchRow ($result , 0);
        $this->freeResult($result);

        if(is_null($res[0])){
            return 1;
        } else {
            return $res[0];
        }
    }
  */
}

?>
