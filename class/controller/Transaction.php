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
    //usleep(1); //con esto se evita que los procesadores generen el mismo id
    //if(isset($_SESSION["uniqid"])) $_SESSION["uniqid"]++;
    //else $_SESSION["uniqid"] = intval(date("Ymdhis"));
    //return $_SESSION["uniqid"];
    //return uniqid();
    return hexdec(uniqid());

    //sleep(1);
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
      "tipo" => "begin",
      "descripcion" => "",
      "detalle" => "",
      "alta" => date("Y-m-d h:i:s"),
      "actualizado" => date("Y-m-d h:i:s"),
    ];
    return self::$id;
  }

  public static function update(array $data){
    /**
     * update transaction
     */
    if(empty(self::$id)) throw new UnexpectedValueException("Id de transaccion no definido");

    if(!empty($data["descripcion"])){
      if(!empty($_SESSION["transaction"][self::$id]["descripcion"])) $_SESSION["transaction"][self::$id]["descripcion"] .= " ";
      $_SESSION["transaction"][self::$id]["descripcion"] .= $data["descripcion"];
    }

    if(!empty($data["detalle"])){
      if(!empty($_SESSION["transaction"][self::$id]["detalle"])) $_SESSION["transaction"][self::$id]["detalle"] .= ",";
      $_SESSION["transaction"][self::$id]["detalle"] .= $data["detalle"];
    }

    if(!empty($data["tipo"])) $_SESSION["transaction"][self::$id]["tipo"] .= $data["tipo"];

    $_SESSION["transaction"][self::$id]["actualizado"] = date("Y-m-d h:i:s");

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
SELECT id, detalle, actualizado
FROM transaccion
WHERE tipo = 'commit'
AND actualizado > '{$status}'
ORDER BY actualizado ASC
LIMIT 20;
";
    $db = self::dbInstance();
    $result = $db->query($query);
    $numRows = intval($db->numRows($result));

    if($numRows > 0){
      if($numRows == 20) return "CLEAR";

      $rows = $db->fetchAll($result);

      $de = [];
      foreach($rows as $row) $de = array_merge($de, explode(",",$row["detalle"]));
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
      if(empty($_SESSION["transaction"][self::$id]["descripcion"])) throw new Exception("Transaccion vacia");
      $descripcion = $_SESSION["transaction"][self::$id]["descripcion"];
      $descripcionEscaped = $dbT->escapeString($descripcion);  //se escapa para almacenarlo en la base de datos
      $detalle = $dbT->escapeString($_SESSION["transaction"][self::$id]["detalle"]);

      $tipo = $dbT->escapeString($_SESSION["transaction"][self::$id]["tipo"]);
      $fecha = $_SESSION["transaction"][self::$id]["actualizado"];

      $queryTransaction = "
        INSERT INTO transaccion (id, actualizado, descripcion, detalle, tipo)
        VALUES (" . $id . ", '" . $fecha . "', '" . $descripcionEscaped . "', '" .$detalle . "', '" . $tipo . "');
      ";

      $dbT->query($queryTransaction);
      $dbD = Dba::dbInstance();
      try {
        $commitDate = date("Y-m-d H:i:s");
        $dbD->multiQueryTransaction($descripcion);
        $dbT->query("UPDATE transaccion SET tipo = 'commit', actualizado = '" . $commitDate . "' WHERE id = " . $id . ";");
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
