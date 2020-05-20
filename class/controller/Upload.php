<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");
require_once("class/model/Sqlo.php");

class Upload {
  /**
   * Obtener todos los datos de una determinada entidad
   */

  public $entityName;


  public $sufix = "";

  public $directory;
  
  public function __construct (){
    $this->uploadPath = date("Y/m/");
  }

  final public static function getInstance() {
    $className = get_called_class();
    return new $className;
  }

  final public static function getInstanceString($entity) {
    $className = snake_case_to("XxYy", $entity) . "Upload";
    return call_user_func("{$className}::getInstance");
  }

  final public static function getInstanceRequire($entity){
    require_once("class/controller/upload/" . snake_case_to("XxYy", $entity) . ".php");
    return self::getInstanceString($entity);
  }

  public function main(array $file) {
    if ( $file["error"] > 0 ) throw new Exception ( "Error al subir archivo");
    unset($file["error"]);

    $dir = $_SERVER["DOCUMENT_ROOT"] . "/" . PATH_UPLOAD . "/" . $this->uploadPath;
    $ext = pathinfo($file["name"], PATHINFO_EXTENSION);
    $id = uniqid();
    if(!empty($this->uploadPath) && (!file_exists($dir))) mkdir($dir, 0555, true);

    $file["id"] = $id;
    $file["content"] = $this->uploadPath.$id.$this->sufix.".".$ext;
    if ( !move_uploaded_file($file["tmp_name"], $_SERVER["DOCUMENT_ROOT"] . "/" . PATH_UPLOAD . "/" . $file["content"]) ) throw new Exception( "Error al mover archivo" );
    unset($file["tmp_name"]);

    $this->insertDb($file);
    return $file;
  }

  protected function insertDb($file){
    $insert = EntitySqlo::getInstanceRequire("file")->insert($file);
    Transaction::begin();
    Transaction::update(["descripcion"=> $insert["sql"], "detalle" => implode(",",$insert["detail"])]);
    Transaction::commit();    
  }

}
