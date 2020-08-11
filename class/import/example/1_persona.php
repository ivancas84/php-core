<?php

/**
 * CONSIDERACIONES
 * no se procesaran las personas sin dni ya que no puede asignarse la trayectoria
 * si existe mas de una inscripcion por alumno solo se procesara la primera, ignorando la segunda
 * si se vuelve a cargar el mismo archivo (respetando los parámetros), se actualizaran los datos. Con esto se da la posibilidad de correjir los errores en el csv y volverlo a cargar.
 */
require_once("../config/config.php");

require_once("class/import/persona/Persona.php");
set_time_limit ( 0 );

$import = new ImportPersona();

$import->id = $_REQUEST["id"]; //Identificacion del documento a procesar para almacenar los resultados
$import->headers = array_map('trim', explode(",",$_REQUEST["headers"])); //encabezados a procesar
$import->source = $_REQUEST["source"]; //informacion a procesar
$import->mode ="tab"; //modo de procesamiento
$import->pathSummary = $_SERVER["DOCUMENT_ROOT"] ."/".PATH_ROOT . "/doc/import/" . $import->id; //resultados

$import->main();

