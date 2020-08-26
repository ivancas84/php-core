<?php

require_once("class/model/Db.php");

class DbLog extends Db {
  /**
   * Extiende la clase Db para realizar un log de las consultas
   */

   protected function log($query){
    $sql = "
INSERT INTO log (id, description) 
VALUES ('" . uniqid() . "', '{$query}')
    ";
    $db = Db::open($host = TXN_HOST, $user = TXN_USER, $passwd = TXN_PASS, $dbname = TXN_DBNAME);
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

