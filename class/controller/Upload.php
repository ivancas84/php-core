<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");
require_once("class/model/Sqlo.php");
require_once("class/model/Values.php");


class Upload {
  /**
   * Controlador de procesamiento de un solo archivo
   */

  public $entityName;
  public $sufix = "";
  public $directory;
    
  public function __construct (){
    $this->uploadPath = date("Y/m/");
  }

  public function main(array $file) {
    if ( $file["error"] > 0 ) throw new Exception ( "Error al subir archivo");

    $this->createDir();
    $fileValue = $this->createFileValue($file);
    $destination = $this->moveUploadedFile($file, $fileValue);
    return $this->insertFile($fileValue);
  }

  public function createDir(){
    $dir = $_SERVER["DOCUMENT_ROOT"] . "/" . PATH_UPLOAD . "/" . $this->uploadPath;
    if(!empty($this->uploadPath) && (!file_exists($dir))) mkdir($dir, 0755, true);
  }

  public function createFileValue($file){
    $ext = pathinfo($file["name"], PATHINFO_EXTENSION);
    $fileValue = $this->container->getValues("file")->_fromArray($file)->_setDefault();
    $fileValue->setContent($this->uploadPath.$fileValue->id().$this->sufix.".".$ext);
    return $fileValue;
  }

  public function moveUploadedFile($file, $fileValue){
    $destination = $_SERVER["DOCUMENT_ROOT"] . "/" . PATH_UPLOAD . "/" . $fileValue->content();
    if ( !move_uploaded_file($file["tmp_name"], $destination) ) throw new Exception( "Error al mover archivo" );
    unset($file["tmp_name"]);
    return $destination;
  }

  public function insertFile($fileValue){
    $f = $fileValue->_toArray();
    $this->container->getDb()->insert("file", $f);
    return $f;
  }
}
