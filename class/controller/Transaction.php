<?

require_once("class/model/db/Dba.php");
require_once("class/model/db/My.php");
require_once("class/model/db/Pg.php");
require_once("class/tools/FileCache.php");

class Transaction {

  public static $id = null; //las transacciones se guardan en sesion mientras se estan ejecutando para poderlas administrar tambien desde el cliente
  public static $dbInstance = NULL; //conexion con una determinada db
  public static $dbCount = 0;

  public static function dbInstance() { //singleton db
    /**
     * Cuando se abren varios recursos de db instance se incrementa un contador, al cerrarse recursos se decrementa. Si el contador llega a 0 se cierra la instancia de la base
     */
    if (!self::$dbCount) {
      (DATA_DBMS == "pg") ?
        self::$dbInstance = new DbSqlPg(TXN_HOST, TXN_USER, TXN_PASS, TXN_DBNAME, TXN_SCHEMA) :
        self::$dbInstance = new DbSqlMy(TXN_HOST, TXN_USER, TXN_PASS, TXN_DBNAME, TXN_SCHEMA);
    }
    self::$dbCount++;
    return self::$dbInstance;
  }

  public static function uniqId(){ //identificador unico
    return uniqid();
    //if(isset($_SESSION["uniqid"])) $_SESSION["uniqid"]++;
    //else $_SESSION["uniqid"] = intval(date("Ymdhis"));
    //return $_SESSION["uniqid"];
    //return hexdec(uniqid());
    //return strtotime("now");
  }

  public static function begin($id = null){
    /**
     * begin transaction
     */
    if(self::$id) throw new Exception("Ya existe una transaccion iniciada");

    if(!empty($id)){
      if(empty($_SESSION["transaction"][$id])) throw new Exception("El id de transaccion es incorrecto");
      self::$id = $id;
      return $id;
    }

    self::$id = self::uniqId();

    $_SESSION["transaction"][self::$id] = [
      "sql" => null,
      "type" => "begin",
      "description" => "",
      "detail" => "",
      "inserted" => date("Y-m-d H:i:s"),
      "updated" => date("Y-m-d H:i:s"),
    ];
    return self::$id;
  }

  public static function update(array $data){
    /**
     * update transaction
     */
    if(empty(self::$id)) throw new UnexpectedValueException("Id de transaccion no definido");

    if(!empty($data["description"])){
      if(!empty($_SESSION["transaction"][self::$id]["description"])) $_SESSION["transaction"][self::$id]["description"] .= " ";
      $_SESSION["transaction"][self::$id]["description"] .= $data["description"];
    }

    if(!empty($data["detail"])){
      if(!empty($_SESSION["transaction"][self::$id]["detail"])) $_SESSION["transaction"][self::$id]["detail"] .= ",";
      $_SESSION["transaction"][self::$id]["detail"] .= $data["detail"];
    }

    if(!empty($data["type"])) $_SESSION["transaction"][self::$id]["type"] .= $data["type"];

    $_SESSION["transaction"][self::$id]["updated"] = date("Y-m-d H:i:s");

    return self::$id;
  }


  public static function checkStatus(){
    /**
     * Estado de la cache
     * CLEAR debe limpiarse toda la cache
     * false no debe ejecutarse ninguna accion
     * timestamp Se han ejecutado transacciones posteriores a la fecha indicada
     */
    $timestampCheck = (!empty($_SESSION["check_transaction"])) ? $_SESSION["check_transaction"] : null;
    $_SESSION["check_transaction"] = date("Y-m-d H:i:s");

    if(!isset($timestampCheck)) return "CLEAR";
    $timestampTransaction = FileCache::get("transaction"); //se obtiene ultima transaccion en formato "Y-m-d H:i:s"
    return ($timestampCheck < $timestampTransaction) ? $timestampCheck : false;
  }

  public static function checkDetails() {
    /**
     * cache detail
     */
    $status = self::checkStatus();
    if(!$status || $status == "CLEAR") return $status;

    $query = "
SELECT id, detail, updated
FROM transaction
WHERE type = 'commit'
AND updated > '{$status}'
ORDER BY updated ASC
LIMIT 20;
";
    $db = self::dbInstance();
    $result = $db->query($query);
    $numRows = intval($db->numRows($result));

    if($numRows > 0){
      if($numRows == 20) return "CLEAR";

      $rows = $db->fetchAll($result);

      $de = [];
      foreach($rows as $row) $de = array_merge($de, explode(",",$row["detail"]));
      return array_unique($de);
    }
  }

  public static function rollback(){
    /**
     * Rollback transaction
     */
    if(empty(self::$id)) throw new UnexpectedValueException("Id de transaccion no definido");
    unset($_SESSION["transaction"][self::$id]);
    self::$id = null;
  }



  public static function commit() {
    /**
     * Commit transaction
     */
    if(empty(self::$id)) throw new UnexpectedValueException("Id de transaccion no definido");

    $dbT = self::dbInstance();

    try {
      $id = $dbT->escapeString(self::$id);
      if(empty($_SESSION["transaction"][self::$id]["description"])) throw new Exception("Transaccion vacia");
      $description = $_SESSION["transaction"][self::$id]["description"];
      $descriptionEscaped = $dbT->escapeString($description);  //se escapa para almacenarlo en la base de datos
      $detail = $dbT->escapeString($_SESSION["transaction"][self::$id]["detail"]);

      $type = $dbT->escapeString($_SESSION["transaction"][self::$id]["type"]);
      $fecha = $_SESSION["transaction"][self::$id]["updated"];

      $queryTransaction = "
        INSERT INTO transaction (id, updated, description, detail, type)
        VALUES ('" . $id . "', '" . $fecha . "', '" . $descriptionEscaped . "', '" .$detail . "', '" . $type . "');
      ";

      $dbT->query($queryTransaction);
      $dbD = Dba::dbInstance();
      try {
        $commitDate = date("Y-m-d H:i:s");
        $dbD->multiQueryTransaction($description);
        $dbT->query("UPDATE transaction SET type = 'commit', updated = '" . $commitDate . "' WHERE id = '" . $id . "';");
        unset($_SESSION["transaction"][self::$id]);
        self::$id = null;
        FileCache::set("transaction", $commitDate);
      } finally {
        Dba::dbClose();
      }
    }
    finally { 
      self::dbClose();
    }
  }

  public static function dbClose() { //cerrar conexiones a la base de datos
    self::$dbCount--;
    if(!self::$dbCount) self::$dbInstance->close(); //cuando todos los recursos liberan la base de datos se cierra
    return self::$dbInstance;
  }
}
