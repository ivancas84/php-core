<?php

/**
 * format DateTime using spanish names
 *
 * @param DateTime $dateTime
 */ 
class SpanishDateTime extends DateTime{
 
 
   static function createFromDateTime(DateTime $dateTime){
     $spanishDateTime = new SpanishDateTime();
     $spanishDateTime->setTimestamp($dateTime->getTimestamp());
     return $spanishDateTime;
   }

   static function createFromDate(string $time){
    if(empty($time)) return false;
    $fecha = trim(str_replace("-", " ", str_replace("/", " ", $time)));
    $fechas = explode(" ",$fecha);
    if(count($fechas) != 3) return false;

    for($i = 0; $i < 2; $i++){
      /**
       * No conocemos como esta definida la fecha
       * Se van a realizar dos iteraciones para intentar definir la fecha
       */      
        $error = false;
        $mes = intval($fechas[1]);
        if($i===0){
          $dia = intval($fechas[0]);
          $anio = $fechas[2];
        } else {
          $dia = intval($fechas[2]);
          $anio = $fechas[0];
        }
      
        if(($dia < 1) || ($dia > 31)) { 
          $error = true;          
          continue;
        }
  
        if(($mes < 1) || ($mes > 12)) {
          $error = true;          
          continue;
        }
  
        if((strlen($anio) != 2) && (strlen($anio) != 4)) {
          $error = true;          
          continue;
        }
  
        if(!$error) break;
      }
  
      if($error) return false;
  
      if($dia < 10) $dia = "0".$dia;
      if($mes < 10) $mes = "0".$mes;
  
      if(strlen($anio) == 2) {
        $pre = ((intval($anio) >= 0) && (intval($anio) < intval(date("y")))) ? "20" : "19";
        $anio = $pre.$anio;
      }
  
      return self::createFromFormat("dmY",  $dia.$mes.$anio);
  }


   static function createFromFormat($format, $time, $timezone = null): DateTime|false{
     if(!isset($timezone)){
       $dateTime = DateTime::createFromFormat($format, $time);
    } else {
        $dateTime = DateTime::createFromFormat($format, $time, $timezone);
     }
     
     if(!$dateTime) return false;
     
     $spanishDateTime = new SpanishDateTime();
     
     $spanishDateTime->setTimestamp($dateTime->getTimestamp());
     if(isset($timezone)) $spanishDateTime->setTimezone($dateTime->getTimezone());
    
     return $spanishDateTime;
   }

  function format($format): string{

     $english = array(
      'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun',
      'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
      'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December',
      'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'

    );

    $spanish = array(
      'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom',
      'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo',
      'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
      'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'

       );
    return str_replace($english, $spanish, parent::format($format));
  }
  
  //***** crear a partir de format Ymd definido en variables separadas *****
  public static function createFromFormatYmd($Y, $m, $d, $timezone = null){
      if((strlen($Y) != 4) || (strlen($m) > 2) || (strlen($d) > 2)) return false;

      $m =  str_pad($m, 2, '0', STR_PAD_LEFT);
      $d =  str_pad($d, 2, '0', STR_PAD_LEFT);
      

      $date = self::createFromFormat("Ymd", $Y . $m . $d, $timezone);

      return $date;
  }
  
}
 
?>
