<?php
  
   //***** transformar numero a cuil *****
  //@param String $numero numero de documento
  //@param String $genero Genero = 1 (masculino) || 2 (femenino) || 3 (Sociedad)
  //@return String cuil
  function dni_to_cuil($numero, $genero){
    $numero = str_pad($numero, 8, "0", STR_PAD_LEFT);
        
    switch(intval($genero)){
      case 1: $tipo = '20'; break; //masculino
      case 2: $tipo = '27'; break; //femenino
      case 3: $tipo = '30'; break; //sociedad
      
      case 4: $tipo = '23'; break; //sociedad
      case 5: $tipo = '33'; break; //sociedad
    }

    $multiplicadores = Array('3', '2', '7','6', '5', '4', '3', '2');

    $calculo = (substr($tipo,0,1)*5)+(substr($tipo,1,1)*4);
    for($i=0;$i<8;$i++) { $calculo += substr($numero,$i,1) * $multiplicadores[$i]; }

    $resto = ($calculo)%11;
    
    $oncemenosresto = 11 - $resto;
    
    if($oncemenosresto == 11) { 
      $digito = "0"; 
    }
    
    else if($oncemenosresto == 10) {
      if($genero == "4" || $genero == "5") throw new Exception("Error al calcular cuil");
      $genero = ($genero == "3") ? "5" : "4";
      return dni_to_cuil($numero, $genero);
    }
    
    else {
       $digito = $oncemenosresto;
    }

    return $tipo . $numero . $digito;
  }
