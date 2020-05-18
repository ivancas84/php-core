<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");
require_once("class/controller/DisplayRender.php");


class Upload {
  /**
   * Obtener todos los datos de una determinada entidad
   */

  public $entityName;

  public $uploadPath = PATH_UPLOAD;

  public $sufix = "";

  public $directory;
  
  public function __construct (){
    $this->directory = date("Y/m/"); 
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
    $dir = $this->uploadPath."/".$this->directory;
    $ext = pathinfo($file["name"], PATHINFO_EXTENSION);
    $id = uniqid();
    $file["content"] = $dir.$id.$this->sufix.".".$ext;

    if(!empty($this->directory) && (!file_exists($dir))) mkdir($dir, 0555, true);
    if ( !move_uploaded_file($file["tmp_name"], $file["content"] ) ) throw new Exception( "Error al mover archivo" );
    return $id;
  }

}
