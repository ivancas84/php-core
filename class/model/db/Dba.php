<?php

/**
 * @todo Implementar render en el getall
 */

require_once("class/model/db/My.php");
require_once("class/model/db/Pg.php");

class Dba {
  /**
   * Facilita el acceso a la base de datos
   */
  public static $dbInstance = NULL; //conexion con una determinada db
  public static $dbCount = 0;
  public static $dataHost = DATA_HOST;
  public static $dataUser = DATA_USER;
  public static $dataPass = DATA_PASS;
  public static $dataDbName = DATA_DBNAME;
  public static $dataSchema = DATA_SCHEMA;
  public static $dataDbms = DATA_DBMS;

  public static function dbInstance() { //singleton db
    /**
     * Cuando se abren varios recursos de db instance se incrementa un contador.
     * Al cerrarse recursos se decrementa el contador. 
     * Si el contador llega a 0 se cierra la instancia de la base
     * Si se van a utilizar multiples consultas a la base de datos, se recomienda efectuar una transaccion de recurso:
     * Ejemplo de transaccion de recurso:
     *   self::dbInstance();
     *   try {
     *     ... 
     *   } finally {
     *     self::dbClose();
     *   }
     * La transaccion de recurso evita multiples conexiones a la base de datos en un mismo script
     */
    if (self::$dbCount <= 0) {
      self::$dbCount = 0;
      (DATA_DBMS == "pg") ?
        self::$dbInstance = new DbSqlPg(self::$dataHost, self::$dataUser,self::$dataPass, self::$dataDbName, self::$dataSchema) :
        self::$dbInstance = new DbSqlMy(self::$dataHost, self::$dataUser,self::$dataPass, self::$dataDbName, self::$dataSchema);
    }
    self::$dbCount++;
    return self::$dbInstance;
  }
  public static function dbClose() { //cerrar conexiones a la base de datos
    self::$dbCount--;
    
    if(!self::$dbCount) self::$dbInstance->close(); //cuando todos los recursos liberan la base de datos se cierra
    return self::$dbInstance;
  }

  public static function dbCloseAll() { 
    /**
     * Cierra todas las conexiones e inicializa el contador a 0
     */
    if(self::$dbCount) self::$dbInstance->close();
    self::$dbCount = 0;
  }    
    


  public static function uniqId(){ //identificador unico
    //usleep(1); //con esto se evita que los procesadores generen el mismo id
    //if(isset($_SESSION["uniqid"])) $_SESSION["uniqid"]++;
    //else $_SESSION["uniqid"] = intval(date("Ymdhis"));
    //return $_SESSION["uniqid"];
    return uniqid();
    //return hexdec(uniqid());

    //sleep(1);
    //return strtotime("now");
  }


  public static function fetchRow($sql){
    $db = self::dbInstance();
    try {
      $result = $db->query($sql);
      return $db->fetchRow($result);
    } finally { self::dbClose(); }
  }

  public static function fetchAssoc($sql){
    $db = self::dbInstance();
    try {
      $result = $db->query($sql);
      try { return $db->fetchAssoc($result); }
      finally { $result->close(); }
    } finally { self::dbClose(); }
  }

  public static function fetchAll($sql){
    $db = self::dbInstance();
    try {
      $result = $db->query($sql);

      try { return $db->fetchAll($result); }
      finally { $result->close(); }
    } finally { self::dbClose(); }
  }

  public static function fetchAllTimeAr($sql){
    $db = self::dbInstance();
    try {
      $db->query("SET lc_time_names = 'es_AR';");
      $result = $db->query($sql);
      try { return $db->fetchAll($result); }
      finally { $result->close(); }
    } finally { self::dbClose(); }
  }

  public static function fetchAllColumns($sql, $column = 0){ //query and fetch result
    $db = self::dbInstance();
    try {
      $result = $db->query($sql);
      try { return $db->fetchAllColumns($result, $column); }
      finally { $result->close(); }
    } finally { self::dbClose(); }
  }

}
