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

  public static function dbInstance() { //singleton db
    /**
     * Cuando se abren varios recursos de db instance se incrementa un contador, al cerrarse recursos se decrementa. Si el contador llega a 0 se cierra la instancia de la base
     */
    if (!self::$dbCount) {
      (DATA_DBMS == "pg") ?
        self::$dbInstance = new DbSqlPg(DATA_HOST, DATA_USER, DATA_PASS, DATA_DBNAME, DATA_SCHEMA) :
        self::$dbInstance = new DbSqlMy(DATA_HOST, DATA_USER, DATA_PASS, DATA_DBNAME, DATA_SCHEMA);
    }
    self::$dbCount++;
    return self::$dbInstance;
  }

  public static function dbClose() { //cerrar conexiones a la base de datos
    self::$dbCount--;
    if(!self::$dbCount) self::$dbInstance->close(); //cuando todos los recursos liberan la base de datos se cierra
    return self::$dbInstance;
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
