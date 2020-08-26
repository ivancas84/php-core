<?php

require_once("class/model/Db.php");

class DbLog extends Db {
  /**
   * Extiende la clase Db para realizar un log de las consultas
   */

  public static $dbInstanceLog = [];

  public static function open($host = DATA_HOST, $user = DATA_USER, $passwd = DATA_PASS, $dbname = DATA_DBNAME){
    if (!key_exists($host.$dbname, self::$dbInstanceLog)) {
      self::$dbInstanceLog[$host.$dbname] = new self($host, $user, $passwd, $dbname);
    } 
    return self::$dbInstanceLog[$host.$dbname];
  }

  public function __destruct(){
    if (key_exists($this->host.$this->dbname, self::$dbInstanceLog) && ($this->thread_id == self::$dbInstanceLog[$this->host.$this->dbname]->thread_id)) {
      unset(self::$dbInstanceLog[$this->host.$this->dbname]);
    }
    parent::close();
  }

  protected function log($query){
    $sql = "
INSERT INTO log (id, description) 
VALUES ('" . uniqid() . "', '{$query}')
    ";
    $db = Db::open($host = TXN_HOST, $user = TXN_USER, $passwd = TXN_PASS, $dbname = TXN_DBNAME);
    echo $sql;
    $db->query($sql);
  }
 
  public function query($query, $resultmode = MYSQLI_STORE_RESULT){
    $result = parent::query($query, $resultmode);
    $this->log($query);
    return $result;    
  }

  
  public function multi_query($query){
    /**
     * cuidado, siempre espera que se recorran los resultados.
     * Se recomienda utilizar multi_query_last si se quiere evitar procesamiento adicional
     */
    if(!$result = parent::multi_query($query)) throw new Exception($this->error);
    $this->log($query);
  }

}

