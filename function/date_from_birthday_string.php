<?php

function date_from_birthday_string($date){
    if(empty($date)) throw new Exception ("Fecha vacía");
       
    $fechas = explode("/",$date);
    if(count($fechas) != 3) $fechas = explode("-",$date);
    if(count($fechas) != 3) throw new Exception ("Sintaxis incorrecta");

    $dia = intval($fechas[0]);
    $mes = intval($fechas[1]);
    $anio = $fechas[2];   
    if(($dia < 1) || ($dia > 31)) throw new Exception ("Día erroneo");
        
    if(($mes < 1) || ($mes > 12)) throw new Exception ("Mes erroneo");        

    if($dia < 10) $dia = "0".$dia;
    if($mes < 10) $mes = "0".$mes;
   
    if((strlen($anio) != 2) && (strlen($anio) != 4)) {
      $this->addWarning("Año erroneo");
      return;
    }
   
       if(strlen($anio) == 2) {
         if((intval($anio) > 0) && (intval($anio) < intval(date("y")))) $pre = "20";
         else $pre = "19";
         $anio = $pre.$anio;
       }
   
       $fechaNacimiento = DateTime::createFromFormat("d/m/Y",  $dia."/".$mes."/".$anio);
       if(!$fechaNacimiento) {
         $this->addWarning("Fecha de nacimiento erronea");
         return;
       }
   
       if($fechaNacimiento->diff(new DateTime())->y < 18) {
         $this->addWarning("Menor a 18 años");
         return;
       }
   
       $this->fechaNacimiento = $fechaNacimiento->format("Y-m-d");
     }
   