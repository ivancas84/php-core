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

   /**
    * @Override
    */ 
   static function createFromFormat($format, $time, $timezone = null){
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
   
   
   
   /**
    * @Override
    */
  function format($format){

     $english = array(
      'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun',
      'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
      'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'
    );

    $spanish = array(
      'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom',
      'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo',
      'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
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
