<?php
require_once("class/model/EntityQuery.php");
require_once("function/filter_file.php");

class UploadApi {
  /**
   * Controlador de procesamiento de un solo archivo
   */
  public $container;
  public $entityName;
  public $permission = "w";

  public $dir = ""; //directorio relativo donde se almacenara el archivo (sera incluido en el content del fileValue)
  /**
   * Si se define debe poseer DIRECTORY_SEPARATOR al final
   */
  public $file; //archivo subido obtenido del formulario
  public $fileValue; //metadatos (valores) definidos para archivo (id, content)
  /**
   * La ruta resultante del archivo: $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . PATH_UPLOAD . DIRECTORY_SEPARATOR . $this->fileValue->_get("content");
   */
  

  public function __construct (){
    $this->dir = date("Y") . DIRECTORY_SEPARATOR . date("m") . DIRECTORY_SEPARATOR;
  }

  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $this->file = filter_file("file");
    if ( $this->file["error"] > 0 ) throw new Exception ( "Error al subir archivo");

    $this->createDir(); //1 crear directorio en base a atributo dir
    $this->createFileValue(); //2 definir "fileValue", poseera entre otras cosas el content (nombre del archivo)
    $this->moveUploadedFile(); //3 mover archivo segun lo definido en 1 y 2
    $this->insertFile(); //4 insertar datos en la base de datos utilizando 2
    return ["id" => $this->fileValue->_get("id"), "detail" => ["file".$this->fileValue->_get("id")], "file"=>$this->fileValue->_toArray("json")];
  }

  public function createDir(){
    $absoluteDir = $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . PATH_UPLOAD . DIRECTORY_SEPARATOR . $this->dir;
    if(!empty($this->dir) && (!file_exists($absoluteDir))) mkdir($absoluteDir, 0755, true);
  }

  public function createFileValue(){
    $ext = pathinfo($this->file["name"], PATHINFO_EXTENSION);
    $this->fileValue = $this->container->value("file")->_fromArray($this->file,"set")->_call("setDefault");
    $this->fileValue->_set("id",uniqid());
    $this->fileValue->_set("content",$this->dir.$this->fileValue->_get("id").".".$ext);    
    $this->fileValue->_call("reset")->_call("check");
    if($this->fileValue->logs->isError()) throw new Exception($this->fileValue->logs->toString());
  }

  public function moveUploadedFile(){
    $destination = $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . PATH_UPLOAD . DIRECTORY_SEPARATOR . $this->fileValue->_get("content");
    if ( !move_uploaded_file($this->file["tmp_name"], $destination) ) throw new Exception( "Error al mover archivo" );
    unset($this->file["tmp_name"]);
  }

  public function insertFile(){
    $sql = $this->container->persist("file")->insert($this->fileValue->_toArray("sql"));
    $this->container->getDb()->multi_query_transaction($sql);
  }
}

