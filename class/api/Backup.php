<?php

class BackupApi {
  /**
   * Controlador base
   * Elementos en comun a todos los controladores
   **/
  
  public $entityName; 
  public $container;
  public $permission;

  public function main(){
    $filename=date("Ymd_his").'_magistrados.sql';
    $exec = "C:" . DIRECTORY_SEPARATOR. "xampp" . DIRECTORY_SEPARATOR. "mysql" . DIRECTORY_SEPARATOR. "bin" . DIRECTORY_SEPARATOR. "mysqldump -u ". DATA_USER . " " . DATA_DBNAME . " > " . $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . PATH_BACKUP . DIRECTORY_SEPARATOR . $filename;
    $result=system($exec,$output);
    if($output==''){ return ["file"=>PATH_BACKUP. DIRECTORY_SEPARATOR . $filename]; }
    else throw new Exception("Error al efectuar copia de seguridad");
  }

}
