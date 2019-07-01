<?

require_once("class/model/Dba.php");
require_once("class/FileCache.php");

class Transaction {

  public static $id = null; //las transacciones se guardan en sesion mientras se estan ejecutando para poderlas administrar tambien desde el cliente

  //begin transaction
  public static function begin($id = null){
    if(self::$id) throw new Exception("Ya existe una transaccion iniciada");

    if(!empty($id)){
      if(empty($_SESSION["transaction"][$id])) throw new Exception("El id de transaccion es incorrecto");
      self::$id = $id;
      return $id;
    }

    self::$id = Dba::uniqId();

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

  //actualizar transaccion
  public static function update(array $data){
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

  //estado de la cache
  //CLEAR debe limpiarse toda la cache
  //false no debe ejecutarse ninguna accion
  //timestamp Se han ejecutado transacciones posteriores a la fecha indicada
  public static function checkStatus(){
    $timestampCheck = (!empty($_SESSION["check_transaction"])) ? $_SESSION["check_transaction"] : null;
    $_SESSION["check_transaction"] = date("Y-m-d H:i:s");

    if(!isset($timestampCheck)) return "CLEAR";
    $timestampTransaction = FileCache::get("transaction"); //se obtiene ultima transaccion en formato "Y-m-d H:i:s"
    return ($timestampCheck < $timestampTransaction) ? $timestampCheck : false;
  }

  //detalle de la cache
  public static function checkDetails() {
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
    $db = Dba::dbInstance();
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

  //rollback transaction
  public static function rollback(){
    if(empty(self::$id)) throw new UnexpectedValueException("Id de transaccion no definido");
    unset($_SESSION["transaction"][self::$id]);
    self::$id = null;
  }

  public static function commit() {  //commit transaction
    if(empty(self::$id)) throw new UnexpectedValueException("Id de transaccion no definido");

    $db = Dba::dbInstance();
    try {
      $id = $db->escapeString(self::$id);
      if(empty($_SESSION["transaction"][self::$id]["descripcion"])) throw new Exception("Transaccion no definida");
      $descripcion = $_SESSION["transaction"][self::$id]["descripcion"];
      $descripcionEscaped = $db->escapeString($descripcion);  //se escapa para almacenarlo en la base de datos
      $detalle = $db->escapeString($_SESSION["transaction"][self::$id]["detalle"]);

      $tipo = $db->escapeString($_SESSION["transaction"][self::$id]["tipo"]);
      $fecha = $_SESSION["transaction"][self::$id]["actualizado"];

      $queryTransaction = "
        INSERT INTO transaccion (id, actualizado, descripcion, detalle, tipo)
        VALUES (" . $id . ", '" . $fecha . "', '" . $descripcionEscaped . "', '" .$detalle . "', '" . $tipo . "');
      ";

      $db->query($queryTransaction);

      $commitDate = date("Y-m-d H:i:s");
      $queryPersist = $descripcion . " UPDATE transaccion SET tipo = 'commit', actualizado = '" . $commitDate . "' WHERE id = " . $id . ";";

      $db->multiQueryTransaction($queryPersist);

      unset($_SESSION["transaction"][self::$id]);
      self::$id = null;
      FileCache::set("transaction", $commitDate);
    }
    finally { Dba::dbClose(); }
  }
}
